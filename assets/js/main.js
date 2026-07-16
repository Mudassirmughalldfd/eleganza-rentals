(() => {
  const header = document.querySelector('[data-header]');
  const progress = document.querySelector('.scroll-progress span');
  const onScroll = () => {
    const y = window.scrollY;
    header?.classList.toggle('scrolled', y > 45);
    if (progress) {
      const max = document.documentElement.scrollHeight - window.innerHeight;
      progress.style.width = `${max > 0 ? (y / max) * 100 : 0}%`;
    }
    document.querySelectorAll('[data-parallax]').forEach(el => {
      if (window.innerWidth > 900) el.style.backgroundPositionY = `calc(46% + ${y * .08}px)`;
    });
    document.querySelectorAll('[data-parallax-soft]').forEach(el => {
      const rect = el.parentElement.getBoundingClientRect();
      el.style.transform = `translateY(${rect.top * -.035}px) scale(1.08)`;
    });
  };
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();

  const menuButton = document.querySelector('[data-menu-button]');
  const mobileMenu = document.querySelector('[data-mobile-menu]');
  menuButton?.addEventListener('click', () => {
    const open = !mobileMenu.classList.contains('open');
    mobileMenu.classList.toggle('open', open); menuButton.classList.toggle('open', open);
    document.body.classList.toggle('menu-open', open); menuButton.setAttribute('aria-expanded', String(open));
  });
  mobileMenu?.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
    mobileMenu.classList.remove('open'); menuButton?.classList.remove('open'); document.body.classList.remove('menu-open');
  }));

  const observer = new IntersectionObserver(entries => entries.forEach(entry => {
    if (entry.isIntersecting) { entry.target.classList.add('in-view'); observer.unobserve(entry.target); }
  }), { threshold: .12, rootMargin: '0px 0px -40px' });
  document.querySelectorAll('.reveal').forEach((el, i) => { el.style.transitionDelay = `${Math.min((i % 4) * .08, .24)}s`; observer.observe(el); });

  if (matchMedia('(pointer:fine)').matches) {
    document.querySelectorAll('[data-tilt]').forEach(card => {
      card.addEventListener('mousemove', e => {
        const r = card.getBoundingClientRect(); const x = (e.clientX - r.left) / r.width - .5; const y = (e.clientY - r.top) / r.height - .5;
        card.style.transform = `perspective(900px) rotateX(${y * -2.2}deg) rotateY(${x * 2.2}deg) translateY(-4px)`;
      });
      card.addEventListener('mouseleave', () => card.style.transform = '');
    });
  }

  document.querySelectorAll('[data-countdown]').forEach(el => {
    const end = new Date(el.dataset.countdown).getTime();
    const tick = () => {
      const diff = end - Date.now();
      if (diff <= 0) { el.textContent = 'Expected return time has passed'; return; }
      const d = Math.floor(diff / 86400000), h = Math.floor(diff % 86400000 / 3600000), m = Math.floor(diff % 3600000 / 60000);
      el.textContent = `Returns in ${d} day${d===1?'':'s'}, ${h} hour${h===1?'':'s'}, ${m} minute${m===1?'':'s'}`;
    }; tick(); setInterval(tick, 60000);
  });
})();

