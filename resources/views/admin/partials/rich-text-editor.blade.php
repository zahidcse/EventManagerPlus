@once
@push('styles')
<style>
    /* Keep TinyMCE popovers above admin chrome; avoid clipping in cards */
    .tox-tinymce-aux {
        z-index: 10050 !important;
    }
    .faq-answer-editor .tox-tinymce {
        border-radius: 0.5rem;
    }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.0/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    var CDN = 'https://cdn.jsdelivr.net/npm/tinymce@7.6.0';
    var uploadUrl = @json(route('admin.editor.upload'));

    window.adminRichTextInit = function (el) {
        if (!window.tinymce || !el || el.getAttribute('data-rich-init') === '1') {
            return;
        }
        el.setAttribute('data-rich-init', '1');
        if (!el.id) {
            el.id = 'rte-' + Math.random().toString(36).slice(2, 11);
        }

        var tokenEl = document.querySelector('meta[name="csrf-token"]');
        var csrf = tokenEl ? tokenEl.getAttribute('content') : '';

        var heightAttr = el.getAttribute('data-rich-height');
        var editorHeight = parseInt(heightAttr || '320', 10);
        if (!isFinite(editorHeight) || editorHeight < 200) {
            editorHeight = 320;
        }

        var toolbar = el.getAttribute('data-rich-toolbar');
        if (!toolbar) {
            toolbar = 'undo redo | blocks | bold italic underline strikethrough | link image | bullist numlist | removeformat | code';
        }

        var uiMode = el.getAttribute('data-rich-ui-mode') || 'split';

        tinymce.init({
            license_key: 'gpl',
            promotion: false,
            branding: false,
            base_url: CDN,
            suffix: '.min',
            target: el,
            height: editorHeight,
            menubar: false,
            plugins: 'link lists code image',
            toolbar: toolbar,
            toolbar_mode: 'wrap',
            ui_mode: uiMode,
            block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3',
            setup: function (editor) {
                editor.on('init', function () {
                    var onDocMouseDown = function (e) {
                        var container = editor.getContainer();
                        if (!container) {
                            return;
                        }
                        var target = e.target;
                        if (container.contains(target)) {
                            return;
                        }
                        var aux = document.querySelector('.tox-tinymce-aux');
                        if (aux && aux.contains(target)) {
                            return;
                        }
                        editor.fire('blur');
                        if (editor.windowManager) {
                            editor.windowManager.close();
                        }
                    };
                    document.addEventListener('mousedown', onDocMouseDown, true);
                    editor.on('remove', function () {
                        document.removeEventListener('mousedown', onDocMouseDown, true);
                    });
                });
            },
            content_style: 'body{font-family:Inter,system-ui,sans-serif;font-size:14px;line-height:1.5} img{max-width:100%;height:auto}',
            // Keep upload URLs intact (relative rewriting breaks /uploads/... on subpath installs & after reload).
            relative_urls: false,
            remove_script_host: false,
            convert_urls: false,
            paste_data_images: true,
            automatic_uploads: true,
            extended_valid_elements: 'figure[class],figcaption[class],img[src|alt|title|width|height|class|style|loading|decoding|srcset|sizes]',
            images_file_types: 'jpeg,jpg,jpe,jfi,jif,jfif,png,gif,bmp,webp',
            images_upload_handler: function (blobInfo, progress) {
                return new Promise(function (resolve, reject) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadUrl);
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.onload = function () {
                        if (xhr.status < 200 || xhr.status >= 300) {
                            try {
                                var j = JSON.parse(xhr.responseText);
                                reject(j.error || j.message || 'Upload failed');
                            } catch (e) {
                                reject('Upload failed');
                            }
                            return;
                        }
                        try {
                            var json = JSON.parse(xhr.responseText);
                            if (!json.location) {
                                reject('Invalid server response');
                                return;
                            }
                            resolve(json.location);
                        } catch (e) {
                            reject('Invalid server response');
                        }
                    };
                    xhr.onerror = function () {
                        reject('Network error');
                    };
                    var fd = new FormData();
                    fd.append('file', blobInfo.blob(), blobInfo.filename());
                    xhr.send(fd);
                });
            },
        });
    };

    window.adminRichTextRemove = function (el) {
        if (!window.tinymce || !el) {
            return;
        }
        var ed = tinymce.get(el.id);
        if (ed) {
            ed.remove();
        }
        el.removeAttribute('data-rich-init');
    };
})();
</script>
@endpush
@endonce
