<?php

$categoryOptions = [['id' => '0', 'name' => 'Tất cả danh mục']];
foreach (\App\Models\Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get() as $category) {
    $categoryOptions[] = ['id' => (string) $category->id, 'name' => (string) $category->name];
}

return [
    'title' => 'Lưới sản phẩm',
    'category' => 'Sản phẩm',
    'icon' => 'fa fa-th-large',
    'cache' => false,
    'settings' => [
        'title' => ['label' => 'Tiêu đề', 'type' => 'text', 'value' => 'Sản phẩm nổi bật'],
        'description' => ['label' => 'Mô tả', 'type' => 'text', 'value' => 'Khám phá các sản phẩm được chọn lọc'],
        'category_id' => ['label' => 'Danh mục', 'type' => 'select', 'value' => '0', 'options' => $categoryOptions],
        'sort' => [
            'label' => 'Sắp xếp', 'type' => 'select', 'value' => 'latest',
            'options' => [
                ['id' => 'latest', 'name' => 'Mới nhất'],
                ['id' => 'oldest', 'name' => 'Cũ nhất'],
                ['id' => 'featured', 'name' => 'Nổi bật trước'],
                ['id' => 'price_asc', 'name' => 'Giá tăng dần'],
                ['id' => 'price_desc', 'name' => 'Giá giảm dần'],
                ['id' => 'name_asc', 'name' => 'Tên A–Z'],
                ['id' => 'name_desc', 'name' => 'Tên Z–A'],
            ],
        ],
        'limit' => [
            'label' => 'Số lượng hiển thị', 'type' => 'select', 'value' => '8',
            'options' => array_map(fn ($value) => ['id' => (string) $value, 'name' => (string) $value], [4, 6, 8, 12, 16, 20, 24]),
        ],
        'columns' => [
            'label' => 'Số cột', 'type' => 'select', 'value' => '4',
            'options' => array_map(fn ($value) => ['id' => (string) $value, 'name' => (string) $value], [2, 3, 4, 5, 6]),
        ],
        'show_compare_price' => ['label' => 'Hiện giá so sánh', 'type' => 'yes_no', 'value' => 1],
        'show_button' => ['label' => 'Hiện nút xem sản phẩm', 'type' => 'yes_no', 'value' => 1],
    ],
];
