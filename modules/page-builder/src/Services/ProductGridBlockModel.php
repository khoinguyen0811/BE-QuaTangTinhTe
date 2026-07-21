<?php

namespace HansSchouten\LaravelPageBuilder\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use PHPageBuilder\Modules\GrapesJS\Block\BaseModel;

class ProductGridBlockModel extends BaseModel
{
    public function products(): Collection
    {
        $query = Product::query()->where('is_active', true);
        $categoryId = (int) $this->setting('category_id');

        if ($categoryId > 0) {
            $categoryIds = $this->categoryAndDescendantIds($categoryId);
            $query->whereHas('categories', function ($categoryQuery) use ($categoryIds) {
                $categoryQuery->whereIn('categories.id', $categoryIds);
            });
        }

        switch ($this->setting('sort')) {
            case 'oldest': $query->oldest(); break;
            case 'price_asc': $query->orderBy('price')->orderBy('id'); break;
            case 'price_desc': $query->orderByDesc('price')->orderByDesc('id'); break;
            case 'name_asc': $query->orderBy('name')->orderBy('id'); break;
            case 'name_desc': $query->orderByDesc('name')->orderByDesc('id'); break;
            case 'featured': $query->orderByDesc('is_featured')->latest(); break;
            default: $query->latest(); break;
        }

        return $query->limit(min(24, max(1, (int) $this->setting('limit'))))->get();
    }

    public function title(): string { return (string) $this->setting('title'); }
    public function description(): string { return (string) $this->setting('description'); }
    public function columns(): int { return min(6, max(2, (int) $this->setting('columns'))); }
    public function showComparePrice(): bool { return (bool) (int) $this->setting('show_compare_price'); }
    public function showButton(): bool { return (bool) (int) $this->setting('show_button'); }

    private function categoryAndDescendantIds(int $categoryId): array
    {
        $categories = Category::query()->select(['id', 'parent_id'])->get();
        $ids = [$categoryId];

        do {
            $newIds = $categories->whereIn('parent_id', $ids)->pluck('id')->diff($ids)->values()->all();
            $ids = array_values(array_unique(array_merge($ids, $newIds)));
        } while ($newIds !== []);

        return $ids;
    }
}
