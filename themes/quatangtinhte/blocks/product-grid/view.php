<?php
$products = $block->products();
$columns = $block->columns();
?>
<style>
    .pagebuilder-product-grid__items { grid-template-columns: repeat(var(--product-grid-columns), minmax(0, 1fr)); }
    @media (max-width: 900px) { .pagebuilder-product-grid__items { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 560px) { .pagebuilder-product-grid__items { grid-template-columns: minmax(0, 1fr); } }
</style>
<section class="pagebuilder-product-grid" style="padding:50px 20px;">
    <div style="max-width:1100px;margin:0 auto;">
        <?php if ($block->title() !== ''): ?>
            <h2 style="font-size:2rem;font-weight:700;color:#333;margin:0 0 12px;text-align:center;"><?= phpb_e($block->title()) ?></h2>
        <?php endif; ?>
        <?php if ($block->description() !== ''): ?>
            <p style="color:#888;margin:0 0 40px;text-align:center;"><?= phpb_e($block->description()) ?></p>
        <?php endif; ?>

        <?php if ($products->isEmpty()): ?>
            <div style="padding:32px;border:1px dashed #cbd5e1;border-radius:12px;text-align:center;color:#64748b;background:#f8fafc;">
                Không có sản phẩm phù hợp với cấu hình khối này.
            </div>
        <?php else: ?>
            <div class="pagebuilder-product-grid__items" style="--product-grid-columns:<?= $columns ?>;display:grid;gap:20px;">
                <?php foreach ($products as $product): ?>
                    <?php
                    $price = (float) $product->price;
                    $comparePrice = (float) $product->compare_at_price;
                    $hasDiscount = $block->showComparePrice() && $comparePrice > $price && $price > 0;
                    $discount = $hasDiscount ? (int) round((1 - ($price / $comparePrice)) * 100) : 0;
                    $url = '/collection/' . rawurlencode($product->slug) . '/';
                    ?>
                    <article style="background:#fff;border:1px solid #edf0f2;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,.08);position:relative;min-width:0;">
                        <?php if ($hasDiscount): ?>
                            <span style="position:absolute;z-index:1;top:12px;left:12px;padding:4px 10px;background:#e74c3c;color:#fff;border-radius:4px;font-size:.75rem;font-weight:600;">-<?= $discount ?>%</span>
                        <?php elseif ($product->badge): ?>
                            <span style="position:absolute;z-index:1;top:12px;left:12px;padding:4px 10px;background:#143944;color:#fff;border-radius:4px;font-size:.75rem;font-weight:600;"><?= phpb_e($product->badge) ?></span>
                        <?php endif; ?>
                        <a href="<?= phpb_e($url) ?>" style="display:block;color:inherit;text-decoration:none;">
                            <?php if ($product->image_url): ?>
                                <img src="<?= phpb_e($product->image_url) ?>" alt="<?= phpb_e($product->name) ?>" loading="lazy" style="width:100%;aspect-ratio:1/1;object-fit:cover;display:block;background:#f5f5f5;">
                            <?php else: ?>
                                <span style="display:flex;aspect-ratio:1/1;align-items:center;justify-content:center;background:#f5f5f5;color:#94a3b8;">Chưa có ảnh</span>
                            <?php endif; ?>
                        </a>
                        <div style="padding:16px;">
                            <a href="<?= phpb_e($url) ?>" style="color:inherit;text-decoration:none;">
                                <h3 style="font-size:.95rem;line-height:1.45;font-weight:600;color:#333;margin:0 0 8px;"><?= phpb_e($product->name) ?></h3>
                            </a>
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span style="font-weight:700;color:#e74c3c;"><?= number_format($price, 0, ',', '.') ?>đ</span>
                                <?php if ($hasDiscount): ?>
                                    <span style="font-size:.8rem;color:#9ca3af;text-decoration:line-through;"><?= number_format($comparePrice, 0, ',', '.') ?>đ</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($block->showButton()): ?>
                                <a href="<?= phpb_e($url) ?>" style="display:block;margin-top:14px;padding:9px 14px;background:#143944;color:#fff;border-radius:7px;text-decoration:none;text-align:center;font-size:.85rem;font-weight:600;">Xem sản phẩm</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
