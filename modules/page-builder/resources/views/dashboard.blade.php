@extends('admin.layouts.app')

@section('title', 'Tổng quan Page Builder')

@section('content')
    <div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3 d-flex flex-column gap-3">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <h4 class="fw-semibold mb-1">Visual Page Builder</h4>
                    <div class="text-muted">Tùy biến và thiết kế trang tĩnh bằng trình thiết kế kéo thả GrapesJS.</div>
                </div>
                <nav style="--bs-breadcrumb-divider: '&gt;'" aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('admin.home') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Page Builder</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Stats row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white card-hover">
                <div class="card-body p-4 text-center">
                    <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex p-3 mb-3">
                        <i class="ti ti-files fs-8"></i>
                    </div>
                    <h3 class="fw-semibold mb-1">{{ $totalPages }}</h3>
                    <p class="text-muted mb-0">Tổng số trang</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white card-hover">
                <div class="card-body p-4 text-center">
                    <div class="bg-success-subtle text-success rounded-circle d-inline-flex p-3 mb-3">
                        <i class="ti ti-circle-check fs-8"></i>
                    </div>
                    <h3 class="fw-semibold mb-1">{{ $publishedPages }}</h3>
                    <p class="text-muted mb-0">Đã xuất bản</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white card-hover">
                <div class="card-body p-4 text-center">
                    <div class="bg-info-subtle text-info rounded-circle d-inline-flex p-3 mb-3">
                        <i class="ti ti-toggle-left fs-8"></i>
                    </div>
                    <h3 class="fw-semibold mb-1">{{ $activePages }}</h3>
                    <p class="text-muted mb-0">Trang đang hoạt động</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions block -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="card-title fw-semibold mb-4">Chức năng quản trị</h5>
            <div class="d-flex gap-3 flex-wrap">
                <a href="{{ route('pagebuilder.pages.index', ['locale' => app()->getLocale()]) }}" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2">
                    <i class="ti ti-list-details fs-5"></i> Danh sách trang tĩnh
                </a>
                <a href="{{ route('pagebuilder.pages.create', ['locale' => app()->getLocale()]) }}" class="btn btn-success d-inline-flex align-items-center gap-2 px-4 py-2">
                    <i class="ti ti-plus fs-5"></i> Tạo trang mới
                </a>
            </div>
        </div>
    </div>
@endsection
