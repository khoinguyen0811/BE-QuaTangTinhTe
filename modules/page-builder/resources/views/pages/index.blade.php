@extends('admin.layouts.app')

@section('title', 'Quản lý trang tĩnh')

@section('content')
    <div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3 d-flex flex-column gap-3">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <h4 class="fw-semibold mb-1">Quản lý Trang Tĩnh (Page Builder)</h4>
                    <div class="text-muted">Tạo, chỉnh sửa và thiết kế giao diện các trang tĩnh với trình kéo thả GrapesJS.</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('pagebuilder.dashboard', ['locale' => app()->getLocale()]) }}" class="btn btn-outline-primary d-flex align-items-center gap-1">
                        <i class="ti ti-dashboard fs-5"></i> Tổng quan
                    </a>
                    <a href="{{ route('pagebuilder.pages.create', ['locale' => app()->getLocale()]) }}" class="btn btn-primary d-flex align-items-center gap-1">
                        <i class="ti ti-plus fs-5"></i> Tạo trang mới
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="ti ti-circle-check fs-6 text-success me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="ti ti-circle-x fs-6 text-danger me-2"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle text-nowrap mb-0">
                    <thead class="text-dark fs-3 bg-light">
                        <tr>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Tiêu đề / Đường dẫn</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Trạng thái (Hệ thống)</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Phiên bản xuất bản</h6></th>
                            <th class="border-bottom-0 text-end"><h6 class="fw-semibold mb-0 px-3">Hành động</h6></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pages as $page)
                            @php
                                $published = is_array($page->layout_published) 
                                    ? $page->layout_published 
                                    : (is_string($page->layout_published) ? json_decode($page->layout_published, true) : null);
                            @endphp
                            <tr>
                                <td class="border-bottom-0">
                                    <h6 class="fw-semibold mb-1">{{ $page->title }}</h6>
                                    <span class="text-muted fs-2">/{{ app()->getLocale() }}/{{ $page->slug }}</span>
                                </td>
                                <td class="border-bottom-0">
                                    @if($page->is_active)
                                        <span class="badge bg-success-subtle text-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Tắt</span>
                                    @endif
                                </td>
                                <td class="border-bottom-0">
                                    @if($published)
                                        <span class="badge bg-success">Đã xuất bản (v{{ $published['revision'] ?? '1' }})</span>
                                    @else
                                        <span class="badge bg-secondary">Bản nháp (Chưa xuất bản)</span>
                                    @endif
                                </td>
                                <td class="border-bottom-0 text-end px-3">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <!-- Open Designer -->
                                        <a href="{{ route('pagebuilder.pages.builder', ['locale' => app()->getLocale(), 'page' => $page->id]) }}" 
                                           class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" title="Mở trình kéo thả GrapesJS">
                                            <i class="ti ti-palette fs-4"></i> Thiết kế
                                        </a>

                                        <!-- Preview Draft -->
                                        @php
                                            $previewUrl = URL::signedRoute('pagebuilder.pages.preview', ['locale' => app()->getLocale(), 'page' => $page->id]);
                                        @endphp
                                        <a href="{{ $previewUrl }}" target="_blank" 
                                           class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" title="Xem thử bản nháp">
                                            <i class="ti ti-eye fs-4"></i> Xem thử
                                        </a>

                                        <!-- Publish/Unpublish -->
                                        @if($published)
                                            <button class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1 btn-unpublish" 
                                                    data-url="{{ route('pagebuilder.pages.unpublish', ['locale' => app()->getLocale(), 'page' => $page->id]) }}">
                                                <i class="ti ti-arrow-bar-down fs-4"></i> Gỡ
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 btn-publish" 
                                                    data-url="{{ route('pagebuilder.pages.publish', ['locale' => app()->getLocale(), 'page' => $page->id]) }}">
                                                <i class="ti ti-send fs-4"></i> Xuất bản
                                            </button>
                                        @endif

                                        <!-- Edit Settings -->
                                        <a href="{{ route('pagebuilder.pages.edit', ['locale' => app()->getLocale(), 'page' => $page->id]) }}" 
                                           class="btn btn-sm btn-outline-info" title="Cấu hình trang">
                                            <i class="ti ti-settings fs-4"></i>
                                        </a>

                                        <!-- Delete Page -->
                                        <form method="POST" action="{{ route('pagebuilder.pages.destroy', ['locale' => app()->getLocale(), 'page' => $page->id]) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa trang này?')">
                                                <i class="ti ti-trash fs-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted mb-2">Chưa có trang nào được tạo với Visual Page Builder.</div>
                                    <a href="{{ route('pagebuilder.pages.create', ['locale' => app()->getLocale()]) }}" class="btn btn-primary btn-sm">Tạo trang đầu tiên</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pages->hasPages())
                <div class="px-4 py-3 border-top">
                    {{ $pages->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- AJAX script for Publishing/Unpublishing -->
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const publishBtns = document.querySelectorAll('.btn-publish');
                const unpublishBtns = document.querySelectorAll('.btn-unpublish');

                const handleAjaxAction = (btn, confirmMsg) => {
                    if (!confirm(confirmMsg)) return;
                    
                    const url = btn.getAttribute('data-url');
                    btn.disabled = true;

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert(data.message || 'Có lỗi xảy ra.');
                            btn.disabled = false;
                        }
                    })
                    .catch(err => {
                        alert('Không thể kết nối đến máy chủ.');
                        btn.disabled = false;
                    });
                };

                publishBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        handleAjaxAction(this, 'Bạn có chắc chắn muốn xuất bản bản nháp hiện tại ra storefront?');
                    });
                });

                unpublishBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        handleAjaxAction(this, 'Bạn có chắc chắn muốn gỡ trang này khỏi storefront? Người dùng sẽ không thể truy cập được nữa.');
                    });
                });
            });
        </script>
    @endpush
@endsection
