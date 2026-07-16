(() => {
  const parseBookingData = () => {
    const node = document.getElementById('booking-availability-data');
    if (!node) return {};
    try { return JSON.parse(node.textContent || '{}'); } catch (_) { return {}; }
  };

  const dateLabel = value => {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('en-GB', {
      day: 'numeric', month: 'long', year: 'numeric', hour: 'numeric', minute: '2-digit'
    }).format(date);
  };

  const initialiseBookingAvailability = form => {
    const cars = parseBookingData();
    const vehicle = form.querySelector('[data-booking-vehicle]');
    const start = form.querySelector('[data-booking-start]');
    const end = form.querySelector('[data-booking-end]');
    const notice = form.querySelector('[data-availability-notice]');
    const submit = form.querySelector('[data-booking-submit]');
    if (!vehicle || !start || !end || !notice || !submit) return () => {};

    const setNotice = (type, title, text, blocked = false) => {
      notice.className = `availability-notice ${type}`;
      notice.innerHTML = `<span class="availability-icon">${type === 'success' ? '✓' : type === 'warning' ? '!' : 'i'}</span><div><strong>${title}</strong><p>${text}</p></div>`;
      submit.disabled = blocked;
      submit.classList.toggle('is-blocked', blocked);
    };

    const update = () => {
      const car = cars[vehicle.value];
      if (start.value) end.min = start.value;
      if (start.value && end.value && end.value < start.value) end.value = start.value;

      if (!car) {
        setNotice('neutral', 'Select a vehicle to check availability.', 'Then choose your preferred start and end dates.');
        return;
      }

      const current = car.status || {};
      const hasDates = Boolean(start.value && end.value);

      if (!hasDates) {
        if (current.available) {
          setNotice('success', `${car.name} is available now.`, 'Choose your dates to check them against scheduled hires.');
        } else if (current.show_return && current.return_at) {
          setNotice('warning', `${car.name} is ${String(current.label || 'currently unavailable').toLowerCase()}.`, `Expected back ${dateLabel(current.return_at)}. Select dates after this time to continue.`);
        } else {
          setNotice('warning', `${car.name} is ${String(current.label || 'currently unavailable').toLowerCase()}.`, current.public_note || 'Choose your dates to check future availability, or contact us for help.');
        }
        return;
      }

      const selectedStart = new Date(`${start.value}T00:00:00`);
      const selectedEndExclusive = new Date(`${end.value}T00:00:00`);
      selectedEndExclusive.setDate(selectedEndExclusive.getDate() + 1);

      const conflict = (car.blocks || []).find(block => {
        const blockStart = new Date(block.start_at);
        const blockEnd = new Date(block.end_at);
        return selectedStart < blockEnd && selectedEndExclusive > blockStart;
      });

      if (conflict) {
        const returnText = conflict.show_return && conflict.end_at
          ? ` It is expected back ${dateLabel(conflict.end_at)}.`
          : '';
        const publicText = conflict.public_note ? ` ${conflict.public_note}` : '';
        setNotice(
          'warning',
          `${car.name} is unavailable for the selected dates.`,
          `${conflict.label || 'The vehicle already has an unavailable period during this range.'}.${returnText}${publicText} Please choose different dates.`,
          true
        );
        return;
      }

      if (!current.available && !(car.blocks || []).length) {
        setNotice('warning', `${car.name} is not currently accepting booking requests.`, current.public_note || 'Please choose another vehicle or contact Eleganza Rentals.', true);
        return;
      }

      setNotice('success', `${car.name} has no recorded conflict for these dates.`, 'You can send your booking request. Final availability will be confirmed directly by Eleganza Rentals.');
    };

    ['change', 'input'].forEach(eventName => {
      vehicle.addEventListener(eventName, update);
      start.addEventListener(eventName, update);
      end.addEventListener(eventName, update);
    });
    update();
    return update;
  };

  document.querySelectorAll('[data-enquiry-form]').forEach(form => {
    const response = form.querySelector('[data-form-response]');
    const button = form.querySelector('button[type="submit"]');
    const refreshBookingState = form.matches('[data-booking-form]') ? initialiseBookingAvailability(form) : () => {};

    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (button.disabled) return;
      response.className = 'form-response';
      response.textContent = '';
      const old = button.innerHTML;
      button.disabled = true;
      button.innerHTML = '<span>Sending…</span>';

      try {
        const result = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const data = await result.json();
        if (!result.ok || !data.ok) throw new Error(data.message || 'Could not send your request.');
        response.classList.add('success');
        response.textContent = data.message;
        form.reset();
      } catch (error) {
        response.classList.add('error');
        response.textContent = error.message || 'Something went wrong. Please call or email us.';
      } finally {
        button.innerHTML = old;
        button.disabled = false;
        refreshBookingState();
      }
    });
  });
})();
