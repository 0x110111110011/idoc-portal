<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
        //
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => [
                'nullable',
                Rule::in([
                    UserRole::ADMIN->value,
                    UserRole::USER->value,
                ]),
            ],
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive']),
            ],
        ]);

        $search = trim($filters['search'] ?? '');

        $query = User::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->whereRaw('name ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('email ILIKE ?', ["%{$search}%"]);
                });
            })
            ->when(
                $filters['role'] ?? null,
                fn(Builder $query, string $role)
                    => $query->where('role', $role),
            )
            ->when(
                ($filters['status'] ?? null) === 'active',
                fn(Builder $query) => $query->where('is_active', true),
            )
            ->when(
                ($filters['status'] ?? null) === 'inactive',
                fn(Builder $query) => $query->where('is_active', false),
            );

        $users = $query
            ->select([
                'id',
                'name',
                'email',
                'role',
                'is_active',
                'email_verified_at',
                'created_at',
            ])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn(User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'created_at' => $user->created_at?->toISOString(),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,

            'filters' => [
                'search' => $search,
                'role' => $filters['role'] ?? '',
                'status' => $filters['status'] ?? '',
            ],

            'flash' => [
                'success' => session('success'),
                'warning' => session('warning'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/create', [
            'roles' => $this->roles(),
        ]);
    }

    public function store(
        StoreUserRequest $request,
    ): RedirectResponse {
        $data = $request->validated();
        $actor = $request->user();

        $user = DB::transaction(function () use (
            $data,
            $actor,
            $request,
        ): User {
            $user = User::query()->create($data);

            $this->activityLogService->record(
                action: 'admin.user_created',
                subject: $user,
                description: "User account {$user->email} was created.",
                properties: [
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'is_active' => $user->is_active,
                ],
                user: $actor,
                request: $request,
            );

            return $user;
        });

        $warning = $this->sendVerificationEmail($user);

        return to_route('admin.users.index')
            ->with('success', 'User created successfully.')
            ->with('warning', $warning);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
            ],

            'roles' => $this->roles(),
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
    ): RedirectResponse {
        $data = $request->validated();
        $actor = $request->user();

        $newRole = UserRole::from($data['role']);
        $newActive = filter_var(
            $data['is_active'],
            FILTER_VALIDATE_BOOLEAN,
        );

        $requestedPassword = $data['password'] ?? null;

        unset($data['password']);

        $shouldSendVerification = false;

        $updatedUser = DB::transaction(function () use (
            $user,
            $actor,
            $request,
            $data,
            $newRole,
            $newActive,
            $requestedPassword,
            &$shouldSendVerification,
        ): User {
            /*
             * Lock administrator records in a consistent order.
             * This protects the last-active-administrator check
             * against concurrent account changes using this controller.
             */
            $administrators = User::query()
                ->where('role', UserRole::ADMIN->value)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $target = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            if ($target->is($actor) && ! $newActive) {
                throw ValidationException::withMessages([
                    'is_active' => 'You cannot deactivate your own account.',
                ]);
            }

            if (
                $target->is($actor)
                && $newRole !== UserRole::ADMIN
            ) {
                throw ValidationException::withMessages([
                    'role' => 'You cannot remove your own administrator role.',
                ]);
            }

            $currentIsActiveAdmin
                = $target->role === UserRole::ADMIN
                && $target->is_active;

            $willRemainActiveAdmin
                = $newRole === UserRole::ADMIN
                && $newActive;

            if ($currentIsActiveAdmin && ! $willRemainActiveAdmin) {
                $activeAdminCount = $administrators
                    ->filter(fn(User $admin) => $admin->is_active)
                    ->count();

                if ($activeAdminCount <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'You cannot remove or deactivate the last active administrator.',
                    ]);
                }
            }

            $before = $this->snapshot($target);

            $shouldSendVerification
                = $target->email !== $data['email'];

            // An email change requires verification again.
            if ($shouldSendVerification) {
                $target->email_verified_at = null;
            }

            if ($requestedPassword !== null && $requestedPassword !== '') {
                $data['password'] = $requestedPassword;
            }

            $target->fill($data);
            $target->save();

            $this->activityLogService->record(
                action: 'admin.user_updated',
                subject: $target,
                description: "User account {$target->email} was updated.",
                properties: [
                    'before' => $before,
                    'after' => $this->snapshot($target),
                ],
                user: $actor,
                request: $request,
            );

            return $target;
        });

        $warning = null;

        if ($shouldSendVerification) {
            $warning = $this->sendVerificationEmail($updatedUser);
        }

        return to_route('admin.users.index')
            ->with('success', 'User updated successfully.')
            ->with('warning', $warning);
    }

    /**
     * Send verification email when the model supports email verification.
     */
    private function sendVerificationEmail(User $user): ?string
    {
        if (
            ! $user instanceof MustVerifyEmail
            || $user->hasVerifiedEmail()
        ) {
            return null;
        }

        try {
            $user->sendEmailVerificationNotification();

            return null;
        } catch (Throwable $exception) {
            report($exception);

            return 'The account was saved, but the verification email could not be sent. Check your mail configuration and resend it.';
        }
    }

    /**
     * Return role options for the React forms.
     */
    private function roles(): array
    {
        return array_map(
            fn(UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ],
            UserRole::cases(),
        );
    }

    /**
     * Exclude passwords and other private fields from audit snapshots.
     */
    private function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
        ];
    }
}
