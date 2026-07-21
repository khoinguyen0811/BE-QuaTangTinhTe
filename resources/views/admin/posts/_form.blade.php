@php
    $fallbackLocale = config('app.fallback_locale', config('app.locale', 'vi'));
    $title = old('title', $post->getTranslation('title', app()->getLocale(), false) ?: $post->getTranslation('title', $fallbackLocale, false));
    $summary = old('summary', $post->getTranslation('summary', app()->getLocale(), false) ?: $post->getTranslation('summary', $fallbackLocale, false));
    $content = old('content', $post->getTranslation('content', app()->getLocale(), false) ?: $post->getTranslation('content', $fallbackLocale, false));
    $seoTitle = old('seo_title', $post->getTranslation('seo_title', app()->getLocale(), false) ?: $post->getTranslation('seo_title', $fallbackLocale, false));
    $seoDesc = old('seo_description', $post->getTranslation('seo_description', app()->getLocale(), false) ?: $post->getTranslation('seo_description', $fallbackLocale, false));
    $publishStatus = (string) old('is_active', $post->exists && $post->is_active ? '1' : '0');
    $cancelUrl = route('admin.posts.index');
@endphp

<div class="row">
    <!-- Left column: Main Content and SEO -->
    <div class="col-lg-8">
        @if ($errors->has('seo_gate'))
            <div class="alert alert-danger" role="alert" aria-labelledby="seo-gate-error-title">
                <h5 id="seo-gate-error-title" class="alert-heading fw-bold">Chưa thể xuất bản: SEO Gate chưa đạt 100%</h5>
                <p>Không có dữ liệu nào bị mất. Bạn có thể sửa các mục dưới đây hoặc chuyển trạng thái sang <strong>Lưu nháp</strong>.</p>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->get('seo_gate') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="alert {{ $seoStrictMode ? 'alert-warning' : 'alert-info' }} d-flex gap-3 align-items-start" role="status">
            <iconify-icon icon="solar:shield-check-bold-duotone" class="fs-7 flex-shrink-0"></iconify-icon>
            <div>
                <strong>Chế độ SEO Gate nghiêm ngặt đang {{ $seoStrictMode ? 'bật' : 'tắt' }}.</strong>
                @if ($seoStrictMode)
                    Bản nháp luôn lưu được, nhưng chỉ bài đạt toàn bộ checklist và 100 điểm mới được xuất bản.
                @else
                    Hệ thống vẫn chấm điểm và cảnh báo, nhưng không chặn bạn xuất bản bài viết.
                @endif
                Hệ thống ưu tiên nội dung hữu ích, có nguồn và cấu trúc rõ ràng; không khuyến khích nhồi từ khóa.
            </div>
        </div>

        <!-- General Info Card -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-7">{{ __('admin.posts.sections.general') }}</h4>
                
                <div class="mb-4">
                    <label class="form-label" for="title">{{ __('admin.posts.fields.title') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ $title }}" placeholder="{{ __('admin.posts.placeholders.title') }}" required>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="slug">{{ __('admin.posts.fields.slug') }}</label>
                    <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $post->slug) }}" placeholder="{{ __('admin.posts.placeholders.slug') }}">
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between gap-3">
                        <label class="form-label" for="summary">{{ __('admin.posts.fields.summary') }} <span class="text-danger">*</span></label>
                        <small class="text-muted"><span id="summary_count">0</span>/300</small>
                    </div>
                    <textarea class="form-control" id="summary" name="summary" rows="4" maxlength="300" placeholder="Viết câu trả lời trực tiếp 120–300 ký tự để người đọc và công cụ AI hiểu ngay nội dung chính.">{{ $summary }}</textarea>
                    <p class="fs-2 text-muted mb-0">Phải có cụm từ khóa tự nhiên và trả lời thẳng vấn đề, không mở đầu vòng vo.</p>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="content_input">{{ __('admin.posts.fields.content') }} <span class="text-danger">*</span></label>
                    <textarea class="form-control d-none" id="content_input" name="content">{{ $content }}</textarea>
                    <div id="content_editor" class="catalog-quill" data-target="content_input" style="height: 350px;">{!! $content !!}</div>
                </div>
            </div>
        </div>

        <!-- SEO Card -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-7">{{ __('admin.posts.sections.seo') }}</h4>
                
                <div class="mb-4">
                    <label class="form-label" for="seo_keys">{{ __('admin.posts.fields.seo_keys') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="seo_keys" name="seo_keys" value="{{ old('seo_keys', $post->seo_keys) }}" placeholder="{{ __('admin.posts.placeholders.focus_keyword') }}">
                    <p class="fs-2 text-muted mb-0">Chỉ nhập một cụm 2–6 từ. Trường này dùng để kiểm tra biên tập, không xuất thành thẻ meta keywords.</p>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between gap-3">
                        <label class="form-label" for="seo_title">{{ __('admin.posts.fields.seo_title') }} <span class="text-danger">*</span></label>
                        <small class="text-muted"><span id="seo_title_count">0</span>/65</small>
                    </div>
                    <input type="text" class="form-control" id="seo_title" name="seo_title" value="{{ $seoTitle }}" maxlength="65" placeholder="Tiêu đề cuối cùng xuất ra thẻ title, 35–65 ký tự.">
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between gap-3">
                        <label class="form-label" for="seo_description">{{ __('admin.posts.fields.seo_description') }} <span class="text-danger">*</span></label>
                        <small class="text-muted"><span id="seo_description_count">0</span>/160</small>
                    </div>
                    <textarea class="form-control" id="seo_description" name="seo_description" rows="4" maxlength="160" placeholder="Mô tả riêng 120–160 ký tự, nêu rõ lợi ích và chủ đề bài viết.">{{ $seoDesc }}</textarea>
                </div>
            </div>
        </div>

        @include('admin.shared.form-actions', ['cancelUrl' => $cancelUrl])
    </div>

    <!-- Right column: Sidebar settings & SEO Analyzer Widget -->
    <div class="col-lg-4">
        <!-- Thumbnail Card -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-7">{{ __('admin.posts.sections.thumbnail') }}</h4>
                
                <!-- Hidden file input for image select -->
                <input type="file" name="image_file" id="post_image_file" class="d-none" accept="image/*">
                
                <!-- Styled image preview area -->
                <div id="post_image_preview_container" class="position-relative text-center border border-2 border-dashed rounded p-3 mb-3 cursor-pointer d-flex flex-column align-items-center justify-content-center bg-light" 
                     style="min-height: 180px; cursor: pointer; border-style: dashed !important;" 
                     onclick="document.getElementById('post_image_file').click()">
                     
                    <img id="post_image_preview" src="{{ $post->image_url ?: '#' }}" 
                         alt="Xem trước ảnh đại diện bài viết"
                         class="img-fluid rounded {{ $post->image_url ? '' : 'd-none' }}" 
                         style="max-height: 150px; object-fit: contain;">
                     
                    <div id="post_image_placeholder" class="text-center py-3 {{ $post->image_url ? 'd-none' : '' }}">
                        <iconify-icon icon="solar:camera-add-bold-duotone" class="fs-10 text-muted mb-2"></iconify-icon>
                        <div class="text-muted small">{{ __('admin.posts.placeholders.image_help') }}</div>
                    </div>
                </div>
                <p class="fs-2 text-center mb-0">JPG, PNG hoặc WEBP tối đa 4 MB; ảnh tải mới bắt buộc tối thiểu <strong>1200×630 px</strong>.</p>
            </div>
        </div>

        <!-- Status Card -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-7">
                    <h4 class="card-title">{{ __('admin.posts.sections.publish') }}</h4>
                    <div class="p-2 h-100 {{ old('is_active', $post->is_active) ? 'bg-success' : 'bg-danger' }} rounded-circle"></div>
                </div>
                <select class="form-select mb-2" name="is_active" id="is_active">
                    <option value="1" @selected($publishStatus === '1')>{{ __('admin.posts.fields.active') }}</option>
                    <option value="0" @selected($publishStatus === '0')>{{ __('admin.posts.fields.inactive') }}</option>
                </select>
                <p class="fs-2 mb-0" id="publish_gate_hint">
                    {{ $seoStrictMode
                        ? 'Lưu nháp không bị chặn. Xuất bản yêu cầu SEO Gate đạt đủ 100 điểm.'
                        : 'Mode nghiêm khắc đang tắt. Điểm SEO chỉ mang tính cảnh báo và không chặn xuất bản.' }}
                </p>
            </div>
        </div>

        <!-- Category Card -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-7">{{ __('admin.posts.sections.category') }}</h4>
                <div class="mb-3">
                    <select name="category_id" id="category_id" class="catalog-select2 form-control select2-select">
                        <option value="">{{ __('admin.posts.uncategorized') }}</option>
                        @foreach($categories as $category)
                            @php
                                $catName = $category->getTranslation('name', app()->getLocale(), false) ?: $category->getTranslation('name', $fallbackLocale, false);
                            @endphp
                            <option value="{{ $category->id }}" @selected(old('category_id', $post->category_id) == $category->id)>
                                {!! str_repeat('&nbsp;&nbsp;', $category->depth ?? 0) !!}{{ $category->depth ? '↳ ' : '' }}{{ $catName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <p class="fs-2 mb-0">{{ __('admin.posts.placeholders.category_help') }}</p>
            </div>
        </div>

        <!-- Live SEO Analyzer Widget -->
        <style>
            .seo-analyzer-card {
                max-height: 647px;
                overflow: auto;
                scrollbar-width: none; /* Firefox */
                -ms-overflow-style: none; /* IE 10+ */
            }
            .seo-analyzer-card::-webkit-scrollbar {
                display: none; /* Safari and Chrome */
            }
        </style>
        <div class="card seo-analyzer-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3 border-bottom pb-2">
                    <h4 class="card-title mb-0 text-success d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:ranking-bold-duotone" class="fs-6"></iconify-icon>{{ __('admin.posts.sections.seo_analysis') }}
                    </h4>
                    <span class="badge bg-danger text-white fw-bold px-2 py-1 fs-1" id="seo_overall_badge">ĐANG KHÓA</span>
                </div>

                <div class="text-center my-4 d-flex flex-column align-items-center justify-content-center">
                    <div class="position-relative d-inline-flex">
                        <svg class="seo-progress-ring" width="100" height="100">
                            <circle class="text-light-subtle" stroke="#e2e8f0" stroke-width="8" fill="transparent" r="40" cx="50" cy="50"/>
                            <circle class="seo-progress-ring-circle" id="seo_progress_circle" stroke="#ef4444" stroke-width="8" stroke-dasharray="251.2" stroke-dashoffset="251.2" fill="transparent" r="40" cx="50" cy="50"/>
                        </svg>
                        <div class="position-absolute top-50 start-50 translate-middle text-center">
                            <span class="fs-6 fw-bold text-dark" id="seo_score_txt">0</span><span class="fs-3 text-muted">/100</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="fs-3 fw-bold" id="seo_rating_label" style="color: #ef4444;">{{ __('admin.posts.seo_widget.rating_too_short') }}</span>
                    </div>
                </div>

                <div class="seo-checklist">
                    <h6 class="fs-2 fw-bold text-muted text-uppercase mb-3">{{ __('admin.posts.sections.seo_results') }}</h6>
                    <div id="seo_rules" aria-live="polite">
                        <div class="seo-rule-item">
                            <span class="seo-status-dot seo-status-orange"></span>
                            <span>Đang gửi dữ liệu tới bộ phân tích SEO phía máy chủ…</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
