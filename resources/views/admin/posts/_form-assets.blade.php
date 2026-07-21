@push('scripts')
    <script src="{{ asset('admin-assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('admin-assets/libs/quill/dist/quill.min.js') }}"></script>
    <script src="{{ asset('admin-assets/libs/select2/dist/js/select2.full.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form.admin-form-with-sticky-actions');
            if (!form) return;

            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            const summaryInput = document.getElementById('summary');
            const contentInput = document.getElementById('content_input');
            const seoKeysInput = document.getElementById('seo_keys');
            const seoTitleInput = document.getElementById('seo_title');
            const seoDescInput = document.getElementById('seo_description');
            const categoryInput = document.getElementById('category_id');
            const statusInput = document.getElementById('is_active');
            const fileInput = document.getElementById('post_image_file');
            const previewImg = document.getElementById('post_image_preview');
            const placeholder = document.getElementById('post_image_placeholder');
            const rulesContainer = document.getElementById('seo_rules');
            let quill = null;
            let debounceTimer = null;
            let analysisRequest = null;
            let lastAnalysis = null;
            let approvedSubmission = false;
            let imageWidth = 0;
            let imageHeight = 0;

            if (window.jQuery && jQuery.fn.select2) {
                $('.select2-select').select2({ minimumResultsForSearch: 5 });
            }

            function generateSlug(text) {
                return String(text || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[đĐ]/g, 'd')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            if (titleInput && slugInput) {
                titleInput.addEventListener('input', function () {
                    if (!slugInput.dataset.manual) {
                        slugInput.value = generateSlug(titleInput.value);
                    }
                    queueAnalysis();
                });
                slugInput.addEventListener('input', function () {
                    slugInput.dataset.manual = 'true';
                    queueAnalysis();
                });
            }

            const editorElement = document.getElementById('content_editor');
            if (editorElement && contentInput) {
                quill = new Quill(editorElement, {
                    theme: 'snow',
                    modules: {
                        toolbar: {
                            container: [
                                [{ 'header': [2, 3, false] }],
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                                ['link', 'image', 'clean']
                            ],
                            handlers: {
                                image: function () {
                                    const url = window.prompt('Nhập URL ảnh HTTPS:');
                                    if (!url) return;
                                    const alt = window.prompt('Nhập mô tả alt chính xác cho ảnh (bắt buộc):');
                                    if (!alt || !alt.trim()) {
                                        Swal.fire({ icon: 'warning', title: 'Thiếu mô tả ảnh', text: 'Ảnh trong bài viết bắt buộc phải có alt mô tả.' });
                                        return;
                                    }
                                    const range = this.quill.getSelection(true);
                                    this.quill.insertEmbed(range.index, 'image', url, 'user');
                                    this.quill.setSelection(range.index + 1, 0, 'silent');
                                    window.requestAnimationFrame(() => {
                                        const images = [...this.quill.root.querySelectorAll('img')];
                                        const image = images.reverse().find(item => item.getAttribute('src') === url);
                                        if (image) image.setAttribute('alt', alt.trim());
                                        syncEditor();
                                        queueAnalysis();
                                    });
                                }
                            }
                        }
                    }
                });
                quill.on('text-change', function () {
                    syncEditor();
                    queueAnalysis();
                });
            }

            function syncEditor() {
                if (quill && contentInput) contentInput.value = quill.root.innerHTML;
            }

            if (fileInput && previewImg) {
                fileInput.addEventListener('change', function (event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    const previewUrl = URL.createObjectURL(file);
                    previewImg.onload = function () {
                        imageWidth = previewImg.naturalWidth || 0;
                        imageHeight = previewImg.naturalHeight || 0;
                        URL.revokeObjectURL(previewUrl);
                        queueAnalysis();
                    };
                    previewImg.src = previewUrl;
                    previewImg.classList.remove('d-none');
                    if (placeholder) placeholder.classList.add('d-none');
                });
            }

            function escapeHtml(value) {
                const element = document.createElement('div');
                element.textContent = String(value ?? '');
                return element.innerHTML;
            }

            function updateCounter(input, outputId) {
                const output = document.getElementById(outputId);
                if (output) output.textContent = String((input?.value || '').trim().length);
            }

            function updateCounters() {
                updateCounter(summaryInput, 'summary_count');
                updateCounter(seoTitleInput, 'seo_title_count');
                updateCounter(seoDescInput, 'seo_description_count');
            }

            function payload() {
                syncEditor();
                return {
                    post_id: form.dataset.postId || null,
                    title: titleInput?.value || '',
                    slug: slugInput?.value || '',
                    category_id: categoryInput?.value || null,
                    summary: summaryInput?.value || '',
                    content: contentInput?.value || '',
                    seo_title: seoTitleInput?.value || '',
                    seo_description: seoDescInput?.value || '',
                    seo_keys: seoKeysInput?.value || '',
                    has_featured_image: form.dataset.hasFeaturedImage === '1' || Boolean(fileInput?.files?.length),
                    featured_image_width: imageWidth,
                    featured_image_height: imageHeight
                };
            }

            function renderAnalysis(analysis) {
                lastAnalysis = analysis;
                const strictModeEnabled = analysis.strict_mode_enabled !== false;
                form.dataset.seoStrictMode = strictModeEnabled ? '1' : '0';
                const score = Number(analysis.score || 0);
                const circle = document.getElementById('seo_progress_circle');
                const scoreText = document.getElementById('seo_score_txt');
                const rating = document.getElementById('seo_rating_label');
                const badge = document.getElementById('seo_overall_badge');
                const hint = document.getElementById('publish_gate_hint');
                const failedCount = Array.isArray(analysis.failed_rules) ? analysis.failed_rules.length : 0;

                if (scoreText) scoreText.textContent = String(score);
                if (circle) {
                    const circumference = 2 * Math.PI * circle.r.baseVal.value;
                    circle.style.strokeDashoffset = String(circumference - (score / 100) * circumference);
                    circle.style.stroke = analysis.ready_to_publish ? '#22c55e' : (score >= 75 ? '#f97316' : '#ef4444');
                }
                if (rating) {
                    rating.textContent = !strictModeEnabled
                        ? `Chế độ khuyến nghị · còn ${failedCount} tiêu chí`
                        : (analysis.ready_to_publish ? 'Sẵn sàng xuất bản' : `Còn ${failedCount} tiêu chí bắt buộc`);
                    rating.style.color = !strictModeEnabled ? '#0284c7' : (analysis.ready_to_publish ? '#22c55e' : (score >= 75 ? '#f97316' : '#ef4444'));
                }
                if (badge) {
                    badge.textContent = !strictModeEnabled ? 'KHÔNG CHẶN' : (analysis.ready_to_publish ? 'ĐƯỢC XUẤT BẢN' : 'ĐANG KHÓA');
                    badge.className = `badge ${!strictModeEnabled ? 'bg-info' : (analysis.ready_to_publish ? 'bg-success' : 'bg-danger')} text-white fw-bold px-2 py-1 fs-1`;
                }
                if (hint) {
                    hint.textContent = !strictModeEnabled
                        ? `Mode nghiêm khắc đang tắt: còn ${failedCount} tiêu chí chưa đạt nhưng bạn vẫn có thể xuất bản.`
                        : (analysis.ready_to_publish
                            ? 'SEO Gate đã đạt 100%. Bài viết có thể xuất bản.'
                            : `SEO Gate đang khóa xuất bản vì còn ${failedCount} tiêu chí chưa đạt. Bạn vẫn có thể lưu nháp.`);
                }
                if (rulesContainer) {
                    rulesContainer.innerHTML = (analysis.rules || []).map(rule => `
                        <div class="seo-rule-item" id="rule_${escapeHtml(rule.key)}">
                            <span class="seo-status-dot ${rule.passed ? 'seo-status-green' : 'seo-status-red'}"></span>
                            <span class="seo-rule-copy">
                                <strong>${escapeHtml(rule.label)}</strong>
                                <small>${escapeHtml(rule.detail)}</small>
                            </span>
                        </div>
                    `).join('');
                }
            }

            async function analyzeNow() {
                updateCounters();
                if (!form.dataset.seoAnalyzeUrl) return null;
                if (analysisRequest) analysisRequest.abort();
                const requestController = new AbortController();
                analysisRequest = requestController;

                try {
                    const response = await fetch(form.dataset.seoAnalyzeUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload()),
                        signal: requestController.signal
                    });
                    if (!response.ok) throw new Error(`SEO analyzer HTTP ${response.status}`);
                    const analysis = await response.json();
                    renderAnalysis(analysis);
                    return analysis;
                } catch (error) {
                    if (error.name === 'AbortError') return null;
                    console.warn('[SEO Gate] Analyzer failed', error);
                    if (rulesContainer) {
                        rulesContainer.innerHTML = '<div class="alert alert-danger py-2 mb-0">Không kết nối được bộ phân tích. Xuất bản sẽ bị khóa an toàn; hãy thử lại hoặc lưu nháp.</div>';
                    }
                    return null;
                } finally {
                    if (analysisRequest === requestController) analysisRequest = null;
                }
            }

            function queueAnalysis() {
                updateCounters();
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(analyzeNow, 450);
            }

            [summaryInput, seoKeysInput, seoTitleInput, seoDescInput].forEach(input => {
                input?.addEventListener('input', queueAnalysis);
            });
            categoryInput?.addEventListener('change', queueAnalysis);
            statusInput?.addEventListener('change', queueAnalysis);
            if (window.jQuery && categoryInput) $(categoryInput).on('select2:select select2:clear', queueAnalysis);

            form.addEventListener('submit', async function (event) {
                syncEditor();
                if (approvedSubmission || statusInput?.value !== '1' || form.dataset.seoStrictMode === '0') return;

                event.preventDefault();
                const submitter = event.submitter;
                const analysis = await analyzeNow();
                if (analysis?.strict_mode_enabled !== false && !analysis?.ready_to_publish) {
                    const failures = (analysis?.failed_rules || []).slice(0, 8)
                        .map(rule => `<li class="text-start mb-1">${escapeHtml(rule.label)}</li>`)
                        .join('');
                    Swal.fire({
                        icon: 'error',
                        title: 'SEO Gate đang khóa xuất bản',
                        html: failures ? `<p>Bài viết phải đạt 100 điểm. Các lỗi đầu tiên:</p><ul>${failures}</ul>` : 'Không thể xác minh đủ tiêu chí. Hãy thử lại hoặc chuyển sang Lưu nháp.',
                        confirmButtonText: 'Tiếp tục chỉnh sửa'
                    });
                    return;
                }

                approvedSubmission = true;
                if (typeof form.requestSubmit === 'function') form.requestSubmit(submitter || undefined);
                else form.submit();
            });

            updateCounters();
            window.setTimeout(analyzeNow, 250);
        });
    </script>
@endpush
