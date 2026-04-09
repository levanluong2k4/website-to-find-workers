const LIGHTBOX_ID = 'reviewMediaLightbox';
const boundRoots = new WeakSet();

let lightboxElements = null;
let activeTrigger = null;

const isElement = (value) => value instanceof Element;

const createMediaNode = (kind, src, label) => {
    if (kind === 'video') {
        const video = document.createElement('video');
        video.src = src;
        video.controls = true;
        video.playsInline = true;
        video.preload = 'metadata';
        video.setAttribute('aria-label', label || 'Video danh gia');
        return video;
    }

    const image = document.createElement('img');
    image.src = src;
    image.alt = label || 'Anh danh gia';
    image.loading = 'eager';
    return image;
};

const closeLightbox = () => {
    if (!lightboxElements) {
        return;
    }

    const { root, mediaSlot, caption } = lightboxElements;

    mediaSlot.querySelectorAll('video').forEach((video) => {
        try {
            video.pause();
        } catch (error) {
            console.warn('Could not pause review video preview', error);
        }
    });

    mediaSlot.innerHTML = '';
    caption.textContent = '';
    root.classList.remove('is-open');
    root.setAttribute('aria-hidden', 'true');
    root.hidden = true;
    document.body.classList.remove('review-lightbox-open');

    if (activeTrigger && typeof activeTrigger.focus === 'function' && document.contains(activeTrigger)) {
        activeTrigger.focus();
    }

    activeTrigger = null;
};

const handleDocumentKeydown = (event) => {
    if (event.key === 'Escape') {
        closeLightbox();
    }
};

const ensureLightbox = () => {
    if (lightboxElements) {
        return lightboxElements;
    }

    const root = document.createElement('div');
    root.id = LIGHTBOX_ID;
    root.className = 'review-lightbox';
    root.hidden = true;
    root.setAttribute('aria-hidden', 'true');
    root.innerHTML = `
        <div class="review-lightbox__backdrop" data-review-lightbox-close="true"></div>
        <div class="review-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Xem media danh gia">
            <button type="button" class="review-lightbox__close" data-review-lightbox-close="true" aria-label="Dong">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="review-lightbox__content">
                <div class="review-lightbox__media"></div>
                <div class="review-lightbox__caption"></div>
            </div>
        </div>
    `;

    document.body.appendChild(root);

    const mediaSlot = root.querySelector('.review-lightbox__media');
    const caption = root.querySelector('.review-lightbox__caption');

    root.querySelectorAll('[data-review-lightbox-close]').forEach((button) => {
        button.addEventListener('click', closeLightbox);
    });

    document.addEventListener('keydown', handleDocumentKeydown);

    lightboxElements = {
        root,
        mediaSlot,
        caption,
    };

    return lightboxElements;
};

const openLightbox = ({ kind, src, label, trigger }) => {
    if (!src) {
        return;
    }

    const { root, mediaSlot, caption } = ensureLightbox();

    mediaSlot.innerHTML = '';
    mediaSlot.appendChild(createMediaNode(kind, src, label));
    caption.textContent = label || (kind === 'video' ? 'Video danh gia' : 'Anh danh gia');
    activeTrigger = trigger || null;

    root.hidden = false;
    root.classList.add('is-open');
    root.setAttribute('aria-hidden', 'false');
    document.body.classList.add('review-lightbox-open');
};

const getMediaTrigger = (target) => {
    if (!isElement(target)) {
        return null;
    }

    if (target.closest('[data-review-media-ignore="true"]')) {
        return null;
    }

    return target.closest('[data-review-media-src]');
};

const openFromTrigger = (trigger) => {
    if (!trigger) {
        return;
    }

    openLightbox({
        kind: trigger.getAttribute('data-review-media-kind') || 'image',
        src: trigger.getAttribute('data-review-media-src') || '',
        label: trigger.getAttribute('data-review-media-label') || '',
        trigger,
    });
};

export function setupReviewLightbox(root = document) {
    if (!root || boundRoots.has(root)) {
        return;
    }

    ensureLightbox();

    root.addEventListener('click', (event) => {
        const trigger = getMediaTrigger(event.target);
        if (!trigger) {
            return;
        }

        if (trigger.matches('a[href]')) {
            event.preventDefault();
        }

        openFromTrigger(trigger);
    });

    root.addEventListener('keydown', (event) => {
        if (!['Enter', ' '].includes(event.key)) {
            return;
        }

        const trigger = getMediaTrigger(event.target);
        if (!trigger) {
            return;
        }

        event.preventDefault();
        openFromTrigger(trigger);
    });

    boundRoots.add(root);
}
