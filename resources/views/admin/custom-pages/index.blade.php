@extends('admin.layouts.app')

@section('title', 'Quản lý trang tĩnh')

@section('content')
    <!-- Header Card -->
    <div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fw-semibold mb-1">Quản lý trang tĩnh</h4>
                    <nav class="py-2" style="--bs-breadcrumb-divider: '&gt;'" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Trang chủ</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Trang tĩnh</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.custom-pages.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:add-circle-line-duotone" class="fs-5"></iconify-icon>
                        Tạo trang mới
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Notification -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center gap-2">
                <iconify-icon icon="solar:check-circle-line-duotone" class="fs-5"></iconify-icon>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Pages List Card -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-nowrap text-muted">
                            <th class="ps-4 fw-semibold small">Tiêu đề trang</th>
                            <th class="fw-semibold small">Đường dẫn (Slug)</th>
                            <th class="fw-semibold small">Trạng thái hoạt động</th>
                            <th class="fw-semibold small">Thời gian xuất bản</th>
                            <th class="fw-semibold small">Cập nhật cuối bởi</th>
                            <th class="pe-4 text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pages as $page)
                            <tr class="text-nowrap">
                                <td class="ps-4">
                                    <h6 class="fw-semibold mb-1 fs-3">
                                        <a href="{{ route('admin.custom-pages.builder', $page) }}" class="text-decoration-none text-dark hover-primary">
                                            {{ $page->title }}
                                        </a>
                                    </h6>
                                    <span class="text-muted small">Cập nhật lúc: {{ $page->updated_at?->format('H:i d/m/Y') }}</span>
                                </td>
                                <td>
                                    <code class="text-primary">/pages/{{ $page->slug }}</code>
                                </td>
                                <td>
                                    @if($page->is_active)
                                        <span class="badge bg-success-subtle text-success">Đang hoạt động</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Tạm ẩn</span>
                                    @endif
                                </td>
                                <td>
                                    @if($page->published_at && $page->layout_published)
                                        <span class="text-success small fw-medium">
                                            <iconify-icon icon="solar:upload-minimalistic-line-duotone" class="align-middle me-1"></iconify-icon>
                                            {{ $page->published_at->format('H:i d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted small">Chưa xuất bản (Bản nháp)</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="small text-dark">{{ $page->updater?->name ?: 'Hệ thống' }}</span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <a href="{{ route('admin.custom-pages.builder', $page) }}" class="btn btn-sm btn-primary d-flex align-items-center gap-1" title="Mở Page Builder">
                                            <iconify-icon icon="solar:pen-new-square-line-duotone"></iconify-icon>
                                            Page Builder
                                        </a>
                                        <a href="{{ route('admin.custom-pages.edit', $page) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" title="Chỉnh sửa thông tin chung">
                                            <iconify-icon icon="solar:settings-line-duotone"></iconify-icon>
                                            Sửa cấu hình
                                        </a>
                                        <form action="{{ route('admin.custom-pages.destroy', $page) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa trang tĩnh này? Các liên kết liên quan sẽ không hoạt động.')" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" title="Xóa trang">
                                                <iconify-icon icon="solar:trash-bin-trash-line-duotone"></iconify-icon>
                                                Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">Chưa có trang tĩnh nào được tạo. Nhấp "Tạo trang mới" để bắt đầu.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pages->hasPages())
                <div class="card-footer border-top p-4">
                    {{ $pages->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
