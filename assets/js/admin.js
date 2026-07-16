(() => {
  const sidebar = document.querySelector('[data-admin-sidebar]');
  document.querySelector('[data-admin-menu]')?.addEventListener('click', () => sidebar?.classList.toggle('open'));
  document.addEventListener('click', e => {
    const trigger = e.target.closest('[data-confirm]');
    if (trigger && !confirm(trigger.dataset.confirm || 'Are you sure?')) e.preventDefault();
  });
  document.querySelectorAll('[data-slug-source]').forEach(source => {
    const target = document.querySelector(source.dataset.slugSource);
    if (!target) return;
    source.addEventListener('input', () => {
      if (target.dataset.touched === '1') return;
      target.value = source.value.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    });
    target.addEventListener('input', () => target.dataset.touched = '1');
  });
})();

(() => {
  const range = document.querySelector('[data-datetime-range]');
  if (!range) return;

  const startDate = range.querySelector('[data-start-date]');
  const startTime = range.querySelector('[data-start-time]');
  const endDate = range.querySelector('[data-end-date]');
  const endTime = range.querySelector('[data-end-time]');
  const summary = range.querySelector('[data-datetime-summary]');

  const parseLocal = (dateInput, timeInput) => {
    if (!dateInput?.value || !timeInput?.value) return null;
    const value = new Date(`${dateInput.value}T${timeInput.value}:00`);
    return Number.isNaN(value.getTime()) ? null : value;
  };

  const pad = value => String(value).padStart(2, '0');
  const setInputDateTime = (dateInput, timeInput, value) => {
    dateInput.value = `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}`;
    timeInput.value = `${pad(value.getHours())}:${pad(value.getMinutes())}`;
  };

  const format = value => new Intl.DateTimeFormat('en-GB', {
    dateStyle: 'full',
    timeStyle: 'short'
  }).format(value);

  const update = changed => {
    if (startDate?.value && endDate) endDate.min = startDate.value;

    const start = parseLocal(startDate, startTime);
    let end = parseLocal(endDate, endTime);

    if (start && end && end <= start && changed && changed.matches('[data-start-date], [data-start-time]')) {
      end = new Date(start.getTime() + 60 * 60 * 1000);
      setInputDateTime(endDate, endTime, end);
    }

    if (!summary) return;

    if (start && end) {
      const durationMinutes = Math.max(0, Math.round((end - start) / 60000));
      const days = Math.floor(durationMinutes / 1440);
      const hours = Math.floor((durationMinutes % 1440) / 60);
      const minutes = durationMinutes % 60;
      const duration = [
        days ? `${days} day${days === 1 ? '' : 's'}` : '',
        hours ? `${hours} hour${hours === 1 ? '' : 's'}` : '',
        minutes ? `${minutes} minute${minutes === 1 ? '' : 's'}` : ''
      ].filter(Boolean).join(', ') || '0 minutes';

      summary.innerHTML = `<strong>Selected period:</strong> ${format(start)} → ${format(end)} <span class="admin-muted">(${duration})</span>`;
      summary.classList.add('visible');
    } else {
      summary.textContent = 'Please select both a date and a time for the start and expected return.';
      summary.classList.add('visible');
    }
  };

  [startDate, startTime, endDate, endTime].forEach(input => {
    input?.addEventListener('change', () => update(input));
    input?.addEventListener('input', () => update(input));
    input?.addEventListener('click', () => {
      if (typeof input.showPicker === 'function') {
        try { input.showPicker(); } catch (_) {}
      }
    });
  });

  range.addEventListener('submit', event => {
    const start = parseLocal(startDate, startTime);
    const end = parseLocal(endDate, endTime);

    if (!start || !end) {
      event.preventDefault();
      alert('Please select the start date, start time, return date and return time.');
      return;
    }

    if (end <= start) {
      event.preventDefault();
      alert('Expected return date and time must be after the unavailable date and time.');
      endDate?.focus();
    }
  });

  update(null);
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
