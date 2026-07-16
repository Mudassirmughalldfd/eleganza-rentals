(() => {
  const gallery = document.querySelector('[data-car-gallery]');
  if (!gallery) return;

  const stage = gallery.querySelector('[data-gallery-stage]');
  const current = gallery.querySelector('[data-gallery-current]');
  const thumbs = [...gallery.querySelectorAll('.gallery-thumb')];
  const fullscreenButton = stage?.querySelector('[data-gallery-fullscreen]');
  let active = 0;

  const appendAutoplay = (src) => {
    if (!src) return src;
    return src + (src.includes('?') ? '&' : '?') + 'autoplay=1';
  };

  const createMedia = ({ type, src, poster, alt, title }, autoplay = false) => {
    if (type === 'youtube') {
      const iframe = document.createElement('iframe');
      iframe.src = autoplay ? appendAutoplay(src) : src;
      iframe.title = title || alt || 'Vehicle video';
      iframe.loading = 'lazy';
      iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
      iframe.allowFullscreen = true;
      return iframe;
    }

    if (type === 'video') {
      const video = document.createElement('video');
      video.src = src;
      video.controls = true;
      video.playsInline = true;
      video.poster = poster || '';
      video.preload = 'metadata';
      if (autoplay) {
        video.autoplay = true;
        video.muted = true;
      }
      return video;
    }

    const image = document.createElement('img');
    image.src = src;
    image.alt = alt || '';
    return image;
  };

  const render = (thumb, index) => {
    if (!stage || !thumb) return;
    const data = {
      type: thumb.dataset.type || 'image',
      src: thumb.dataset.src || '',
      poster: thumb.dataset.poster || '',
      alt: thumb.dataset.alt || '',
      title: thumb.dataset.title || ''
    };

    stage.querySelector('img, video, iframe')?.remove();
    stage.prepend(createMedia(data, data.type !== 'image'));
    thumbs.forEach(item => item.classList.remove('active'));
    thumb.classList.add('active');
    active = index;
    if (current) current.textContent = String(index + 1).padStart(2, '0');
    thumb.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
  };

  thumbs.forEach((thumb, index) => thumb.addEventListener('click', () => render(thumb, index)));

  const modal = document.querySelector('[data-gallery-modal]');
  const modalContent = modal?.querySelector('[data-gallery-modal-content]');

  const openModal = () => {
    if (!modal || !modalContent || !thumbs[active]) return;
    const thumb = thumbs[active];
    const data = {
      type: thumb.dataset.type || 'image',
      src: thumb.dataset.src || '',
      poster: thumb.dataset.poster || '',
      alt: thumb.dataset.alt || '',
      title: thumb.dataset.title || ''
    };
    modalContent.innerHTML = '';
    modalContent.appendChild(createMedia(data, data.type !== 'image'));
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeModal = () => {
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (modalContent) modalContent.innerHTML = '';
  };

  fullscreenButton?.addEventListener('click', openModal);
  modal?.querySelector('[data-gallery-close]')?.addEventListener('click', closeModal);
  modal?.addEventListener('click', event => { if (event.target === modal) closeModal(); });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeModal();
    if (!thumbs.length) return;
    if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
      const next = (active + 1) % thumbs.length;
      render(thumbs[next], next);
    }
    if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
      const previous = (active - 1 + thumbs.length) % thumbs.length;
      render(thumbs[previous], previous);
    }
  });
})();
