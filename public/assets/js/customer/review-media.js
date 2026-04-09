import { showToast } from '../api.js';

const DEFAULT_LIMITS = {
    maxImages: 5,
    maxVideoDuration: 20,
};

const escapeHtml = (value = '') => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const normalizeUrlList = (value) => {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .map((item) => String(item || '').trim())
        .filter(Boolean);
};

const getVideoDuration = async (file) => new Promise((resolve) => {
    const video = document.createElement('video');
    const objectUrl = URL.createObjectURL(file);

    video.preload = 'metadata';
    video.onloadedmetadata = () => {
        URL.revokeObjectURL(objectUrl);
        resolve(Number(video.duration) || 0);
    };
    video.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        resolve(0);
    };
    video.src = objectUrl;
});

export function createReviewMediaController({
    imageInput,
    videoInput,
    previewContainer,
    summaryElement,
    limits = {},
}) {
    const options = {
        ...DEFAULT_LIMITS,
        ...limits,
    };

    const state = {
        existingImages: [],
        existingVideo: '',
        newImages: [],
        newVideo: null,
        newVideoDuration: null,
        objectUrls: [],
    };

    const revokeObjectUrls = () => {
        state.objectUrls.forEach((url) => {
            try {
                URL.revokeObjectURL(url);
            } catch (error) {
                console.warn('Could not revoke object URL', error);
            }
        });

        state.objectUrls = [];
    };

    const syncInputs = () => {
        if (imageInput) {
            const transfer = new DataTransfer();
            state.newImages.forEach((file) => transfer.items.add(file));
            imageInput.files = transfer.files;
        }

        if (videoInput) {
            const transfer = new DataTransfer();
            if (state.newVideo) {
                transfer.items.add(state.newVideo);
            }
            videoInput.files = transfer.files;
        }
    };

    const updateSummary = () => {
        if (!summaryElement) {
            return;
        }

        const imageCount = state.existingImages.length + state.newImages.length;
        const videoCount = state.existingVideo || state.newVideo ? 1 : 0;
        summaryElement.textContent = `${imageCount}/${options.maxImages} anh • ${videoCount}/1 video`;
    };

    const buildPreviewItems = () => {
        const existingImageItems = state.existingImages.map((url, index) => ({
            key: `existing-image-${index}`,
            kind: 'image',
            source: 'existing',
            previewUrl: url,
            label: `Anh da luu ${index + 1}`,
            index,
        }));

        const newImageItems = state.newImages.map((file, index) => {
            const previewUrl = URL.createObjectURL(file);
            state.objectUrls.push(previewUrl);

            return {
                key: `new-image-${index}`,
                kind: 'image',
                source: 'new',
                previewUrl,
                label: file.name || `Anh moi ${index + 1}`,
                index,
            };
        });

        const videoItems = [];

        if (state.existingVideo) {
            videoItems.push({
                key: 'existing-video',
                kind: 'video',
                source: 'existing',
                previewUrl: state.existingVideo,
                label: 'Video da luu',
            });
        } else if (state.newVideo) {
            const previewUrl = URL.createObjectURL(state.newVideo);
            state.objectUrls.push(previewUrl);

            videoItems.push({
                key: 'new-video',
                kind: 'video',
                source: 'new',
                previewUrl,
                label: state.newVideo.name || 'Video moi',
            });
        }

        return [...existingImageItems, ...newImageItems, ...videoItems];
    };

    const render = () => {
        revokeObjectUrls();
        updateSummary();

        if (!previewContainer) {
            syncInputs();
            return;
        }

        const items = buildPreviewItems();

        if (!items.length) {
            previewContainer.innerHTML = `
                <div class="review-media-empty">
                    Chua co media. Ban co the them toi da ${options.maxImages} anh va 1 video.
                </div>
            `;
            syncInputs();
            return;
        }

        previewContainer.innerHTML = items.map((item) => {
            const mediaHtml = item.kind === 'video'
                ? `<video src="${escapeHtml(item.previewUrl)}" preload="metadata" muted playsinline></video>`
                : `<img src="${escapeHtml(item.previewUrl)}" alt="${escapeHtml(item.label)}">`;

            const removeAttrs = item.kind === 'video'
                ? `data-remove-video="${item.source}"`
                : `data-remove-image="${item.source}" data-remove-index="${item.index}"`;

            return `
                <div
                    class="review-media-item ${item.kind === 'video' ? 'review-media-item--video' : ''}"
                    data-review-media-kind="${item.kind}"
                    data-review-media-src="${escapeHtml(item.previewUrl)}"
                    data-review-media-label="${escapeHtml(item.label)}"
                    role="button"
                    tabindex="0"
                    aria-label="Xem ${escapeHtml(item.label)}"
                >
                    ${mediaHtml}
                    <button type="button" class="review-media-item__remove" ${removeAttrs} data-review-media-ignore="true" aria-label="Xoa media">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                    <span class="review-media-badge">
                        ${item.kind === 'video' ? 'Video' : (item.source === 'existing' ? 'Da luu' : 'Moi')}
                    </span>
                </div>
            `;
        }).join('');

        previewContainer.querySelectorAll('[data-remove-image]').forEach((button) => {
            button.addEventListener('click', () => {
                const source = button.getAttribute('data-remove-image') || 'new';
                const index = Number(button.getAttribute('data-remove-index') || -1);

                if (index < 0) {
                    return;
                }

                if (source === 'existing') {
                    state.existingImages.splice(index, 1);
                } else {
                    state.newImages.splice(index, 1);
                }

                render();
            });
        });

        previewContainer.querySelectorAll('[data-remove-video]').forEach((button) => {
            button.addEventListener('click', () => {
                const source = button.getAttribute('data-remove-video') || 'new';

                if (source === 'existing') {
                    state.existingVideo = '';
                } else {
                    state.newVideo = null;
                    state.newVideoDuration = null;
                }

                render();
            });
        });

        syncInputs();
    };

    const addImages = (files) => {
        const incomingFiles = Array.from(files || []).filter((file) => file?.type?.startsWith('image/'));
        if (!incomingFiles.length) {
            return;
        }

        const currentCount = state.existingImages.length + state.newImages.length;
        const availableSlots = Math.max(0, options.maxImages - currentCount);

        if (availableSlots <= 0) {
            showToast(`Toi da ${options.maxImages} anh cho moi danh gia.`, 'error');
            return;
        }

        if (incomingFiles.length > availableSlots) {
            showToast(`Chi co the them toi da ${availableSlots} anh nua.`, 'error');
        }

        state.newImages.push(...incomingFiles.slice(0, availableSlots));
        render();
    };

    const setVideo = async (file) => {
        if (!(file instanceof File) || !file.type.startsWith('video/')) {
            return;
        }

        const duration = await getVideoDuration(file);

        if (duration > options.maxVideoDuration) {
            showToast(`Video khong duoc vuot qua ${options.maxVideoDuration} giay.`, 'error');
            return;
        }

        state.newVideo = file;
        state.newVideoDuration = duration;
        state.existingVideo = '';
        render();
    };

    imageInput?.addEventListener('change', () => {
        addImages(imageInput.files);
        imageInput.value = '';
    });

    videoInput?.addEventListener('change', async () => {
        const nextVideo = videoInput.files?.[0] || null;

        if (nextVideo) {
            await setVideo(nextVideo);
        }

        videoInput.value = '';
    });

    return {
        reset(review = null) {
            revokeObjectUrls();
            state.existingImages = normalizeUrlList(review?.hinh_anh_danh_gia);
            state.existingVideo = String(review?.video_danh_gia || '').trim();
            state.newImages = [];
            state.newVideo = null;
            state.newVideoDuration = null;
            render();
        },
        appendToFormData(formData) {
            state.existingImages.forEach((url) => {
                formData.append('existing_hinh_anh_danh_gia[]', url);
            });

            state.newImages.forEach((file) => {
                formData.append('hinh_anh_danh_gia[]', file);
            });

            if (state.newVideo) {
                formData.append('video_danh_gia', state.newVideo);
            }

            if (state.newVideoDuration !== null) {
                formData.append('video_duration', String(state.newVideoDuration));
            }

            formData.append('keep_existing_video', state.existingVideo ? '1' : '0');
        },
        getMediaSummaryLabel() {
            const imageCount = state.existingImages.length + state.newImages.length;
            const hasVideo = Boolean(state.existingVideo || state.newVideo);

            if (!imageCount && !hasVideo) {
                return '';
            }

            return [imageCount ? `${imageCount} anh` : '', hasVideo ? '1 video' : '']
                .filter(Boolean)
                .join(' • ');
        },
    };
}
