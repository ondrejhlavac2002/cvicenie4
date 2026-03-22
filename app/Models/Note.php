<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'status',
        'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'note_category')->withTimestamps();
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('updated_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $nestedQuery) use ($term): void {
            $nestedQuery
                ->where('title', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%");
        });
    }

    public function pin(): bool
    {
        return $this->update(['is_pinned' => true]);
    }

    public function unpin(): bool
    {
        return $this->update(['is_pinned' => false]);
    }

    public function publish(): bool
    {
        return $this->changeStatus(self::STATUS_PUBLISHED);
    }

    public function archive(): bool
    {
        return $this->changeStatus(self::STATUS_ARCHIVED);
    }

    public static function statusBreakdown(): Collection
    {
        return static::query()
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(function (self $note): array {
                return [
                    'status' => $note->status,
                    'count' => (int) $note->count,
                ];
            });
    }

    public static function archiveOldDrafts(int $olderThanDays = 30): int
    {
        return static::query()
            ->where('status', self::STATUS_DRAFT)
            ->where('updated_at', '<', now()->subDays($olderThanDays))
            ->update([
                'status' => self::STATUS_ARCHIVED,
                'updated_at' => now(),
            ]);
    }

    protected function changeStatus(string $status): bool
    {
        return $this->update(['status' => $status]);
    }
}
