<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    //
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'folder_id',
        'title',
        'description',
        'extension',
        'current_version_id',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'current_version_id' => 'integer',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(
            DocumentVersion::class,
            'current_version_id'
        );
    }
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
