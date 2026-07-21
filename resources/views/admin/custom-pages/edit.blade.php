@extends('admin.layouts.app')

@section('title', 'Chỉnh sửa trang tĩnh: ' . $customPage->title)

@section('content')
    <!-- Header Card -->
    <div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fw-semibold mb-1">Chỉnh sửa trang tĩnh: {{ $customPage->title }}</h4>
                    <nav class="py-2" style="--bs-breadcrumb-divider: '&gt;'" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Trang chủ</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.custom-pages.index') }}">Trang tĩnh</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.custom-pages.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:round-arrow-left-line-duotone" class="fs-5"></iconify-icon>
                        Quay lại danh sách
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card">
        <div class="card-body p-4">
            <form action="{{ route('admin.custom-pages.update', $customPage) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <!-- Main Configurations -->
                    <div class="col-lg-8">
                        <h5 class="fw-semibold mb-3 border-bottom pb-2">Thông tin trang tĩnh</h5>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Tiêu đề trang <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $customPage->title) }}" required placeholder="Ví dụ: Giới thiệu về chúng tôi">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label fw-semibold">Đường dẫn tĩnh (Slug)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">/pages/</span>
                                <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $customPage->slug) }}" placeholder="Ví dụ: gioi-thieu">
                            </div>
                            @error('slug')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Đường dẫn tĩnh không được trùng lặp và không chứa ký tự đặc biệt hoặc tiếng Việt có dấu.</small>
                        </div>
                    </div>

                    <!-- Sidebar Configurations (SEO & Status) -->
                    <div class="col-lg-4">
                        <h5 class="fw-semibold mb-3 border-bottom pb-2">Cấu hình SEO & Hiển thị</h5>

                        <div class="mb-3">
                            <label for="is_active" class="form-label fw-semibold d-block">Trạng thái hoạt động</label>
                            <select name="is_active" id="is_active" class="form-select">
                                <option value="1" {{ old('is_active', $customPage->is_active ? '1' : '0') === '1' ? 'selected' : '' }}>Đang hoạt động</option>
                                <option value="0" {{ old('is_active', $customPage->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>Tạm ẩn</option>
                            </select>
                            <small class="text-muted">Khi tạm ẩn, trang tĩnh sẽ không thể truy cập từ bên ngoài website.</small>
                        </div>

                        <div class="mb-3">
                            <label for="seo_title" class="form-label fw-semibold">SEO Title</label>
                            <input type="text" name="seo_title" id="seo_title" class="form-control" value="{{ old('seo_title', $customPage->seo_title) }}" placeholder="Mặc định là tiêu đề trang">
                        </div>

                        <div class="mb-3">
                            <label for="seo_description" class="form-label fw-semibold">SEO Description</label>
                            <textarea name="seo_description" id="seo_description" rows="4" class="form-control" placeholder="Mô tả nội dung trang cho các công cụ tìm kiếm...">{{ old('seo_description', $customPage->seo_description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="seo_image" class="form-label fw-semibold">SEO Share Image (URL)</label>
                            <input type="text" name="seo_image" id="seo_image" class="form-control" value="{{ old('seo_image', $customPage->seo_image) }}" placeholder="Đường dẫn ảnh đại diện khi chia sẻ MXH">
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3 mt-4 text-end">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <iconify-icon icon="solar:diskette-line-duotone" class="fs-5"></iconify-icon>
                        Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
