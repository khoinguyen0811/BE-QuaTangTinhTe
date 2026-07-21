@extends('admin.layouts.app')

@section('title', __('admin.settings.title'))

@section('content')
    <!-- Header Card -->
    <div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3 d-flex flex-column gap-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fw-semibold mb-1">{{ __('admin.settings.title') }}</h4>
                    <nav class="py-2" style="--bs-breadcrumb-divider: '&gt;'" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('admin.home') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ __('admin.settings.title') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link d-flex active align-items-center gap-2 py-3" data-bs-toggle="tab" href="#general-pane" role="tab">
                <span><i class="ti ti-info-circle fs-4"></i></span>
                <span class="d-none d-md-block ms-1">{{ __('admin.settings.tabs.general') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 py-3" data-bs-toggle="tab" href="#contact-pane" role="tab">
                <span><i class="ti ti-phone fs-4"></i></span>
                <span class="d-none d-md-block ms-1">{{ __('admin.settings.tabs.contact') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 py-3" data-bs-toggle="tab" href="#social-pane" role="tab">
                <span><i class="ti ti-share fs-4"></i></span>
                <span class="d-none d-md-block ms-1">{{ __('admin.settings.tabs.social') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 py-3" data-bs-toggle="tab" href="#seo-pane" role="tab">
                <span><i class="ti ti-search fs-4"></i></span>
                <span class="d-none d-md-block ms-1">{{ __('admin.settings.tabs.seo') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 py-3" data-bs-toggle="tab" href="#theme-pane" role="tab">
                <span><i class="ti ti-palette fs-4"></i></span>
                <span class="d-none d-md-block ms-1">{{ __('admin.settings.tabs.theme') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2 py-3" data-bs-toggle="tab" href="#ui-components-pane" role="tab">
                <span><i class="ti ti-components fs-4"></i></span>
                <span class="d-none d-md-block ms-1">Thành phần UI</span>
            </a>
        </li>
    </ul>

    <!-- Form -->
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" id="settingsForm">
        @csrf
        <div class="tab-content" id="settingTabsContent">
            <!-- General Pane -->
            <div class="tab-pane fade show active" id="general-pane" role="tabpanel" aria-labelledby="general-tab">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4 text-dark">{{ __('admin.settings.general.title') }}</h5>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold text-dark" for="shop_name">{{ __('admin.settings.general.shop_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-dark" id="shop_name" name="shop_name" 
                                    value="{{ old('shop_name', $settings->get('shop_name')) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark" for="logo">{{ __('admin.settings.general.logo') }}</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                <div class="form-text text-dark">{{ __('admin.settings.general.logo_help') }}</div>
                                @if($settings->get('logo_url'))
                                    <div class="mt-3">
                                        <p class="small text-dark mb-1">{{ __('admin.settings.general.logo_current') }}</p>
                                        <img src="{{ $settings->get('logo_url') }}" alt="Logo" class="img-thumbnail" style="max-height: 80px;">
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark" for="favicon">{{ __('admin.settings.general.favicon') }}</label>
                                <input type="file" class="form-control" id="favicon" name="favicon" accept="image/*">
                                <div class="form-text text-dark">{{ __('admin.settings.general.favicon_help') }}</div>
                                @if($settings->get('favicon_url'))
                                    <div class="mt-3">
                                        <p class="small text-dark mb-1">{{ __('admin.settings.general.favicon_current') }}</p>
                                        <img src="{{ $settings->get('favicon_url') }}" alt="Favicon" class="img-thumbnail" style="max-height: 40px;">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Pane -->
            <div class="tab-pane fade" id="contact-pane" role="tabpanel" aria-labelledby="contact-tab">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4 text-dark">{{ __('admin.settings.contact.title') }}</h5>
                        @php
                            $contact = $settings->get('contact') ?? [];
                        @endphp
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark" for="contact_phone">{{ __('admin.settings.contact.phone') }}</label>
                                <input type="text" class="form-control text-dark" id="contact_phone" name="contact[phone]" 
                                    value="{{ old('contact.phone', $contact['phone'] ?? '') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark" for="contact_email">{{ __('admin.settings.contact.email') }}</label>
                                <input type="email" class="form-control text-dark" id="contact_email" name="contact[email]" 
                                    value="{{ old('contact.email', $contact['email'] ?? '') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold text-dark" for="contact_address">{{ __('admin.settings.contact.address') }}</label>
                                <textarea class="form-control text-dark" id="contact_address" name="contact[address]" rows="4">{{ old('contact.address', $contact['address'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Pane -->
            <div class="tab-pane fade" id="social-pane" role="tabpanel" aria-labelledby="social-tab">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4 text-dark">{{ __('admin.settings.social.title') }}</h5>
                        @php
                            $social = $settings->get('social_links') ?? [];
                        @endphp
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark" for="social_facebook">Facebook URL</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-brand-facebook fs-5 text-primary"></i></span>
                                    <input type="url" class="form-control text-dark" id="social_facebook" name="social_links[facebook]" 
                                        placeholder="https://facebook.com/yourpage" value="{{ old('social_links.facebook', $social['facebook'] ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark" for="social_youtube">YouTube Channel URL</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-brand-youtube fs-5 text-danger"></i></span>
                                    <input type="url" class="form-control text-dark" id="social_youtube" name="social_links[youtube]" 
                                        placeholder="https://youtube.com/c/yourchannel" value="{{ old('social_links.youtube', $social['youtube'] ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark" for="social_instagram">Instagram URL</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-brand-instagram fs-5 text-info"></i></span>
                                    <input type="url" class="form-control text-dark" id="social_instagram" name="social_links[instagram]" 
                                        placeholder="https://instagram.com/yourprofile" value="{{ old('social_links.instagram', $social['instagram'] ?? '') }}">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark" for="social_tiktok">TikTok URL</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-brand-tiktok fs-5 text-dark"></i></span>
                                    <input type="url" class="form-control text-dark" id="social_tiktok" name="social_links[tiktok]" 
                                        placeholder="https://tiktok.com/@yourprofile" value="{{ old('social_links.tiktok', $social['tiktok'] ?? '') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO Pane -->
            <div class="tab-pane fade" id="seo-pane" role="tabpanel" aria-labelledby="seo-tab">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4 text-dark">{{ __('admin.settings.seo.title') }}</h5>
                        @php
                            $seo = $settings->get('seo') ?? [];
                        @endphp
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                @php
                                    $strictPostGate = filter_var(old('seo.strict_post_gate', $seo['strict_post_gate'] ?? true), FILTER_VALIDATE_BOOLEAN);
                                @endphp
                                <div class="border rounded-3 p-3 bg-light-subtle d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="ti ti-shield-check fs-5 text-primary"></i>
                                            <label class="form-label fw-bold text-dark mb-0" for="seo_strict_post_gate">SEO Gate nghiêm khắc cho bài viết</label>
                                        </div>
                                        <p class="text-muted mb-0 small">
                                            Khi bật, bài viết phải đạt đủ 100% tiêu chí SEO mới được xuất bản. Khi tắt, hệ thống vẫn chấm điểm và cảnh báo nhưng không chặn xuất bản.
                                        </p>
                                    </div>
                                    <div class="form-check form-switch m-0 flex-shrink-0">
                                        <input type="hidden" name="seo[strict_post_gate]" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="seo_strict_post_gate"
                                            name="seo[strict_post_gate]" value="1" @checked($strictPostGate)
                                            style="width: 3rem; height: 1.5rem; cursor: pointer;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold text-dark" for="seo_title">{{ __('admin.settings.seo.meta_title') }}</label>
                                <input type="text" class="form-control text-dark" id="seo_title" name="seo[title]" 
                                    value="{{ old('seo.title', $seo['title'] ?? '') }}" placeholder="{{ __('admin.settings.seo.meta_title_placeholder') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold text-dark" for="seo_description">{{ __('admin.settings.seo.meta_desc') }}</label>
                                <textarea class="form-control text-dark" id="seo_description" name="seo[description]" rows="4" 
                                    placeholder="{{ __('admin.settings.seo.meta_desc_placeholder') }}">{{ old('seo.description', $seo['description'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Theme Pane -->
            <div class="tab-pane fade" id="theme-pane" role="tabpanel" aria-labelledby="theme-tab">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4 text-dark">{{ __('admin.settings.theme.title') }}</h5>
                        @php
                            $theme = $settings->get('theme') ?? [];
                        @endphp
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark" for="theme_color">{{ __('admin.settings.theme.primary_color') }}</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color border" id="theme_color" name="theme[primary_color]" 
                                        value="{{ old('theme.primary_color', $theme['primary_color'] ?? '#0d6efd') }}" title="{{ __('admin.settings.theme.color_title') }}">
                                    <span class="text-dark small font-monospace fw-semibold">{{ $theme['primary_color'] ?? '#0d6efd' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-dark" for="theme_layout">{{ __('admin.settings.theme.layout') }}</label>
                                <select class="form-select text-dark" id="theme_layout" name="theme[layout]">
                                    <option value="default" @selected(old('theme.layout', $theme['layout'] ?? 'default') === 'default')>{{ __('admin.settings.theme.layout_default') }}</option>
                                    <option value="compact" @selected(old('theme.layout', $theme['layout'] ?? 'default') === 'compact')>{{ __('admin.settings.theme.layout_compact') }}</option>
                                    <option value="boxed" @selected(old('theme.layout', $theme['layout'] ?? 'default') === 'boxed')>{{ __('admin.settings.theme.layout_boxed') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- UI Components Pane -->
            <div class="tab-pane fade" id="ui-components-pane" role="tabpanel" aria-labelledby="ui-components-tab">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                            <div>
                                <h5 class="card-title fw-semibold mb-1 text-dark">Thành phần UI</h5>
                                <p class="text-muted mb-0 small">Quản lý các khối giao diện dùng chung trên website như navbar, header, footer và các khu vực có thể mở rộng sau này.</p>
                            </div>
                        </div>

                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                                <div>
                                    <h6 class="fw-semibold mb-1 text-dark">Navbar chính</h6>
                                    <p class="text-muted mb-0 small">Chỉnh trực tiếp các mục đang hiển thị trên thanh điều hướng. Mỗi mục có thể là link thường, dropdown một cột hoặc mega menu nhiều cột.</p>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold" id="load-current-navbar">
                                        <i class="ti ti-refresh me-1"></i> Nạp navbar hiện tại
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm fw-semibold" id="add-navbar-item">
                                        <i class="ti ti-plus me-1"></i> Thêm menu
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" name="navigation_menu" id="navigation_menu_payload">
                            <div id="navbar-builder" class="d-flex flex-column gap-3"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Sticky Save Button -->
        <div class="mt-4 pt-2 mb-5">
            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                <i class="ti ti-device-floppy me-1 fs-5"></i> {{ __('admin.settings.save_settings') }}
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const savedNavigationMenu = @json($settings->get('navigation_menu') ?? []);
        const defaultNavigationMenu = [
            { label: 'Trang chủ', href: '/', badge: '', visible: true, dropdown_mode: 'single', children: [], columns: [] },
            { label: 'Giới thiệu', href: '/about', badge: '', visible: true, dropdown_mode: 'single', children: [], columns: [] },
            { label: 'Bộ sưu tập', href: '/collection', badge: '', visible: true, dropdown_mode: 'single', children: [], columns: [] },
            { label: 'Bài viết', href: '/posts', badge: '', visible: true, dropdown_mode: 'single', children: [], columns: [] },
            { label: 'Liên hệ', href: '/contact', badge: '', visible: true, dropdown_mode: 'single', children: [], columns: [] },
        ];
        const initialNavigationMenu = Array.isArray(savedNavigationMenu) && savedNavigationMenu.length
            ? savedNavigationMenu
            : defaultNavigationMenu;

        document.addEventListener('DOMContentLoaded', function () {
            const navbarEditor = initNavbarBuilder(initialNavigationMenu);
            const form = document.getElementById('settingsForm');
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    navbarEditor.sync();

                    Swal.fire({
                        title: '{{ __('admin.settings.saving') }}',
                        text: '{{ __('admin.settings.please_wait') }}',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const formData = new FormData(form);

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __('admin.success') }}',
                                text: data.message || '{{ __('admin.settings.updated') }}',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __('admin.error') }}',
                                text: data.message || '{{ __('admin.settings.save_failed') }}'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        let errMsg = '{{ __('admin.failed_to_connect') }}';
                        if (error.errors) {
                            errMsg = Object.values(error.errors).flat().join('\n');
                        } else if (error.message) {
                            errMsg = error.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('admin.error') }}',
                            text: errMsg
                        });
                    });
                });
            }
        });

        function initNavbarBuilder(initialMenu) {
            const builder = document.getElementById('navbar-builder');
            const payload = document.getElementById('navigation_menu_payload');
            const addButton = document.getElementById('add-navbar-item');
            const loadCurrentButton = document.getElementById('load-current-navbar');
            let menu = normalizeNavigationMenu(initialMenu);

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const emptyLink = (label = 'Menu con mới') => ({ label, href: '#', visible: true });
            const emptyColumn = (title = 'Cột mới') => ({ title, items: [emptyLink('Item mới')] });
            const syncPayload = () => {
                if (payload) payload.value = JSON.stringify(menu);
            };

            function normalizeLinks(links) {
                return Array.isArray(links)
                    ? links.filter(Boolean).map(link => ({
                        label: String(link.label || '').trim(),
                        href: String(link.href || '#').trim() || '#',
                        visible: link.visible !== false,
                    }))
                    : [];
            }

            function normalizeColumns(columns) {
                return Array.isArray(columns)
                    ? columns.filter(Boolean).map(column => ({
                        title: String(column.title || '').trim(),
                        items: normalizeLinks(column.items),
                    }))
                    : [];
            }

            function normalizeNavigationMenu(value) {
                return Array.isArray(value)
                    ? value.filter(Boolean).map(item => ({
                        label: String(item.label || '').trim(),
                        href: String(item.href || '#').trim() || '#',
                        badge: String(item.badge || '').trim(),
                        visible: item.visible !== false,
                        dropdown_mode: item.dropdown_mode === 'multi' ? 'multi' : 'single',
                        children: normalizeLinks(item.children),
                        columns: normalizeColumns(item.columns),
                    }))
                    : [];
            }

            function linkRow(link, className, actionDelete, extraAttrs = '') {
                return `
                    <div class="${className} row g-2 align-items-center py-2 border-top" ${extraAttrs}>
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm js-link-label" value="${escapeHtml(link.label)}" placeholder="Tên item">
                        </div>
                        <div class="col-md-5">
                            <input type="text" class="form-control form-control-sm js-link-href" value="${escapeHtml(link.href)}" placeholder="/collection hoặc https://...">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check">
                                <input class="form-check-input js-link-visible" type="checkbox" ${link.visible !== false ? 'checked' : ''}>
                                <label class="form-check-label small">Hiện</label>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-light btn-sm text-danger" data-action="${actionDelete}" title="Xóa">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            }

            function renderSinglePanel(item, itemIndex) {
                const children = item.children || [];
                return `
                    <div class="rounded border bg-light-subtle p-3 mt-3 js-single-panel ${item.dropdown_mode === 'single' ? '' : 'd-none'}">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <div class="fw-semibold text-dark small">Dropdown thường</div>
                                <div class="text-muted small">Danh sách một cột nằm dưới menu chính.</div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-action="add-child" data-item-index="${itemIndex}">
                                <i class="ti ti-plus me-1"></i> Thêm item
                            </button>
                        </div>
                        <div class="js-child-list">
                            ${children.length
                                ? children.map((child, childIndex) => linkRow(child, 'js-child-row', 'delete-child', `data-child-index="${childIndex}"`)).join('')
                                : '<div class="text-muted small py-2">Chưa có item dropdown.</div>'}
                        </div>
                    </div>
                `;
            }

            function renderMultiPanel(item, itemIndex) {
                const columns = item.columns || [];
                return `
                    <div class="rounded border bg-light-subtle p-3 mt-3 js-multi-panel ${item.dropdown_mode === 'multi' ? '' : 'd-none'}">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="fw-semibold text-dark small">Mega menu nhiều cột</div>
                                <div class="text-muted small">Mỗi cột có tiêu đề riêng và danh sách item bên dưới.</div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-action="add-column" data-item-index="${itemIndex}">
                                <i class="ti ti-columns-3 me-1"></i> Thêm cột
                            </button>
                        </div>
                        <div class="row g-3 js-column-list">
                            ${columns.length
                                ? columns.map((column, columnIndex) => `
                                    <div class="col-lg-4 js-column" data-column-index="${columnIndex}">
                                        <div class="bg-white border rounded p-3 h-100">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <input type="text" class="form-control form-control-sm fw-semibold js-column-title" value="${escapeHtml(column.title)}" placeholder="Tiêu đề cột">
                                                <button type="button" class="btn btn-light btn-sm text-danger" data-action="delete-column" title="Xóa cột">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                            <div class="js-column-item-list">
                                                ${(column.items || []).length
                                                    ? column.items.map((link, linkIndex) => linkRow(link, 'js-column-link-row', 'delete-column-item', `data-link-index="${linkIndex}"`)).join('')
                                                    : '<div class="text-muted small py-2">Chưa có item trong cột.</div>'}
                                            </div>
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-2" data-action="add-column-item">
                                                <i class="ti ti-plus me-1"></i> Thêm item trong cột
                                            </button>
                                        </div>
                                    </div>
                                `).join('')
                                : '<div class="col-12 text-muted small py-2">Chưa có cột mega menu.</div>'}
                        </div>
                    </div>
                `;
            }

            function render() {
                if (!builder) return;
                builder.innerHTML = menu.length
                    ? menu.map((item, itemIndex) => `
                        <div class="border rounded-3 p-3 bg-white js-navbar-item" data-item-index="${itemIndex}">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Tên menu</label>
                                    <input type="text" class="form-control form-control-sm js-item-label" value="${escapeHtml(item.label)}" placeholder="Sản phẩm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Link menu chính</label>
                                    <input type="text" class="form-control form-control-sm js-item-href" value="${escapeHtml(item.href)}" placeholder="/collection">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Badge</label>
                                    <input type="text" class="form-control form-control-sm js-item-badge" value="${escapeHtml(item.badge)}" placeholder="HOT">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Kiểu dropdown</label>
                                    <select class="form-select form-select-sm js-dropdown-mode">
                                        <option value="single" ${item.dropdown_mode !== 'multi' ? 'selected' : ''}>Single</option>
                                        <option value="multi" ${item.dropdown_mode === 'multi' ? 'selected' : ''}>Multi</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-center justify-content-end gap-2">
                                    <div class="form-check me-auto">
                                        <input class="form-check-input js-item-visible" type="checkbox" ${item.visible !== false ? 'checked' : ''}>
                                        <label class="form-check-label small">Hiện</label>
                                    </div>
                                    <button type="button" class="btn btn-light btn-sm" data-action="move-item-up" ${itemIndex === 0 ? 'disabled' : ''} title="Lên">
                                        <i class="ti ti-chevron-up"></i>
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm" data-action="move-item-down" ${itemIndex === menu.length - 1 ? 'disabled' : ''} title="Xuống">
                                        <i class="ti ti-chevron-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm text-danger" data-action="delete-item" title="Xóa menu">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                            ${renderSinglePanel(item, itemIndex)}
                            ${renderMultiPanel(item, itemIndex)}
                        </div>
                    `).join('')
                    : '<div class="text-center text-muted py-5 border rounded-3 bg-light">Chưa có menu nào. Bấm “Thêm menu” để bắt đầu.</div>';
                syncPayload();
            }

            function readLinkRow(row) {
                return {
                    label: row.querySelector('.js-link-label')?.value.trim() || '',
                    href: row.querySelector('.js-link-href')?.value.trim() || '#',
                    visible: row.querySelector('.js-link-visible')?.checked !== false,
                };
            }

            function syncFromDom() {
                if (!builder) return;
                menu = Array.from(builder.querySelectorAll('.js-navbar-item')).map(itemRow => {
                    const mode = itemRow.querySelector('.js-dropdown-mode')?.value === 'multi' ? 'multi' : 'single';
                    const children = Array.from(itemRow.querySelectorAll('.js-single-panel .js-child-row')).map(readLinkRow);
                    const columns = Array.from(itemRow.querySelectorAll('.js-multi-panel .js-column')).map(columnRow => ({
                        title: columnRow.querySelector('.js-column-title')?.value.trim() || '',
                        items: Array.from(columnRow.querySelectorAll('.js-column-link-row')).map(readLinkRow),
                    }));

                    return {
                        label: itemRow.querySelector('.js-item-label')?.value.trim() || '',
                        href: itemRow.querySelector('.js-item-href')?.value.trim() || '#',
                        badge: itemRow.querySelector('.js-item-badge')?.value.trim() || '',
                        visible: itemRow.querySelector('.js-item-visible')?.checked !== false,
                        dropdown_mode: mode,
                        children,
                        columns,
                    };
                });
                syncPayload();
            }

            addButton?.addEventListener('click', () => {
                syncFromDom();
                menu.push({
                    label: 'Menu mới',
                    href: '#',
                    badge: '',
                    visible: true,
                    dropdown_mode: 'single',
                    children: [],
                    columns: [],
                });
                render();
            });

            loadCurrentButton?.addEventListener('click', () => {
                menu = normalizeNavigationMenu(defaultNavigationMenu);
                render();
            });

            builder?.addEventListener('input', syncFromDom);
            builder?.addEventListener('change', (event) => {
                syncFromDom();
                if (event.target.classList.contains('js-dropdown-mode')) {
                    const itemRow = event.target.closest('.js-navbar-item');
                    const itemIndex = Number(itemRow?.dataset.itemIndex);
                    const item = menu[itemIndex];
                    if (item && item.dropdown_mode === 'multi' && item.columns.length === 0) {
                        item.columns = [{
                            title: item.label || 'Cột 1',
                            items: item.children.length ? [...item.children] : [emptyLink('Item mới')],
                        }];
                    }
                    if (item && item.dropdown_mode === 'single' && item.children.length === 0 && item.columns.length) {
                        item.children = item.columns.flatMap(column => column.items || []);
                    }
                    render();
                }
            });

            builder?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-action]');
                if (!button) return;
                event.preventDefault();
                syncFromDom();

                const itemRow = button.closest('.js-navbar-item');
                const itemIndex = Number(itemRow?.dataset.itemIndex);
                const item = menu[itemIndex];
                const action = button.dataset.action;

                if (action === 'delete-item') {
                    menu.splice(itemIndex, 1);
                } else if (action === 'move-item-up' && itemIndex > 0) {
                    [menu[itemIndex - 1], menu[itemIndex]] = [menu[itemIndex], menu[itemIndex - 1]];
                } else if (action === 'move-item-down' && itemIndex < menu.length - 1) {
                    [menu[itemIndex + 1], menu[itemIndex]] = [menu[itemIndex], menu[itemIndex + 1]];
                } else if (item && action === 'add-child') {
                    item.children.push(emptyLink());
                } else if (item && action === 'delete-child') {
                    const childRow = button.closest('.js-child-row');
                    item.children.splice(Number(childRow?.dataset.childIndex), 1);
                } else if (item && action === 'add-column') {
                    item.columns.push(emptyColumn(`Cột ${item.columns.length + 1}`));
                    item.dropdown_mode = 'multi';
                } else if (item && action === 'delete-column') {
                    const column = button.closest('.js-column');
                    item.columns.splice(Number(column?.dataset.columnIndex), 1);
                } else if (item && action === 'add-column-item') {
                    const column = button.closest('.js-column');
                    const columnIndex = Number(column?.dataset.columnIndex);
                    item.columns[columnIndex]?.items.push(emptyLink('Item mới'));
                } else if (item && action === 'delete-column-item') {
                    const column = button.closest('.js-column');
                    const linkRow = button.closest('.js-column-link-row');
                    item.columns[Number(column?.dataset.columnIndex)]?.items.splice(Number(linkRow?.dataset.linkIndex), 1);
                }

                render();
            });

            render();

            return {
                sync: syncFromDom,
            };
        }

        function generateWebhookToken() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let token = '';
            for (let i = 0; i < 32; i++) {
                token += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('ghtk_webhook_token').value = token;
        }

        function copyWebhookUrl() {
            const copyText = document.getElementById('ghtk_webhook_url');
            if (copyText) {
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(copyText.value).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('admin.settings.shipping.ghtk.copied') }}',
                        text: '{{ __('admin.settings.shipping.ghtk.copied_msg') }}',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                });
            }
        }

    </script>
@endpush
