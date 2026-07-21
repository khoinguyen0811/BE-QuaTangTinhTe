@extends('admin.layouts.app')

@section('title', 'Bố cục trang tĩnh: ' . $customPage->title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin-assets/css/home-builder.css') }}?v={{ filemtime(public_path('admin-assets/css/home-builder.css')) }}">
@endpush

@section('content')
    <div
        id="custom-page-builder-root"
        class="home-builder"
        data-csrf="{{ csrf_token() }}"
        data-page-id="{{ $customPage->id }}"
        data-lock-version="{{ $customPage->lock_version }}"
        data-draft-url="{{ route('admin.custom-pages.draft', $customPage) }}"
        data-save-url="{{ route('admin.custom-pages.layout.update', $customPage) }}"
        data-publish-url="{{ route('admin.custom-pages.publish', $customPage) }}"
        data-media-url="{{ route('admin.custom-pages.media.index', $customPage) }}"
        data-upload-url="{{ route('admin.custom-pages.media', $customPage) }}"
        data-preview-url="{{ $previewUrl }}"
    >
        <header class="builder-header">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.custom-pages.index') }}">Trang tĩnh</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Bố cục</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="mb-0 fw-semibold">{{ $customPage->title }}</h4>
                    <span id="builder-status" class="builder-status">Đang tải dữ liệu...</span>
                </div>
            </div>
            <div class="builder-actions">
                <button id="builder-save" type="button" class="btn btn-outline-primary" disabled>
                    <iconify-icon icon="solar:diskette-line-duotone"></iconify-icon>
                    Lưu bản nháp
                </button>
                <button id="builder-publish" type="button" class="btn btn-primary" disabled>
                    <iconify-icon icon="solar:upload-minimalistic-line-duotone"></iconify-icon>
                    Xuất bản
                </button>
            </div>
        </header>

        <div id="builder-alert" class="alert d-none" role="alert"></div>

        <div class="builder-workspace" aria-busy="true">
            <aside id="builder-sections-panel" class="builder-panel builder-sections-panel" aria-label="Danh sách block">
                <div class="builder-panel-heading d-flex align-items-center justify-content-between">
                    <div>
                        <strong>Khối nội dung (Blocks)</strong>
                        <small>Kéo thả để sắp xếp thứ tự</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary p-1 px-2 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addBlockModal">
                        <iconify-icon icon="solar:add-circle-line-duotone"></iconify-icon>
                        Thêm
                    </button>
                </div>
                <div id="builder-section-list" class="builder-section-list">
                    <div class="builder-loading">Đang tải cấu trúc...</div>
                </div>
            </aside>

            <section class="builder-preview-panel" aria-label="Xem trước trang tĩnh">
                <div class="builder-preview-toolbar">
                    <div class="builder-preview-toolbar-group">
                        <button
                            id="builder-toggle-sections"
                            type="button"
                            class="builder-panel-toggle"
                            title="Ẩn danh sách block"
                            aria-label="Ẩn danh sách block"
                            aria-controls="builder-sections-panel"
                            aria-expanded="true"
                        >
                            <iconify-icon icon="solar:alt-arrow-left-line-duotone"></iconify-icon>
                        </button>
                        <div class="builder-device-switch" role="group" aria-label="Kích thước xem trước">
                            <button type="button" data-viewport="desktop" class="is-active" title="Desktop" aria-label="Desktop">
                                <iconify-icon icon="solar:monitor-line-duotone"></iconify-icon>
                            </button>
                            <button type="button" data-viewport="tablet" title="Tablet" aria-label="Tablet">
                                <iconify-icon icon="solar:tablet-line-duotone"></iconify-icon>
                            </button>
                            <button type="button" data-viewport="mobile" title="Mobile" aria-label="Mobile">
                                <iconify-icon icon="solar:smartphone-line-duotone"></iconify-icon>
                            </button>
                        </div>
                    </div>
                    <span class="builder-preview-hint">Nhấp để chỉnh sửa nội dung trực tiếp</span>
                    <div class="builder-preview-toolbar-group is-end">
                        <a id="builder-open-live-page" href="{{ $previewUrl }}" target="_blank" rel="noopener" class="builder-open-preview">
                            Xem nháp
                            <iconify-icon icon="solar:square-top-down-line-duotone"></iconify-icon>
                        </a>
                        <button
                            id="builder-toggle-inspector"
                            type="button"
                            class="builder-panel-toggle"
                            title="Ẩn bảng thuộc tính"
                            aria-label="Ẩn bảng thuộc tính"
                            aria-controls="builder-inspector-panel"
                            aria-expanded="true"
                        >
                            <iconify-icon icon="solar:alt-arrow-right-line-duotone"></iconify-icon>
                        </button>
                    </div>
                </div>
                <div id="builder-preview-stage" class="builder-preview-stage" data-viewport="desktop">
                    <iframe
                        id="builder-preview-frame"
                        title="Xem trước trang tĩnh"
                        src="{{ $previewUrl }}"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                    ></iframe>
                </div>
            </section>

            <aside id="builder-inspector-panel" class="builder-panel builder-inspector-panel" aria-label="Thuộc tính block">
                <div class="builder-panel-heading d-flex align-items-center justify-content-between">
                    <div>
                        <strong id="builder-inspector-title">Thuộc tính block</strong>
                        <small id="builder-inspector-type">Chọn một block để chỉnh sửa</small>
                    </div>
                    <button type="button" class="builder-inspector-close" id="builder-inspector-close" title="Đóng bảng">
                        <iconify-icon icon="solar:close-square-line-duotone" class="fs-6"></iconify-icon>
                    </button>
                </div>
                <div id="builder-inspector" class="builder-inspector">
                    <div class="builder-empty">Chọn block ở danh sách hoặc trong preview.</div>
                </div>
            </aside>
        </div>

        <div class="modal fade builder-media-modal" id="builderMediaModal" tabindex="-1" aria-labelledby="builderMediaTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="builder-media-title-wrap">
                            <h5 class="modal-title" id="builderMediaTitle">Chọn ảnh từ thư viện</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="builder-media-toolbar">
                            <div class="builder-media-search">
                                <iconify-icon icon="solar:magnifer-line-duotone"></iconify-icon>
                                <input id="builder-media-search" type="search" class="form-control" placeholder="Tìm theo tên ảnh..." autocomplete="off">
                            </div>
                            <div class="builder-media-toolbar-actions">
                                <span id="builder-media-storage" class="builder-media-storage">Đang kiểm tra lưu trữ...</span>
                                <button id="builder-media-upload" type="button" class="btn btn-outline-primary">
                                    <iconify-icon icon="solar:upload-line-duotone"></iconify-icon>
                                    Tải ảnh từ máy
                                </button>
                                <input id="builder-media-file" type="file" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" hidden>
                            </div>
                        </div>
                        <div id="builder-media-alert" class="alert d-none" role="alert"></div>
                        <div id="builder-media-grid" class="builder-media-grid" aria-live="polite">
                            <div class="builder-loading">Đang tải thư viện ảnh...</div>
                        </div>
                    </div>
                    <div class="modal-footer builder-media-footer">
                        <span id="builder-media-selection" class="builder-media-selection">Chưa chọn ảnh</span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button id="builder-media-use" type="button" class="btn btn-primary" disabled>
                                <iconify-icon icon="solar:check-circle-line-duotone"></iconify-icon>
                                Dùng ảnh này
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="addBlockModal" tabindex="-1" aria-labelledby="addBlockTitle" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBlockTitle">Thêm block nội dung mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="list-group list-group-flush">
                            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" data-add-block-type="rich_text">
                                <iconify-icon icon="solar:document-text-line-duotone" class="fs-4 text-primary"></iconify-icon>
                                <div>
                                    <strong class="d-block">Văn bản Rich Text</strong>
                                    <small class="text-muted">Soạn thảo văn bản tự do, tiêu đề, danh sách, căn lề.</small>
                                </div>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" data-add-block-type="faq">
                                <iconify-icon icon="solar:question-square-line-duotone" class="fs-4 text-success"></iconify-icon>
                                <div>
                                    <strong class="d-block">Accordion FAQ</strong>
                                    <small class="text-muted">Nhóm các câu hỏi và trả lời dạng xếp gọn (Accordion).</small>
                                </div>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" data-add-block-type="contact_form">
                                <iconify-icon icon="solar:letter-line-duotone" class="fs-4 text-warning"></iconify-icon>
                                <div>
                                    <strong class="d-block">Biểu mẫu liên hệ (Contact Form)</strong>
                                    <small class="text-muted">Form gửi thông tin liên hệ kèm địa chỉ và bản đồ Google Maps.</small>
                                </div>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" data-add-block-type="feature_columns">
                                <iconify-icon icon="solar:widget-2-line-duotone" class="fs-4 text-info"></iconify-icon>
                                <div>
                                    <strong class="d-block">Cột tính năng nổi bật (Feature Columns)</strong>
                                    <small class="text-muted">Hiển thị các khối thông tin chia thành 2, 3 hoặc 4 cột.</small>
                                </div>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" data-add-block-type="image_text">
                                <iconify-icon icon="solar:gallery-wide-line-duotone" class="fs-4 text-danger"></iconify-icon>
                                <div>
                                    <strong class="d-block">Hình ảnh và Văn bản (Image & Text)</strong>
                                    <small class="text-muted">Hình ảnh bố cục xen kẽ với tiêu đề, mô tả và nút bấm CTA.</small>
                                </div>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" data-add-block-type="cta">
                                <iconify-icon icon="solar:megaphone-line-duotone" class="fs-4 text-purple"></iconify-icon>
                                <div>
                                    <strong class="d-block">Khối kêu gọi hành động (CTA Block)</strong>
                                    <small class="text-muted">Khối tiêu đề nổi bật thu hút lượt click kèm nút liên kết.</small>
                                </div>
                            </button>
                            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3" data-add-block-type="spacer_divider">
                                <iconify-icon icon="solar:scissors-square-line-duotone" class="fs-4 text-muted"></iconify-icon>
                                <div>
                                    <strong class="d-block">Spacer & Divider</strong>
                                    <small class="text-muted">Tạo khoảng cách trống hoặc đường kẻ phân chia các khối.</small>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/custom-page-builder/index.js'])
@endpush

