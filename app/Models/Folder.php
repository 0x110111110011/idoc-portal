<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('access')->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
 * Scope folders to those the specified user may access.
 *
 * Administrators can access all folders.
 * Other users can access folders they own or that are shared with them.
 */
    public function scopeAccessibleTo(
        Builder $query,
        User $user,
    ): Builder {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query
                ->where('created_by', $user->id)
                ->orWhereHas('users', function (Builder $users) use ($user) {
                    $users->where('users.id', $user->id);
                });
        });
    }
}
