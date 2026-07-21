@extends('admin.layouts.app')

@section('title', 'Cấu hình trang Visual Page Builder')

@section('content')
    <div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3 d-flex flex-column gap-3">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <h4 class="fw-semibold mb-1">Cấu hình trang: {{ $page->title }}</h4>
                    <div class="text-muted">Cập nhật thông tin cơ bản, slug và SEO cho trang.</div>
                </div>
                <a href="{{ route('pagebuilder.pages.index', ['locale' => app()->getLocale()]) }}" class="btn btn-outline-primary d-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left fs-5"></i> Quay lại danh sách
                </a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
            <h6 class="fw-semibold mb-1"><i class="ti ti-alert-circle me-1"></i> Có lỗi xảy ra khi nhập liệu:</h6>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('pagebuilder.pages.update', ['locale' => app()->getLocale(), 'page' => $page->id]) }}">
                @csrf
                @method('PUT')

                <!-- Basic Info -->
                <h5 class="fw-semibold mb-3">Thông tin cơ bản</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="title" class="form-label fw-semibold">Tiêu đề trang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $page->title) }}" placeholder="Nhập tiêu đề trang..." required>
                    </div>
                    <div class="col-md-6">
                        <label for="slug" class="form-label fw-semibold">Đường dẫn tĩnh (Slug)</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $page->slug) }}" placeholder="Để trống hệ thống tự sinh từ tiêu đề...">
                        <div class="form-text">Lưu ý: Thay đổi đường dẫn tĩnh sẽ làm thay đổi URL truy cập trang.</div>
                    </div>
                </div>

                <!-- SEO Config -->
                <h5 class="fw-semibold mb-3">Cấu hình SEO</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="seo_title" class="form-label fw-semibold">Tiêu đề SEO</label>
                        <input type="text" class="form-control" id="seo_title" name="seo_title" value="{{ old('seo_title', $page->seo_title) }}" placeholder="Tiêu đề hiển thị trên thanh tab của trình duyệt...">
                    </div>
                    <div class="col-md-6">
                        <label for="seo_image" class="form-label fw-semibold">Hình ảnh SEO (URL)</label>
                        <input type="text" class="form-control" id="seo_image" name="seo_image" value="{{ old('seo_image', $page->seo_image) }}" placeholder="URL hình ảnh preview khi share link...">
                    </div>
                    <div class="col-12">
                        <label for="seo_description" class="form-label fw-semibold">Mô tả SEO</label>
                        <textarea class="form-control" id="seo_description" name="seo_description" rows="3" placeholder="Mô tả tóm tắt nội dung trang khi tìm kiếm trên Google...">{{ old('seo_description', $page->seo_description) }}</textarea>
                    </div>
                </div>

                <!-- System Config -->
                <h5 class="fw-semibold mb-3">Hệ thống</h5>
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $page->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_active">Hoạt động (Cho phép truy cập ở storefront)</label>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary px-4">Cập nhật cấu hình</button>
                </div>
            </form>
        </div>
    </div>
@endsection
