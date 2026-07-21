@extends('admin.layouts.app')

@section('title', __('catalog.variants.edit'))

@section('content')
    <div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-0">{{ __('catalog.variants.edit') }}: {{ $product->name }}</h4>
        </div>
    </div>

    @if ($errors->has('conflict'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('conflict') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.products.variants.update', [$product, $variant]) }}" class="admin-form-with-sticky-actions">
        @csrf
        @method('PUT')
        <input type="hidden" name="updated_at" value="{{ optional($variant->updated_at)->format('Y-m-d\TH:i:s.uP') }}">
        @include('admin.catalog.variants._form')
    </form>
@endsection
