@extends('admin.layouts.app')

@section('title', 'Bố cục trang chủ')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin-assets/css/home-builder.css') }}?v={{ filemtime(public_path('admin-assets/css/home-builder.css')) }}">
@endpush

@section('content')
    <div
        id="home-builder-root"
        class="home-builder"
        data-csrf="{{ csrf_token() }}"
        data-draft-url="{{ route('admin.home-builder.draft') }}"
        data-save-url="{{ route('admin.home-builder.draft.update') }}"
        data-publish-url="{{ route('admin.home-builder.publish') }}"
        data-versions-url="{{ route('admin.home-builder.versions') }}"
        data-rollback-base="{{ url('/'.app()->getLocale().'/admin/home-builder/rollback') }}"
        data-media-url="{{ route('admin.home-builder.media.index') }}"
        data-upload-url="{{ route('admin.home-builder.media') }}"
        data-preview-url="{{ $previewUrl }}"
    >
        <header class="builder-header">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Bố cục trang chủ</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="mb-0 fw-semibold">Home Builder</h4>
                    <span id="builder-status" class="builder-status">Đang tải dữ liệu...</span>
                </div>
            </div>
            <div class="builder-actions">
                <button id="builder-history" type="button" class="btn btn-outline-secondary">
                    <iconify-icon icon="solar:history-line-duotone"></iconify-icon>
                    Lịch sử
                </button>
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
            <aside id="builder-sections-panel" class="builder-panel builder-sections-panel" aria-label="Danh sách section">
                <div class="builder-panel-heading">
                    <div>
                        <strong>Section trang chủ</strong>
                        <small>Kéo để đổi thứ tự</small>
                    </div>
                </div>
                <div id="builder-section-list" class="builder-section-list">
                    <div class="builder-loading">Đang tải section...</div>
                </div>
            </aside>

            <section class="builder-preview-panel" aria-label="Xem trước trang chủ">
                <div class="builder-preview-toolbar">
                    <div class="builder-preview-toolbar-group">
                        <button
                            id="builder-toggle-sections"
                            type="button"
                            class="builder-panel-toggle"
                            title="Ẩn danh sách section"
                            aria-label="Ẩn danh sách section"
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
                    <span class="builder-preview-hint">Nhấp chữ để sửa trực tiếp · nhấp ảnh để thay ảnh</span>
                    <div class="builder-preview-toolbar-group is-end">
                        <a href="{{ preg_replace('/\?.*$/', '', $previewUrl) }}" target="_blank" rel="noopener" class="builder-open-preview">
                            Mở trang
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
                        title="Xem trước trang chủ"
                        src="{{ $previewUrl }}"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                    ></iframe>
                </div>
            </section>

            <aside id="builder-inspector-panel" class="builder-panel builder-inspector-panel" aria-label="Thuộc tính section">
                <div class="builder-panel-heading">
                    <div>
                        <strong id="builder-inspector-title">Thuộc tính</strong>
                        <small id="builder-inspector-type">Chọn một section để chỉnh sửa</small>
                    </div>
                </div>
                <div id="builder-inspector" class="builder-inspector">
                    <div class="builder-empty">Chọn section ở danh sách hoặc trong preview.</div>
                </div>
            </aside>
        </div>

        <div class="modal fade" id="builderHistoryModal" tabindex="-1" aria-labelledby="builderHistoryTitle" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="builderHistoryTitle">Lịch sử bố cục trang chủ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div id="builder-history-list" class="modal-body">
                        <div class="builder-loading">Đang tải lịch sử...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade builder-media-modal" id="builderMediaModal" tabindex="-1" aria-labelledby="builderMediaTitle" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="builder-media-title-wrap">
                            <h5 class="modal-title" id="builderMediaTitle">Chọn ảnh từ thư viện</h5>
                            <span class="builder-media-info-wrap">
                                <button type="button" class="builder-media-info" aria-label="Thông tin lưu trữ ảnh" aria-describedby="builderMediaInfoTooltip">
                                    <iconify-icon icon="solar:info-circle-line-duotone"></iconify-icon>
                                </button>
                                <span id="builderMediaInfoTooltip" class="builder-media-info-tooltip" role="tooltip">
                                    Ảnh mới ưu tiên tải lên Cloudinary khi đã cấu hình. Nếu chưa cấu hình hoặc Cloudinary gặp lỗi, hệ thống tự lưu cục bộ. Upload Cloudinary có thể chậm hơn một chút vì ảnh được truyền tới dịch vụ bên ngoài; ảnh có sẵn được dùng ngay, không tải lại.
                                </span>
                            </span>
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
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin-assets/js/home-builder.js') }}?v={{ filemtime(public_path('admin-assets/js/home-builder.js')) }}"></script>
@endpush
