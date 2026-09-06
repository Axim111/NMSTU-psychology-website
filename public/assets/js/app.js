/* global window, document, fetch */
/**
 * Минимальный фронтовый слой (без фреймворков):
 * - модальные окна
 * - выбор даты/времени записи (подтягивает свободные слоты по API)
 * - подгрузка формы записи в модалку
 * - простые фильтры психологов на главной
 */
(() => {
  function qs(sel, root = document) { return root.querySelector(sel); }
  function qsa(sel, root = document) { return Array.from(root.querySelectorAll(sel)); }

  // -----------------------------
  // Modals
  // -----------------------------
  function openModal(modal) {
    if (!modal) return;
    modal.classList.add('is-open');
    document.documentElement.classList.add('modal-open');
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('is-open');
    if (!qs('.tw-modal.is-open')) {
      document.documentElement.classList.remove('modal-open');
    }
  }

  function initModals() {
    qsa('[data-modal-open]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const id = btn.getAttribute('data-modal-open');
        openModal(qs(`#${CSS.escape(id)}`));
      });
    });

    qsa('[data-modal]').forEach((modal) => {
      // закрытие по клику в оверлей
      modal.addEventListener('click', (e) => {
        const close = e.target.closest('[data-modal-close]');
        if (close) {
          e.preventDefault();
          closeModal(modal);
          return;
        }

        if (e.target === modal) {
          closeModal(modal);
        }
      });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      const top = qs('.tw-modal.is-open');
      if (top) closeModal(top);
    });
  }

  // -----------------------------
  // Главная: фильтр психологов
  // -----------------------------
  function initPsychologistsFilter() {
    const root = qs('[data-psychologists]');
    if (!root) return;

    const search = qs('[data-psych-search]');
    const chips = qsa('[data-direction-chip]');

    function applyFilter() {
      const q = (search?.value || '').trim().toLowerCase();
      const activeDirIds = new Set(
        chips
          .filter((c) => c.classList.contains('is-active'))
          .map((c) => c.getAttribute('data-direction-chip'))
          .filter(Boolean)
      );

      qsa('[data-psych-card]', root).forEach((card) => {
        const name = (card.getAttribute('data-psych-name') || '').toLowerCase();
        const dirs = (card.getAttribute('data-psych-directions') || '').split(',').map((x) => x.trim()).filter(Boolean);

        const okName = q === '' || name.includes(q);
        const okDir = activeDirIds.size === 0 || dirs.some((id) => activeDirIds.has(id));

        card.style.display = (okName && okDir) ? '' : 'none';
      });
    }

    chips.forEach((chip) => {
      chip.addEventListener('click', () => {
        chip.classList.toggle('is-active');
        applyFilter();
      });
    });
    search?.addEventListener('input', applyFilter);
  }

  // -----------------------------
  // Страница психолога: слоты
  // -----------------------------
  function formatTimeHHMM(timeStr) {
    return String(timeStr || '').slice(0, 5);
  }

  const FORMAT_LABELS = {
    individual: 'Индивидуально',
    group: 'Группой',
    family: 'Семейно',
  };

  function initBookingSlotsModal() {
    const page = qs('[data-psychologist-page]');
    if (!page) return;

    const psychologistId = page.getAttribute('data-psychologist-id');
    if (!psychologistId) return;

    const modal = qs('#twSlotsModal');
    const listDates = qs('[data-slots-dates]', modal);
    const listTimes = qs('[data-slots-times]', modal);
    const legend = qs('[data-slots-legend]', modal);
    const btnBook = qs('[data-open-slots]', page);

    const formModal = qs('#twBookingFormModal');
    const formContainer = qs('[data-booking-form-container]', formModal);

    let selectedDate = null;
    let slotsByDate = new Map(); // date -> array of slots

    function renderLegend() {
      if (!legend) return;
      legend.innerHTML = Object.keys(FORMAT_LABELS).map((k) => (
        `<span class="tw-legend-item"><span class="tw-dot tw-dot--${k}"></span>${FORMAT_LABELS[k]}</span>`
      )).join('');
    }

    function renderDates(dates) {
      if (!listDates) return;
      if (dates.length === 0) {
        listDates.innerHTML = '<div class="empty-state">Свободных дат пока нет</div>';
        return;
      }

      listDates.innerHTML = dates.map((d) => (
        `<button type="button" class="tw-date-item ${d === selectedDate ? 'is-active' : ''}" data-date="${d}">
          ${new Date(d + 'T00:00:00').toLocaleDateString('ru-RU', { weekday: 'short', day: '2-digit', month: 'short' })}
        </button>`
      )).join('');
    }

    function renderTimes(date) {
      if (!listTimes) return;
      const slots = slotsByDate.get(date) || [];
      if (slots.length === 0) {
        listTimes.innerHTML = '<div class="empty-state">На эту дату свободного времени нет</div>';
        return;
      }

      listTimes.innerHTML = slots.map((s) => (
        `<button type="button" class="tw-time-item tw-time-item--${s.format}" data-slot-id="${s.id}">
          ${formatTimeHHMM(s.start_time)}
        </button>`
      )).join('');
    }

    async function loadSlots() {
      const res = await fetch(`/api/psychologist/slots?id=${encodeURIComponent(psychologistId)}`, {
        headers: { 'Accept': 'application/json' }
      });
      if (!res.ok) throw new Error(`Slots API error: ${res.status}`);
      const data = await res.json();
      const dates = Array.isArray(data?.dates) ? data.dates : [];

      slotsByDate = new Map();
      dates.forEach((row) => {
        slotsByDate.set(row.date, row.slots || []);
      });

      selectedDate = dates[0]?.date || null;
      renderLegend();
      renderDates(dates.map((x) => x.date));
      if (selectedDate) renderTimes(selectedDate);
    }

    async function openSlots() {
      try {
        // "скелет" сразу, чтобы модалка не была пустой
        if (listDates) listDates.innerHTML = '<div class="tw-skeleton">Загружаем даты…</div>';
        if (listTimes) listTimes.innerHTML = '<div class="tw-skeleton">Загружаем время…</div>';
        renderLegend();
        openModal(modal);
        await loadSlots();
      } catch (e) {
        if (listDates) listDates.innerHTML = '<div class="empty-state">Не получилось загрузить расписание</div>';
        if (listTimes) listTimes.innerHTML = '';
      }
    }

    btnBook?.addEventListener('click', (e) => {
      e.preventDefault();
      openSlots();
    });

    // выбор даты
    listDates?.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-date]');
      if (!btn) return;
      selectedDate = btn.getAttribute('data-date');
      renderDates(Array.from(slotsByDate.keys()));
      renderTimes(selectedDate);
    });

    // выбор времени -> подгрузка формы
    listTimes?.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-slot-id]');
      if (!btn) return;
      const slotId = btn.getAttribute('data-slot-id');
      if (!slotId) return;

      try {
        if (formContainer) formContainer.innerHTML = '<div class="tw-skeleton">Загружаем форму…</div>';
        const res = await fetch(`/book?slot_id=${encodeURIComponent(slotId)}&partial=1`, {
          headers: { 'Accept': 'text/html' }
        });
        if (!res.ok) throw new Error(`Form error: ${res.status}`);
        const html = await res.text();
        if (formContainer) formContainer.innerHTML = html;
        closeModal(modal);
        openModal(formModal);
      } catch (err) {
        if (formContainer) formContainer.innerHTML = '<div class="empty-state">Не получилось загрузить форму</div>';
        openModal(formModal);
      }
    });
  }

  // -----------------------------
  // Бургер-меню
  // -----------------------------
  function initBurgerMenu() {
    const btn = qs('[data-burger-toggle]');
    const menu = qs('[data-burger-menu]');
    if (!btn || !menu) return;

    const open = () => {
      btn.classList.add('is-open');
      menu.classList.add('is-open');
      document.documentElement.classList.add('burger-open');
      btn.setAttribute('aria-expanded', 'true');
      btn.setAttribute('aria-label', 'Закрыть меню');
    };

    const close = () => {
      btn.classList.remove('is-open');
      menu.classList.remove('is-open');
      document.documentElement.classList.remove('burger-open');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Открыть меню');
    };

    const toggle = () => {
      if (menu.classList.contains('is-open')) {
        close();
      } else {
        open();
      }
    };

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      toggle();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      if (menu.classList.contains('is-open')) close();
    });

    document.addEventListener('click', (e) => {
      if (!menu.classList.contains('is-open')) return;
      const insideMenu = e.target.closest('[data-burger-menu]');
      const insideBtn = e.target.closest('[data-burger-toggle]');
      if (!insideMenu && !insideBtn) close();
    });

    qsa('a', menu).forEach((link) => {
      link.addEventListener('click', () => {
        if (menu.classList.contains('is-open')) close();
      });
    });
  }

  // -----------------------------
  // Init
  // -----------------------------
  document.addEventListener('DOMContentLoaded', () => {
    initModals();
    initBurgerMenu();
    initPsychologistsFilter();
    initBookingSlotsModal();
  });
})();