/* Premium custom dropdowns used across public and admin forms. */
(() => {
  const SELECTOR = 'select:not([multiple]):not([size]):not([data-native-select])';
  let uid = 0;
  const enhanced = [];

  const closeAll = except => {
    enhanced.forEach(item => {
      if (item.wrapper === except) return;
      item.wrapper.classList.remove('is-open', 'drop-up');
      item.trigger.setAttribute('aria-expanded', 'false');
      item.options.forEach(option => option.classList.remove('is-focused'));
    });
  };

  const enhance = select => {
    if (select.dataset.eleganzaSelect === 'ready') return;
    select.dataset.eleganzaSelect = 'ready';

    const wrapper = document.createElement('div');
    wrapper.className = 'eleganza-select';
    const parent = select.parentNode;
    parent.insertBefore(wrapper, select);
    wrapper.appendChild(select);
    select.classList.add('native-select-hidden');
    select.tabIndex = -1;

    const id = `eleganza-select-${++uid}`;
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'eleganza-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', `${id}-menu`);

    const value = document.createElement('span');
    value.className = 'eleganza-select-value';
    const chevron = document.createElement('span');
    chevron.className = 'eleganza-select-chevron';
    chevron.setAttribute('aria-hidden', 'true');
    trigger.append(value, chevron);

    const menu = document.createElement('div');
    menu.className = 'eleganza-select-menu';
    menu.id = `${id}-menu`;
    menu.setAttribute('role', 'listbox');
    menu.setAttribute('aria-label', select.getAttribute('aria-label') || select.name || 'Select an option');

    const customOptions = Array.from(select.options).map((nativeOption, index) => {
      const option = document.createElement('button');
      option.type = 'button';
      option.className = 'eleganza-select-option';
      option.setAttribute('role', 'option');
      option.dataset.index = String(index);
      option.textContent = nativeOption.textContent.trim();
      option.disabled = nativeOption.disabled;
      option.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        if (nativeOption.disabled) return;
        select.selectedIndex = index;
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
        sync();
        closeAll();
        trigger.focus({ preventScroll: true });
      });
      menu.appendChild(option);
      return option;
    });

    wrapper.append(trigger, menu);

    const sync = () => {
      const chosen = select.options[select.selectedIndex] || select.options[0];
      value.textContent = chosen ? chosen.textContent.trim() : 'Select an option';
      wrapper.classList.toggle('eleganza-select-placeholder', !select.value);
      trigger.disabled = select.disabled;
      trigger.setAttribute('aria-disabled', String(select.disabled));
      customOptions.forEach((option, index) => {
        const selected = index === select.selectedIndex;
        option.classList.toggle('is-selected', selected);
        option.setAttribute('aria-selected', String(selected));
      });
      wrapper.classList.remove('has-error');
    };

    const open = () => {
      if (select.disabled) return;
      closeAll(wrapper);
      wrapper.classList.add('is-open');
      trigger.setAttribute('aria-expanded', 'true');
      wrapper.classList.remove('drop-up');
      requestAnimationFrame(() => {
        const rect = wrapper.getBoundingClientRect();
        const availableBelow = window.innerHeight - rect.bottom;
        const expectedHeight = Math.min(menu.scrollHeight, window.innerHeight * .46);
        wrapper.classList.toggle('drop-up', availableBelow < expectedHeight + 18 && rect.top > expectedHeight);
        const selected = customOptions[select.selectedIndex];
        selected?.scrollIntoView({ block: 'nearest' });
      });
    };

    const toggle = event => {
      event.preventDefault();
      event.stopPropagation();
      wrapper.classList.contains('is-open') ? closeAll() : open();
    };

    const focusOption = direction => {
      if (!wrapper.classList.contains('is-open')) open();
      const enabled = customOptions.filter(option => !option.disabled);
      if (!enabled.length) return;
      let current = enabled.findIndex(option => option.classList.contains('is-focused'));
      if (current < 0) current = enabled.findIndex(option => option.classList.contains('is-selected'));
      current = Math.max(0, current);
      if (direction === 'first') current = 0;
      else if (direction === 'last') current = enabled.length - 1;
      else current = (current + direction + enabled.length) % enabled.length;
      customOptions.forEach(option => option.classList.remove('is-focused'));
      enabled[current].classList.add('is-focused');
      enabled[current].scrollIntoView({ block: 'nearest' });
    };

    trigger.addEventListener('click', toggle);
    trigger.addEventListener('keydown', event => {
      if (event.key === 'ArrowDown') { event.preventDefault(); focusOption(1); }
      else if (event.key === 'ArrowUp') { event.preventDefault(); focusOption(-1); }
      else if (event.key === 'Home') { event.preventDefault(); focusOption('first'); }
      else if (event.key === 'End') { event.preventDefault(); focusOption('last'); }
      else if ((event.key === 'Enter' || event.key === ' ') && wrapper.classList.contains('is-open')) {
        event.preventDefault();
        const focused = customOptions.find(option => option.classList.contains('is-focused'));
        (focused || customOptions[select.selectedIndex])?.click();
      } else if (event.key === 'Enter' || event.key === ' ') toggle(event);
      else if (event.key === 'Escape') { closeAll(); trigger.focus({ preventScroll: true }); }
    });

    select.addEventListener('change', sync);
    select.addEventListener('invalid', event => {
      event.preventDefault();
      wrapper.classList.add('has-error');
      trigger.focus({ preventScroll: false });
    });
    select.form?.addEventListener('reset', () => setTimeout(sync, 0));

    sync();
    enhanced.push({ wrapper, trigger, options: customOptions, sync });
  };

  document.querySelectorAll(SELECTOR).forEach(enhance);
  document.addEventListener('click', event => {
    if (!event.target.closest('.eleganza-select')) closeAll();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeAll();
  });
  window.addEventListener('resize', () => closeAll(), { passive: true });
})();
