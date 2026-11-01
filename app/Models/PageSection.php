<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    use HasFactory;

    public const PAGE_WORK = 'work';

    public const PAGE_AGENCY = 'agency';

    public const PAGE_CAREERS = 'careers';

    /**
     * @var list<string>
     */
    public const PAGES = [
        self::PAGE_WORK,
        self::PAGE_AGENCY,
        self::PAGE_CAREERS,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'page',
        'title',
        'subtitle',
        'content',
        'image_path',
        'video_url',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<PageSection>  $query
     * @return Builder<PageSection>
     */
    public function scopeForPage(Builder $query, string $page): Builder
    {
        return $query
            ->where('page', $page)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
