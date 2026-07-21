/**
 * GrapesJS Element Blocks - Injected via customScripts('body')
 * 
 * Adds fundamental building-block elements that users can freely combine
 * to create custom sections. These are low-level primitives (not full sections).
 * 
 * Compatible with GrapesJS 0.15.9 + PHPageBuilder block system.
 */
(function() {
    if (!window.editor) return;

    var bm = window.editor.BlockManager;

    // ─── CATEGORY: Bố cục (Layout) ──────────────────────────────
    bm.add('column-1', {
        label: '<i class="fa fa-square-o" style="font-size:28px;display:block;margin-bottom:4px"></i> 1 Cột',
        category: 'Bố cục',
        content: '<div style="padding:10px;"><div style="min-height:60px;padding:10px;" phpb-blocks-container="true"></div></div>',
        attributes: { class: 'gjs-fonts gjs-f-b1' }
    });

    bm.add('column-2', {
        label: '<i class="fa fa-columns" style="font-size:28px;display:block;margin-bottom:4px"></i> 2 Cột',
        category: 'Bố cục',
        content: '<div style="display:flex;flex-wrap:wrap;gap:16px;padding:10px;">' +
            '<div style="flex:1;min-width:200px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '<div style="flex:1;min-width:200px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '</div>',
        attributes: { class: 'gjs-fonts gjs-f-b2' }
    });

    bm.add('column-3', {
        label: '<i class="fa fa-th" style="font-size:28px;display:block;margin-bottom:4px"></i> 3 Cột',
        category: 'Bố cục',
        content: '<div style="display:flex;flex-wrap:wrap;gap:16px;padding:10px;">' +
            '<div style="flex:1;min-width:180px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '<div style="flex:1;min-width:180px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '<div style="flex:1;min-width:180px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '</div>',
        attributes: { class: 'gjs-fonts gjs-f-b3' }
    });

    bm.add('column-3-7', {
        label: '<i class="fa fa-th-large" style="font-size:28px;display:block;margin-bottom:4px"></i> 30/70',
        category: 'Bố cục',
        content: '<div style="display:flex;flex-wrap:wrap;gap:16px;padding:10px;">' +
            '<div style="flex:3;min-width:150px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '<div style="flex:7;min-width:250px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '</div>',
        attributes: { class: 'gjs-fonts gjs-f-b37' }
    });

    bm.add('column-4', {
        label: '<i class="fa fa-th" style="font-size:28px;display:block;margin-bottom:4px"></i> 4 Cột',
        category: 'Bố cục',
        content: '<div style="display:flex;flex-wrap:wrap;gap:16px;padding:10px;">' +
            '<div style="flex:1;min-width:150px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '<div style="flex:1;min-width:150px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '<div style="flex:1;min-width:150px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '<div style="flex:1;min-width:150px;min-height:60px;padding:10px;" phpb-blocks-container="true"></div>' +
            '</div>',
    });

    // ─── CATEGORY: Phần tử cơ bản (Elements) ───────────────────
    bm.add('text-element', {
        label: '<i class="fa fa-font" style="font-size:28px;display:block;margin-bottom:4px"></i> Văn bản',
        category: 'Phần tử',
        content: '<p style="padding:10px;font-size:1rem;line-height:1.7;color:#333;">Nhấn đúp để chỉnh sửa văn bản này.</p>',
        attributes: { class: 'gjs-fonts gjs-f-text' }
    });

    bm.add('heading-element', {
        label: '<i class="fa fa-header" style="font-size:28px;display:block;margin-bottom:4px"></i> Tiêu đề',
        category: 'Phần tử',
        content: '<h2 style="padding:10px;font-size:1.75rem;font-weight:700;color:#333;">Tiêu đề</h2>',
    });

    bm.add('image-element', {
        label: '<i class="fa fa-picture-o" style="font-size:28px;display:block;margin-bottom:4px"></i> Hình ảnh',
        category: 'Phần tử',
        content: '<img src="https://placehold.co/600x400/e8e8e8/999?text=Chọn+ảnh" alt="Hình ảnh" style="max-width:100%;border-radius:8px;">',
        attributes: { class: 'gjs-fonts gjs-f-image' }
    });

    bm.add('button-element', {
        label: '<i class="fa fa-hand-pointer-o" style="font-size:28px;display:block;margin-bottom:4px"></i> Nút bấm',
        category: 'Phần tử',
        content: '<a href="#" style="display:inline-block;padding:12px 28px;background:#667eea;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.95rem;text-align:center;">Nút bấm</a>',
    });

    bm.add('link-element', {
        label: '<i class="fa fa-link" style="font-size:28px;display:block;margin-bottom:4px"></i> Liên kết',
        category: 'Phần tử',
        content: '<a href="#" style="color:#667eea;text-decoration:underline;font-size:1rem;">Liên kết</a>',
    });

    bm.add('video-element', {
        label: '<i class="fa fa-youtube-play" style="font-size:28px;display:block;margin-bottom:4px"></i> Video',
        category: 'Phần tử',
        content: '<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:12px;"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;" allowfullscreen></iframe></div>',
    });

    bm.add('map-element', {
        label: '<i class="fa fa-map-marker" style="font-size:28px;display:block;margin-bottom:4px"></i> Bản đồ',
        category: 'Phần tử',
        content: '<div style="border-radius:12px;overflow:hidden;"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.5!2d106.7!3d10.78!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTDCsDQ2JzQ4LjAiTiAxMDbCsDQyJzAwLjAiRQ!5e0!3m2!1svi!2s!4v1" style="width:100%;height:350px;border:none;" allowfullscreen loading="lazy"></iframe></div>',
    });

    bm.add('list-element', {
        label: '<i class="fa fa-list" style="font-size:28px;display:block;margin-bottom:4px"></i> Danh sách',
        category: 'Phần tử',
        content: '<ul style="padding-left:20px;font-size:1rem;line-height:2;color:#555;"><li>Mục thứ nhất</li><li>Mục thứ hai</li><li>Mục thứ ba</li></ul>',
    });

    bm.add('quote-element', {
        label: '<i class="fa fa-quote-left" style="font-size:28px;display:block;margin-bottom:4px"></i> Trích dẫn',
        category: 'Phần tử',
        content: '<blockquote style="border-left:4px solid #667eea;padding:16px 24px;margin:16px 0;background:#f8f9ff;border-radius:0 8px 8px 0;"><p style="font-style:italic;color:#555;font-size:1.05rem;line-height:1.7;margin:0;">Nội dung trích dẫn ở đây...</p><footer style="margin-top:8px;color:#999;font-size:0.85rem;">— Tác giả</footer></blockquote>',
    });

    bm.add('table-element', {
        label: '<i class="fa fa-table" style="font-size:28px;display:block;margin-bottom:4px"></i> Bảng',
        category: 'Phần tử',
        content: '<table style="width:100%;border-collapse:collapse;font-size:0.95rem;">' +
            '<thead><tr style="background:#667eea;color:#fff;"><th style="padding:12px 16px;text-align:left;">Cột 1</th><th style="padding:12px 16px;text-align:left;">Cột 2</th><th style="padding:12px 16px;text-align:left;">Cột 3</th></tr></thead>' +
            '<tbody><tr style="border-bottom:1px solid #eee;"><td style="padding:12px 16px;">Dữ liệu 1</td><td style="padding:12px 16px;">Dữ liệu 2</td><td style="padding:12px 16px;">Dữ liệu 3</td></tr>' +
            '<tr style="border-bottom:1px solid #eee;background:#f9f9f9;"><td style="padding:12px 16px;">Dữ liệu 4</td><td style="padding:12px 16px;">Dữ liệu 5</td><td style="padding:12px 16px;">Dữ liệu 6</td></tr></tbody></table>',
    });

    bm.add('icon-box', {
        label: '<i class="fa fa-star" style="font-size:28px;display:block;margin-bottom:4px"></i> Icon Box',
        category: 'Phần tử',
        content: '<div style="text-align:center;padding:24px;">' +
            '<div style="width:64px;height:64px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:16px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.8rem;">⭐</div>' +
            '<h4 style="font-size:1.1rem;font-weight:600;color:#333;margin-bottom:8px;">Tiêu đề</h4>' +
            '<p style="color:#888;font-size:0.9rem;line-height:1.6;">Mô tả ngắn gọn về nội dung này.</p></div>',
    });

    bm.add('badge-element', {
        label: '<i class="fa fa-tag" style="font-size:28px;display:block;margin-bottom:4px"></i> Badge',
        category: 'Phần tử',
        content: '<span style="display:inline-block;padding:4px 12px;background:#667eea;color:#fff;border-radius:20px;font-size:0.8rem;font-weight:600;">Badge</span>',
    });

    // ─── CATEGORY: Form Elements ────────────────────────────────
    bm.add('form-element', {
        label: '<i class="fa fa-wpforms" style="font-size:28px;display:block;margin-bottom:4px"></i> Form',
        category: 'Biểu mẫu',
        content: '<form style="max-width:500px;padding:24px;">' +
            '<div style="margin-bottom:16px;"><label style="display:block;font-weight:600;margin-bottom:6px;color:#333;font-size:0.9rem;">Họ tên</label><input type="text" placeholder="Nhập họ tên..." style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem;outline:none;"></div>' +
            '<div style="margin-bottom:16px;"><label style="display:block;font-weight:600;margin-bottom:6px;color:#333;font-size:0.9rem;">Email</label><input type="email" placeholder="email@example.com" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem;outline:none;"></div>' +
            '<div style="margin-bottom:16px;"><label style="display:block;font-weight:600;margin-bottom:6px;color:#333;font-size:0.9rem;">Tin nhắn</label><textarea rows="4" placeholder="Nhập tin nhắn..." style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem;outline:none;resize:vertical;"></textarea></div>' +
            '<button type="submit" style="padding:12px 32px;background:#667eea;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:0.95rem;cursor:pointer;">Gửi</button></form>',
    });

    bm.add('input-element', {
        label: '<i class="fa fa-i-cursor" style="font-size:28px;display:block;margin-bottom:4px"></i> Ô nhập',
        category: 'Biểu mẫu',
        content: '<div style="margin-bottom:16px;"><label style="display:block;font-weight:600;margin-bottom:6px;color:#333;font-size:0.9rem;">Nhãn</label><input type="text" placeholder="Nhập nội dung..." style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem;"></div>',
    });

    bm.add('textarea-element', {
        label: '<i class="fa fa-align-left" style="font-size:28px;display:block;margin-bottom:4px"></i> Textarea',
        category: 'Biểu mẫu',
        content: '<div style="margin-bottom:16px;"><label style="display:block;font-weight:600;margin-bottom:6px;color:#333;font-size:0.9rem;">Nhãn</label><textarea rows="4" placeholder="Nhập nội dung..." style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem;resize:vertical;"></textarea></div>',
    });

    bm.add('select-element', {
        label: '<i class="fa fa-chevron-down" style="font-size:28px;display:block;margin-bottom:4px"></i> Dropdown',
        category: 'Biểu mẫu',
        content: '<div style="margin-bottom:16px;"><label style="display:block;font-weight:600;margin-bottom:6px;color:#333;font-size:0.9rem;">Chọn</label><select style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem;background:#fff;"><option>Lựa chọn 1</option><option>Lựa chọn 2</option><option>Lựa chọn 3</option></select></div>',
    });

    // Trigger lazy loading refresh
    if (window.initLazyLoading) {
        setTimeout(window.initLazyLoading, 100);
    }
})();
