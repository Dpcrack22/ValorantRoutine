document.addEventListener('DOMContentLoaded', () => {
  setupFlashDismiss();
  setupSiteNavMenu();
  setupAuthModal();
  setupAuthConsole();
  setupRoutineNameFilter();
  setupDashboardQuickFill();
  setupRoutineRows();
  setupMatchRows();
  setupSessionDraftAutosave();
  setupTodayButton();
  renderChart();

  window.addEventListener('resize', debounce(renderChart, 150));
});

function setupFlashDismiss() {
  const flashes = Array.from(document.querySelectorAll('.flash'));

  flashes.forEach((flash) => {
    const closeButton = flash.querySelector('[data-flash-close]');

    if (!closeButton) {
      return;
    }

    closeButton.addEventListener('click', () => {
      flash.remove();
    });
  });
}

function setupSiteNavMenu() {
  const headers = Array.from(document.querySelectorAll('.site-header'));

  headers.forEach((header) => {
    const nav = header.querySelector('.site-nav');
    const toggle = header.querySelector('[data-site-nav-toggle]');

    if (!(nav instanceof HTMLElement) || !(toggle instanceof HTMLButtonElement)) {
      return;
    }

    const setOpen = (isOpen) => {
      header.classList.toggle('is-nav-open', isOpen);
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
      setOpen(!header.classList.contains('is-nav-open'));
    });

    nav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 940px)').matches) {
          setOpen(false);
        }
      });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    });

    window.addEventListener('resize', () => {
      if (!window.matchMedia('(max-width: 940px)').matches) {
        setOpen(false);
      }
    });
  });
}

function setupAuthModal() {
  const modal = document.querySelector('[data-auth-modal]');

  if (!modal) {
    return;
  }

  const openButtons = Array.from(document.querySelectorAll('[data-auth-open]'));
  const closeButtons = Array.from(modal.querySelectorAll('[data-auth-close]'));
  const tabButtons = Array.from(modal.querySelectorAll('[data-auth-tab]'));
  const panels = Array.from(modal.querySelectorAll('[data-auth-panel]'));
  const body = document.body;

  const activateTab = (tabName) => {
    const nextTab = tabName || 'login';

    tabButtons.forEach((button) => {
      const isActive = button.dataset.authTab === nextTab;
      button.classList.toggle('is-active', isActive);
      button.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    panels.forEach((panel) => {
      panel.classList.toggle('is-active', panel.dataset.authPanel === nextTab);
    });

    const activePanel = panels.find((panel) => panel.dataset.authPanel === nextTab);
    const focusTarget = activePanel?.querySelector('input, select, textarea');
    if (focusTarget instanceof HTMLElement) {
      window.setTimeout(() => focusTarget.focus({ preventScroll: true }), 0);
    }
  };

  const openModal = (tabName) => {
    modal.hidden = false;
    modal.classList.add('is-open');
    body.classList.add('modal-open');
    activateTab(tabName || 'login');
  };

  const closeModal = () => {
    modal.classList.remove('is-open');
    body.classList.remove('modal-open');
    modal.hidden = true;
  };

  openButtons.forEach((button) => {
    button.addEventListener('click', (event) => {
      event.preventDefault();
      openModal(button.dataset.authOpen || 'login');
    });
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeModal);
  });

  tabButtons.forEach((button) => {
    button.addEventListener('click', () => activateTab(button.dataset.authTab || 'login'));
  });

  modal.addEventListener('click', (event) => {
    if (event.target === modal || event.target.hasAttribute('data-auth-close')) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });

  const initialTab = new URLSearchParams(window.location.search).get('auth') || tabButtons[0]?.dataset.authTab || 'login';
  const shouldOpen = new URLSearchParams(window.location.search).has('auth');

  activateTab(initialTab);

  if (shouldOpen) {
    openModal(initialTab);
  }
}

function setupAuthConsole() {
  const consolePanel = document.querySelector('[data-auth-console]');

  if (!consolePanel) {
    return;
  }

  const tabButtons = Array.from(consolePanel.querySelectorAll('[data-auth-tab]'));
  const panels = Array.from(consolePanel.querySelectorAll('[data-auth-panel]'));
  const triggers = Array.from(document.querySelectorAll('[data-auth-open]'));

  const activateTab = (tabName) => {
    const nextTab = tabName || tabButtons[0]?.dataset.authTab || 'login';

    tabButtons.forEach((button) => {
      const isActive = button.dataset.authTab === nextTab;
      button.classList.toggle('is-active', isActive);
      button.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    panels.forEach((panel) => {
      panel.classList.toggle('is-active', panel.dataset.authPanel === nextTab);
    });

    const activePanel = panels.find((panel) => panel.dataset.authPanel === nextTab);
    const focusTarget = activePanel?.querySelector('input, select, textarea');

    if (focusTarget instanceof HTMLElement) {
      window.setTimeout(() => focusTarget.focus({ preventScroll: true }), 0);
    }

    return nextTab;
  };

  tabButtons.forEach((button) => {
    button.addEventListener('click', () => {
      activateTab(button.dataset.authTab || 'login');
    });
  });

  triggers.forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const tabName = trigger.getAttribute('data-auth-open') || 'login';
      activateTab(tabName);
      consolePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  const activeTab =
    tabButtons.find((button) => button.classList.contains('is-active'))?.dataset.authTab ||
    panels.find((panel) => panel.classList.contains('is-active'))?.dataset.authPanel ||
    'login';

  activateTab(activeTab);
}

function setupDashboardQuickFill() {
  const dashboardData = window.__DASHBOARD_DATA__ || {};
  const fillButtons = Array.from(document.querySelectorAll('[data-fill-last-session]'));
  const templateButtons = Array.from(document.querySelectorAll('[data-routine-template]'));
  const routineContainer = document.getElementById('routineRows');
  const matchContainer = document.getElementById('matchRows');
  const loadRoutineButton = document.getElementById('loadRoutineTemplate');
  const benchmarkInput = document.querySelector('input[name="benchmark"]');
  const notesInput = document.querySelector('[name="notes"]');
  const daySelect = document.querySelector('select[name="day_name"]');
  const routineNameSelect = document.getElementById('sessionRoutineName');
  const sessionDate = document.getElementById('sessionDate');
  const lastSession = dashboardData.last_session || null;

  if (!fillButtons.length && !templateButtons.length) {
    return;
  }

  const getLocalDateValue = () => {
    const today = new Date();
    const offset = today.getTimezoneOffset() * 60000;
    const localDate = new Date(today.getTime() - offset);
    return localDate.toISOString().slice(0, 10);
  };

  const applyCurrentDate = () => {
    const todayValue = getLocalDateValue();

    if (sessionDate) {
      sessionDate.value = todayValue;
    }

    if (daySelect) {
      daySelect.value = getDayName(todayValue);
    }
  };

  const getRoutineFields = (row) => ({
    itemId: row.querySelector('select[name="routine_user_item_id[]"]'),
    points: row.querySelector('input[name="routine_points[]"]'),
    minutes: row.querySelector('input[name="routine_minutes[]"]'),
    accuracy: row.querySelector('input[name="routine_accuracy[]"]'),
    notes: row.querySelector('input[name="routine_notes[]"]'),
  });

  const getMatchFields = (row) => ({
    type: row.querySelector('select[name="match_type[]"]'),
    kills: row.querySelector('input[name="match_kills[]"]'),
    deaths: row.querySelector('input[name="match_deaths[]"]'),
    assists: row.querySelector('input[name="match_assists[]"]'),
    result: row.querySelector('select[name="match_result[]"]'),
    headshot: row.querySelector('input[name="match_headshot_pct[]"]'),
    roundsFor: row.querySelector('input[name="match_rounds_for[]"]'),
    roundsAgainst: row.querySelector('input[name="match_rounds_against[]"]'),
    acs: row.querySelector('input[name="match_acs[]"]'),
    kast: row.querySelector('input[name="match_kast[]"]'),
    rankedFields: row.querySelector('[data-ranked-match-fields]'),
    preview: row.querySelector('[data-kda-preview]'),
  });

  const clearRoutineRow = (row) => {
    const fields = getRoutineFields(row);

    if (fields.itemId) fields.itemId.selectedIndex = 0;
    if (fields.points) fields.points.value = '';
    if (fields.accuracy) fields.accuracy.value = '';
  };

  const clearMatchRow = (row) => {
    const fields = getMatchFields(row);

    if (fields.type) fields.type.selectedIndex = 0;
    if (fields.kills) fields.kills.value = '';
    if (fields.deaths) fields.deaths.value = '';
    if (fields.assists) fields.assists.value = '';
    if (fields.result) fields.result.selectedIndex = 0;
    if (fields.headshot) fields.headshot.value = '';
    if (fields.roundsFor) fields.roundsFor.value = '';
    if (fields.roundsAgainst) fields.roundsAgainst.value = '';
    if (fields.acs) fields.acs.value = '';
    if (fields.kast) fields.kast.value = '';
    if (fields.rankedFields) fields.rankedFields.hidden = true;
    if (fields.preview) fields.preview.textContent = '0.00';
  };

  const fillRoutineRow = (row, routine, options = { keepMetrics: false }) => {
    const fields = getRoutineFields(row);
    const routineItems = Array.isArray(dashboardData.routine_items) ? dashboardData.routine_items : [];
    const routineId = Number(routine.user_routine_item_id || routine.routine_user_item_id || 0);
    const matchedRoutineItem = routineId
      ? routineItems.find((item) => Number(item.id) === routineId)
      : routineItems.find((item) => {
          const platformMatch = String(item.platform || '') === String(routine.section_name || '');
          const nameMatch = String(item.exercise_name || '') === String(routine.item_name || '');
          return platformMatch && nameMatch;
        });

    if (fields.itemId && matchedRoutineItem) fields.itemId.value = String(matchedRoutineItem.id);

    if (!options.keepMetrics) {
      if (fields.points && routine.score_points !== undefined && routine.score_points !== null) {
        fields.points.value = String(routine.score_points);
      }

      if (fields.accuracy && routine.accuracy_pct !== undefined && routine.accuracy_pct !== null) {
        fields.accuracy.value = String(routine.accuracy_pct);
      }
    }
  };

  const fillMatchRow = (row, match) => {
    const fields = getMatchFields(row);

    if (fields.type && match.match_type) fields.type.value = match.match_type;
    if (fields.kills && match.kills !== undefined && match.kills !== null) fields.kills.value = String(match.kills);
    if (fields.deaths && match.deaths !== undefined && match.deaths !== null) fields.deaths.value = String(match.deaths);
    if (fields.assists && match.assists !== undefined && match.assists !== null) fields.assists.value = String(match.assists);
    if (fields.result && match.match_result) fields.result.value = match.match_result;
    if (fields.headshot && match.headshot_pct !== undefined && match.headshot_pct !== null) fields.headshot.value = String(match.headshot_pct);
    if (fields.roundsFor && match.rounds_for !== undefined && match.rounds_for !== null) fields.roundsFor.value = String(match.rounds_for);
    if (fields.roundsAgainst && match.rounds_against !== undefined && match.rounds_against !== null) fields.roundsAgainst.value = String(match.rounds_against);
    if (fields.acs && match.acs !== undefined && match.acs !== null) fields.acs.value = String(match.acs);
    if (fields.kast && match.kast !== undefined && match.kast !== null) fields.kast.value = String(match.kast);

    toggleMatchConditionalFields(row);

    updateMatchKdaPreview(row);
  };

  const ensureRoutineRows = (count) => {
    if (!routineContainer) {
      return [];
    }

    while (routineContainer.children.length < count) {
      const row = createRoutineRow();
      routineContainer.appendChild(row);
      bindRemoveButton(row, routineContainer, applyRoutineNameFilter);
    }

    applyRoutineNameFilter();

    return Array.from(routineContainer.querySelectorAll('.routine-row'));
  };

  const syncRoutineRowCount = (count) => {
    if (!routineContainer) {
      return [];
    }

    const targetCount = Math.max(1, count);

    while (routineContainer.children.length < targetCount) {
      const row = createRoutineRow();
      routineContainer.appendChild(row);
      bindRemoveButton(row, routineContainer, applyRoutineNameFilter);
    }

    while (routineContainer.children.length > targetCount) {
      const lastChild = routineContainer.lastElementChild;
      if (!lastChild) {
        break;
      }
      lastChild.remove();
    }

    applyRoutineNameFilter();

    return Array.from(routineContainer.querySelectorAll('.routine-row'));
  };

  const hasRoutineRowData = () => {
    if (!routineContainer) {
      return false;
    }

    return Array.from(routineContainer.querySelectorAll('.routine-row')).some((row) => {
      const itemValue = String(row.querySelector('select[name="routine_user_item_id[]"]')?.value || '').trim();
      const pointsValue = String(row.querySelector('input[name="routine_points[]"]')?.value || '').trim();
      const accuracyValue = String(row.querySelector('input[name="routine_accuracy[]"]')?.value || '').trim();

      return itemValue !== '' || pointsValue !== '' || accuracyValue !== '';
    });
  };

  const getRoutineItemsByName = (routineName) => {
    const normalizedTarget = normalizeRoutineName(routineName);
    const routineItems = Array.isArray(dashboardData.routine_items) ? dashboardData.routine_items : [];

    return routineItems.filter((item) => normalizeRoutineName(item.routine_name) === normalizedTarget);
  };

  const preloadRoutinePlaylist = (routineName, options = { force: false, askConfirm: false }) => {
    if (!routineContainer) {
      return false;
    }

    const routineItems = getRoutineItemsByName(routineName);
    if (!routineItems.length) {
      return false;
    }

    const alreadyHasData = hasRoutineRowData();
    if (!options.force && alreadyHasData) {
      return false;
    }

    if (options.askConfirm && alreadyHasData) {
      const accepted = window.confirm('Esto reemplazara los ejercicios cargados en la seccion de rutina. Continuar?');
      if (!accepted) {
        return false;
      }
    }

    const rows = syncRoutineRowCount(routineItems.length);

    rows.forEach((row, index) => {
      const item = routineItems[index];
      const itemField = row.querySelector('select[name="routine_user_item_id[]"]');
      const pointsField = row.querySelector('input[name="routine_points[]"]');
      const accuracyField = row.querySelector('input[name="routine_accuracy[]"]');

      if (itemField instanceof HTMLSelectElement) {
        itemField.value = item ? String(item.id) : '';
      }

      if (pointsField instanceof HTMLInputElement) {
        pointsField.value = '';
      }

      if (accuracyField instanceof HTMLInputElement) {
        accuracyField.value = '';
      }
    });

    applyRoutineNameFilter();

    return true;
  };

  const ensureMatchRows = (count) => {
    if (!matchContainer) {
      return [];
    }

    while (matchContainer.children.length < count) {
      const row = createMatchRow();
      matchContainer.appendChild(row);
      bindRemoveButton(row, matchContainer);
      bindMatchRowEvents(row);
    }

    return Array.from(matchContainer.querySelectorAll('.match-row'));
  };

  const fillFromSession = (session, options = { useTodayDate: true }) => {
    if (!session) {
      return;
    }

    if (options.useTodayDate) {
      applyCurrentDate();
    } else {
      if (sessionDate && session.session_date) {
        sessionDate.value = String(session.session_date);
      }

      if (daySelect && session.day_name) {
        daySelect.value = String(session.day_name);
      }
    }

    if (routineNameSelect && session.session_routine_name) {
      routineNameSelect.value = session.session_routine_name;
      applyRoutineNameFilter();
    }

    if (benchmarkInput && session.benchmark) {
      benchmarkInput.value = session.benchmark;
    }

    if (notesInput) {
      notesInput.value = session.notes ? String(session.notes) : '';
    }

    const routineRows = ensureRoutineRows((session.routines || []).length || 1);
    routineRows.forEach((row, index) => {
      const template = session.routines?.[index];
      if (template) {
        fillRoutineRow(row, template, { keepMetrics: false });
      } else {
        clearRoutineRow(row);
      }
    });

    const matchRows = ensureMatchRows((session.matches || []).length || 1);
    matchRows.forEach((row, index) => {
      const template = session.matches?.[index];
      if (template) {
        fillMatchRow(row, template);
      } else {
        clearMatchRow(row);
      }
    });

    sessionDate?.focus();
  };

  const fillRoutineTemplate = (template) => {
    if (!template || !routineContainer) {
      return;
    }

    const rows = Array.from(routineContainer.querySelectorAll('.routine-row'));
    let targetRow = rows.find((row) => {
      const itemSelect = row.querySelector('select[name="routine_user_item_id[]"]');
      return itemSelect instanceof HTMLSelectElement && !itemSelect.value;
    });

    if (!targetRow) {
      targetRow = createRoutineRow();
      routineContainer.appendChild(targetRow);
      bindRemoveButton(targetRow, routineContainer);
    }

    fillRoutineRow(targetRow, template, { keepMetrics: false });

    const pointsInput = targetRow.querySelector('input[name="routine_points[]"]');
    if (pointsInput instanceof HTMLInputElement) {
      pointsInput.focus();
    }
  };

  fillButtons.forEach((button) => {
    button.addEventListener('click', () => fillFromSession(lastSession, { useTodayDate: true }));
  });

  if (routineNameSelect) {
    routineNameSelect.addEventListener('change', () => {
      preloadRoutinePlaylist(routineNameSelect.value, { force: true, askConfirm: true });
    });
  }

  if (loadRoutineButton && routineNameSelect) {
    loadRoutineButton.addEventListener('click', () => {
      preloadRoutinePlaylist(routineNameSelect.value, { force: true, askConfirm: true });
    });
  }

  templateButtons.forEach((button) => {
    button.addEventListener('click', () => {
      try {
        fillRoutineTemplate(JSON.parse(button.dataset.routineTemplate || '{}'));
      } catch (error) {
        console.error('No se pudo aplicar la plantilla rápida.', error);
      }
    });
  });

  if (sessionDate || daySelect) {
    applyCurrentDate();
  }

  if (routineNameSelect) {
    preloadRoutinePlaylist(routineNameSelect.value, { force: false, askConfirm: false });
  }

  window.__fillSessionForm = fillFromSession;
}

function setupRoutineRows() {
  const container = document.getElementById('routineRows');
  const addButton = document.getElementById('addRoutine');

  if (!container) {
    return;
  }

  container.querySelectorAll('.routine-row').forEach((row) => bindRemoveButton(row, container, applyRoutineNameFilter));

  if (addButton) {
    addButton.addEventListener('click', () => {
      const row = createRoutineRow();
      container.appendChild(row);
      bindRemoveButton(row, container, applyRoutineNameFilter);
      applyRoutineNameFilter();
    });
  }

  applyRoutineNameFilter();
}

function setupMatchRows() {
  const container = document.getElementById('matchRows');
  const addButton = document.getElementById('addMatch');

  if (!container) {
    return;
  }

  container.querySelectorAll('.match-row').forEach((row) => {
    bindRemoveButton(row, container);
    bindMatchRowEvents(row);
  });

  if (addButton) {
    addButton.addEventListener('click', () => {
      const row = createMatchRow();
      container.appendChild(row);
      bindRemoveButton(row, container);
      bindMatchRowEvents(row);
    });
  }
}

function setupRoutineNameFilter() {
  const routineNameSelect = document.getElementById('sessionRoutineName');
  if (!routineNameSelect) {
    return;
  }

  routineNameSelect.addEventListener('change', () => {
    applyRoutineNameFilter();
  });

  applyRoutineNameFilter();
}

function applyRoutineNameFilter() {
  const routineNameSelect = document.getElementById('sessionRoutineName');
  if (!routineNameSelect) {
    return;
  }

  const selectedRoutineName = normalizeRoutineName(routineNameSelect.value);

  document.querySelectorAll('.routine-summary-card[data-routine-name]').forEach((card) => {
    const cardRoutineName = normalizeRoutineName(card.getAttribute('data-routine-name') || '');
    card.hidden = cardRoutineName !== selectedRoutineName;
  });

  document.querySelectorAll('select[name="routine_user_item_id[]"]').forEach((select) => {
    if (!(select instanceof HTMLSelectElement)) {
      return;
    }

    const currentValue = select.value;
    let selectedStillValid = currentValue === '';

    Array.from(select.options).forEach((option, index) => {
      if (index === 0) {
        option.hidden = false;
        option.disabled = false;
        return;
      }

      const optionRoutineName = normalizeRoutineName(option.getAttribute('data-routine-name') || '');
      const isAllowed = optionRoutineName === selectedRoutineName;

      option.hidden = !isAllowed;
      option.disabled = !isAllowed;

      if (isAllowed && option.value === currentValue) {
        selectedStillValid = true;
      }
    });

    if (!selectedStillValid) {
      select.value = '';
    }
  });
}

function normalizeRoutineName(value) {
  const name = String(value || '').trim();
  return name === '' ? 'Rutina principal' : name;
}

function bindMatchRowEvents(row) {
  toggleMatchConditionalFields(row);
  updateMatchKdaPreview(row);

  const typeField = row.querySelector('select[name="match_type[]"]');
  if (typeField) {
    typeField.addEventListener('change', () => {
      toggleMatchConditionalFields(row);
      updateMatchKdaPreview(row);
    });
  }

  ['match_kills[]', 'match_deaths[]', 'match_assists[]'].forEach((fieldName) => {
    const field = row.querySelector(`[name="${fieldName}"]`);
    if (field) {
      field.addEventListener('input', () => {
        updateMatchKdaPreview(row);
      });
    }
  });
}

function updateMatchKdaPreview(row) {
  const preview = row.querySelector('[data-kda-preview]');
  if (!preview) {
    return;
  }

  const kills = Number(row.querySelector('input[name="match_kills[]"]')?.value || 0);
  const deaths = Math.max(1, Number(row.querySelector('input[name="match_deaths[]"]')?.value || 0));
  const assists = Number(row.querySelector('input[name="match_assists[]"]')?.value || 0);
  const kda = ((Number.isFinite(kills) ? kills : 0) + (Number.isFinite(assists) ? assists : 0)) / (Number.isFinite(deaths) ? deaths : 1);
  preview.textContent = kda.toFixed(2);
}

function createRoutineRow() {
  const routineItems = Array.isArray(window.__DASHBOARD_DATA__?.routine_items) ? window.__DASHBOARD_DATA__.routine_items : [];
  const row = document.createElement('article');
  row.className = 'entry-row routine-row';
  row.innerHTML = `
    <div class="row-line row-line-three">
      <label class="inline-field">
        <span>Ejercicio de rutina</span>
        <select name="routine_user_item_id[]">
          <option value="">Elige un ejercicio</option>
          ${routineItemOptions(routineItems)}
        </select>
      </label>
      <label class="inline-field small">
        <span>Puntos</span>
        <input type="text" name="routine_points[]" placeholder="12340" />
      </label>
      <label class="inline-field small">
        <span>Accuracy %</span>
        <input type="text" name="routine_accuracy[]" placeholder="86.4" />
      </label>
    </div>
    <div class="row-line row-line-four">
      <button class="remove-row" type="button" title="Eliminar rutina">-</button>
    </div>
  `;

  applyRoutineNameFilter();

  return row;
}

function createMatchRow() {
  const row = document.createElement('article');
  row.className = 'entry-row match-row';
  row.innerHTML = `
    <div class="row-line row-line-four">
      <label class="inline-field small">
        <span>Tipo</span>
        <select name="match_type[]">
          ${matchTypeOptions()}
        </select>
      </label>
      <label class="inline-field small">
        <span>Kills</span>
        <input type="text" name="match_kills[]" placeholder="24" />
      </label>
      <label class="inline-field small">
        <span>Deaths</span>
        <input type="text" name="match_deaths[]" placeholder="18" />
      </label>
      <label class="inline-field small">
        <span>Assists</span>
        <input type="text" name="match_assists[]" placeholder="5" />
      </label>
      <label class="inline-field small">
        <span>Headshot %</span>
        <input type="text" name="match_headshot_pct[]" placeholder="27.5" />
      </label>
    </div>
    <div class="row-line row-line-four match-line-bottom">
      <label class="inline-field small">
        <span>Resultado</span>
        <select name="match_result[]">
          <option value="">Elige</option>
          <option value="win">Win</option>
          <option value="loss">Loss</option>
        </select>
      </label>
      <div class="kda-preview-box">
        <span class="small-muted">KDA auto</span>
        <strong data-kda-preview>0.00</strong>
      </div>
      <button class="remove-row" type="button" title="Eliminar partida">-</button>
    </div>
    <div class="row-line row-line-four match-ranked-fields" data-ranked-match-fields hidden>
      <label class="inline-field small">
        <span>Rondas a favor</span>
        <input type="text" name="match_rounds_for[]" placeholder="13" />
      </label>
      <label class="inline-field small">
        <span>Rondas en contra</span>
        <input type="text" name="match_rounds_against[]" placeholder="4" />
      </label>
      <label class="inline-field small">
        <span>ACS</span>
        <input type="text" name="match_acs[]" placeholder="245" />
      </label>
      <label class="inline-field small">
        <span>KAST %</span>
        <input type="text" name="match_kast[]" placeholder="78.5" />
      </label>
    </div>
  `;

  return row;
}

function toggleMatchConditionalFields(row) {
  const typeField = row.querySelector('select[name="match_type[]"]');
  const rankedFields = row.querySelector('[data-ranked-match-fields]');

  if (!typeField || !rankedFields) {
    return;
  }

  const isRanked = ['Ranked', 'Premier'].includes(String(typeField.value || ''));
  row.dataset.matchMode = isRanked ? 'ranked' : 'standard';
  row.classList.toggle('is-ranked-match', isRanked);
  rankedFields.hidden = !isRanked;

  const resultField = row.querySelector('select[name="match_result[]"]');
  if (resultField) {
    resultField.required = String(typeField.value || '').trim() !== '';
  }
}

function setupSessionDraftAutosave() {
  const form = document.querySelector('[data-draft-form="sessions"]');

  if (!form) {
    return;
  }

  const storageKey = 'valorantRoutine.sessionsDraft';
  const routineContainer = document.getElementById('routineRows');
  const matchContainer = document.getElementById('matchRows');

  const ensureRowCount = (container, currentCount, targetCount, factory, onCreate) => {
    if (!container) {
      return;
    }

    while (currentCount < targetCount) {
      const row = factory();
      container.appendChild(row);
      bindRemoveButton(row, container);
      if (typeof onCreate === 'function') {
        onCreate(row);
      }
      currentCount += 1;
    }
  };

  const saveDraft = () => {
    const benchmarkInput = form.querySelector('input[name="benchmark"]');
    const routineNameSelect = form.querySelector('[name="session_routine_name"]');
    const draft = {
      sessionDate: form.querySelector('[name="session_date"]')?.value || '',
      dayName: form.querySelector('[name="day_name"]')?.value || '',
      sessionRoutineName: routineNameSelect?.value || '',
      benchmark: benchmarkInput?.value || '',
      notes: form.querySelector('[name="notes"]')?.value || '',
      routines: [],
      matches: [],
    };

    const routineRows = Array.from(form.querySelectorAll('.routine-row'));
    routineRows.forEach((row) => {
      draft.routines.push({
        routine_user_item_id: row.querySelector('[name="routine_user_item_id[]"]')?.value || '',
        routine_points: row.querySelector('[name="routine_points[]"]')?.value || '',
        routine_accuracy: row.querySelector('[name="routine_accuracy[]"]')?.value || '',
      });
    });

    const matchRows = Array.from(form.querySelectorAll('.match-row'));
    matchRows.forEach((row) => {
      draft.matches.push({
        match_type: row.querySelector('[name="match_type[]"]')?.value || '',
        match_kills: row.querySelector('[name="match_kills[]"]')?.value || '',
        match_deaths: row.querySelector('[name="match_deaths[]"]')?.value || '',
        match_assists: row.querySelector('[name="match_assists[]"]')?.value || '',
        match_result: row.querySelector('[name="match_result[]"]')?.value || '',
        match_headshot_pct: row.querySelector('[name="match_headshot_pct[]"]')?.value || '',
        match_rounds_for: row.querySelector('[name="match_rounds_for[]"]')?.value || '',
        match_rounds_against: row.querySelector('[name="match_rounds_against[]"]')?.value || '',
        match_acs: row.querySelector('[name="match_acs[]"]')?.value || '',
        match_kast: row.querySelector('[name="match_kast[]"]')?.value || '',
      });
    });

    window.localStorage.setItem(storageKey, JSON.stringify(draft));
  };

  const applyDraft = () => {
    try {
      const raw = window.localStorage.getItem(storageKey);
      if (!raw) {
        return false;
      }

      const draft = JSON.parse(raw);

      const sessionDate = form.querySelector('[name="session_date"]');
      const dayName = form.querySelector('[name="day_name"]');
      const sessionRoutineName = form.querySelector('[name="session_routine_name"]');
      const benchmarkInput = form.querySelector('[name="benchmark"]');
      const notes = form.querySelector('[name="notes"]');

      if (sessionDate && draft.sessionDate) sessionDate.value = draft.sessionDate;
      if (dayName && draft.dayName) dayName.value = draft.dayName;
      if (sessionRoutineName && draft.sessionRoutineName) sessionRoutineName.value = draft.sessionRoutineName;
      if (benchmarkInput && draft.benchmark) benchmarkInput.value = draft.benchmark;
      if (benchmarkInput && !draft.benchmark && draft.benchmarkLabel) {
        benchmarkInput.value = draft.benchmarkLabel;
      }
      if (notes && draft.notes) notes.value = draft.notes;

      applyRoutineNameFilter();

      const routines = Array.isArray(draft.routines) ? draft.routines : [];
      const matches = Array.isArray(draft.matches) ? draft.matches : [];

      if (routineContainer) {
        ensureRowCount(routineContainer, routineContainer.querySelectorAll('.routine-row').length, routines.length, createRoutineRow);
        const routineRows = Array.from(routineContainer.querySelectorAll('.routine-row'));

        routineRows.forEach((row, index) => {
          const draftRow = routines[index] || {};
          const routineItem = row.querySelector('[name="routine_user_item_id[]"]');
          const routinePoints = row.querySelector('[name="routine_points[]"]');
          const routineAccuracy = row.querySelector('[name="routine_accuracy[]"]');

          if (routineItem) routineItem.value = draftRow.routine_user_item_id || '';
          if (routinePoints) routinePoints.value = draftRow.routine_points || '';
          if (routineAccuracy) routineAccuracy.value = draftRow.routine_accuracy || '';
        });

        applyRoutineNameFilter();
      }

      if (matchContainer) {
        ensureRowCount(matchContainer, matchContainer.querySelectorAll('.match-row').length, matches.length, createMatchRow, bindMatchRowEvents);
        const matchRows = Array.from(matchContainer.querySelectorAll('.match-row'));

        matchRows.forEach((row, index) => {
          const draftRow = matches[index] || {};
          const matchType = row.querySelector('[name="match_type[]"]');
          const matchKills = row.querySelector('[name="match_kills[]"]');
          const matchDeaths = row.querySelector('[name="match_deaths[]"]');
          const matchAssists = row.querySelector('[name="match_assists[]"]');
          const matchResult = row.querySelector('[name="match_result[]"]');
          const matchHeadshot = row.querySelector('[name="match_headshot_pct[]"]');
          const matchRoundsFor = row.querySelector('[name="match_rounds_for[]"]');
          const matchRoundsAgainst = row.querySelector('[name="match_rounds_against[]"]');
            const matchAcs = row.querySelector('[name="match_acs[]"]');
          const matchKast = row.querySelector('[name="match_kast[]"]');

          if (matchType) matchType.value = draftRow.match_type || '';
          if (matchKills) matchKills.value = draftRow.match_kills || '';
          if (matchDeaths) matchDeaths.value = draftRow.match_deaths || '';
          if (matchAssists) matchAssists.value = draftRow.match_assists || '';
          if (matchResult) matchResult.value = draftRow.match_result || '';
          if (matchHeadshot) matchHeadshot.value = draftRow.match_headshot_pct || '';
          if (matchRoundsFor) matchRoundsFor.value = draftRow.match_rounds_for || '';
          if (matchRoundsAgainst) matchRoundsAgainst.value = draftRow.match_rounds_against || '';
            if (matchAcs) matchAcs.value = draftRow.match_acs || '';
          if (matchKast) matchKast.value = draftRow.match_kast || '';

          toggleMatchConditionalFields(row);

          const preview = row.querySelector('[data-kda-preview]');
          const kills = Number(matchKills?.value || 0);
          const deaths = Math.max(1, Number(matchDeaths?.value || 0));
          const assists = Number(matchAssists?.value || 0);
          if (preview) {
            preview.textContent = (((Number.isFinite(kills) ? kills : 0) + (Number.isFinite(assists) ? assists : 0)) / (Number.isFinite(deaths) ? deaths : 1)).toFixed(2);
          }
        });
      }
      return true;
    } catch (error) {
      console.error('No se pudo restaurar el borrador de sesiones.', error);
      return false;
    }
  };

  const draftApplied = applyDraft();

  if (!draftApplied) {
    const todaySession = window.__TODAY_SESSION__ || null;
    if (todaySession && typeof window.__fillSessionForm === 'function') {
      window.__fillSessionForm(todaySession, { useTodayDate: false });
      saveDraft();
    }
  }

  form.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement) || !target.classList.contains('remove-row')) {
      return;
    }

    window.setTimeout(saveDraft, 0);
  });

  form.addEventListener('input', saveDraft);
  form.addEventListener('change', saveDraft);
  form.addEventListener('submit', saveDraft);
}

function bindRemoveButton(row, container, afterRemove = () => {}) {
  const removeButton = row.querySelector('.remove-row');
  if (!removeButton) {
    return;
  }

  removeButton.addEventListener('click', () => {
    if (container.children.length <= 1) {
      return;
    }

    row.remove();
    afterRemove();
  });
}

function routineSectionOptions() {
  return [
    ['KovaaK\'s', 'KovaaK\'s'],
    ['Range', 'Range'],
    ['Aim Lab', 'Aim Lab'],
    ['Warmup', 'Warmup'],
    ['Other', 'Other'],
  ]
    .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
    .join('');
}

function matchTypeOptions() {
  return [
    ['', 'Elige tipo'],
    ['Deathmatch', 'Deathmatch'],
    ['Team Deathmatch', 'Team Deathmatch'],
    ['Ranked', 'Ranked'],
    ['Unrated', 'Unrated'],
    ['Premier', 'Premier'],
    ['Custom', 'Custom'],
  ]
    .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
    .join('');
}

function routineItemOptions(items) {
  return items
    .map((item) => {
      const id = escapeHtml(String(item.id ?? ''));
      const routineName = escapeHtml(String(item.routine_name || 'Rutina principal'));
      const label = `${escapeHtml(String(item.platform || ''))} · ${escapeHtml(String(item.exercise_name || ''))}`;
      return `<option value="${id}" data-routine-name="${routineName}">${label}</option>`;
    })
    .join('');
}

function renderChart() {
  const canvases = [
    {
      canvas: document.getElementById('progressChart'),
      data: Array.isArray(window.__CHART_DATA__) ? window.__CHART_DATA__ : [],
      title: 'Total de puntos por dia',
      accent: '#ff4655',
      fill: 'rgba(255, 70, 85, 0.28)',
      fillEnd: 'rgba(255, 70, 85, 0.03)',
      key: 'points',
    },
    {
      canvas: document.getElementById('routineChart'),
      data: Array.isArray(window.__DASHBOARD_CHARTS__?.routine) ? window.__DASHBOARD_CHARTS__.routine : [],
      title: 'Puntos de rutina por dia',
      accent: '#76dfff',
      fill: 'rgba(118, 223, 255, 0.24)',
      fillEnd: 'rgba(118, 223, 255, 0.03)',
      key: 'value',
    },
    {
      canvas: document.getElementById('kdaChart'),
      data: Array.isArray(window.__DASHBOARD_CHARTS__?.kda) ? window.__DASHBOARD_CHARTS__.kda : [],
      title: 'KDA medio por dia',
      accent: '#34d399',
      fill: 'rgba(52, 211, 153, 0.22)',
      fillEnd: 'rgba(52, 211, 153, 0.03)',
      key: 'value',
      decimals: 2,
    },
    {
      canvas: document.getElementById('resultsChart'),
      data: Array.isArray(window.__DASHBOARD_CHARTS__?.results) ? window.__DASHBOARD_CHARTS__.results : [],
      title: 'Victorias por dia',
      accent: '#ffb86b',
      fill: 'rgba(255, 184, 107, 0.24)',
      fillEnd: 'rgba(255, 184, 107, 0.03)',
      key: 'points',
    },
  ];

  canvases.forEach(({ canvas, data, title, accent, fill, fillEnd, key, decimals = 0 }) => {
    if (!canvas) {
      return;
    }

    const context = canvas.getContext('2d');
    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;

    const width = Math.max(320, Math.floor(rect.width || canvas.width));
    const height = Math.max(260, Math.floor(rect.height || canvas.height));

    canvas.width = Math.floor(width * ratio);
    canvas.height = Math.floor(height * ratio);
    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    context.clearRect(0, 0, width, height);

    drawRoundedRect(context, 0, 0, width, height, 20, 'rgba(7, 13, 24, 0.96)');

    if (!data.length) {
      context.fillStyle = '#9ca9c2';
      context.font = '600 20px "Space Grotesk", sans-serif';
      context.textAlign = 'center';
      context.fillText('Todavia no hay datos para el grafico', width / 2, height / 2);
      return;
    }

    const padding = { top: 44, right: 28, bottom: 72, left: 58 };
    const plotWidth = width - padding.left - padding.right;
    const plotHeight = height - padding.top - padding.bottom;
    const points = data.map((item) => Number(item[key]) || 0);
    const labels = data.map((item) => String(item.label || ''));
    const maxValue = Math.max(...points);
    const minValue = Math.min(...points);
    const range = Math.max(maxValue - minValue, 1);
    const stepX = points.length > 1 ? plotWidth / (points.length - 1) : 0;

    drawGrid(context, padding, width, height, 5);

    context.save();
    const fillGradient = context.createLinearGradient(0, padding.top, 0, padding.top + plotHeight);
    fillGradient.addColorStop(0, fill);
    fillGradient.addColorStop(1, fillEnd);

    context.beginPath();
    points.forEach((point, index) => {
      const x = padding.left + (stepX * index);
      const y = padding.top + plotHeight - (((point - minValue) / range) * plotHeight);

      if (index === 0) {
        context.moveTo(x, y);
      } else {
        context.lineTo(x, y);
      }
    });
    context.lineTo(padding.left + (stepX * (points.length - 1)), padding.top + plotHeight);
    context.lineTo(padding.left, padding.top + plotHeight);
    context.closePath();
    context.fillStyle = fillGradient;
    context.fill();

    context.strokeStyle = accent;
    context.lineWidth = 4;
    context.lineJoin = 'round';
    context.lineCap = 'round';
    context.shadowColor = accent;
    context.shadowBlur = 18;

    context.beginPath();
    points.forEach((point, index) => {
      const x = padding.left + (stepX * index);
      const y = padding.top + plotHeight - (((point - minValue) / range) * plotHeight);

      if (index === 0) {
        context.moveTo(x, y);
      } else {
        context.lineTo(x, y);
      }
    });
    context.stroke();

    points.forEach((point, index) => {
      const x = padding.left + (stepX * index);
      const y = padding.top + plotHeight - (((point - minValue) / range) * plotHeight);

      context.fillStyle = '#ecf2ff';
      context.beginPath();
      context.arc(x, y, 5, 0, Math.PI * 2);
      context.fill();

      context.fillStyle = accent;
      context.beginPath();
      context.arc(x, y, 3, 0, Math.PI * 2);
      context.fill();
    });
    context.restore();

    context.fillStyle = '#ecf2ff';
    context.font = '700 22px "Space Grotesk", sans-serif';
    context.textAlign = 'left';
    context.fillText(title, 24, 30);

    context.fillStyle = '#9aa7c0';
    context.font = '600 16px "Space Grotesk", sans-serif';
    context.textAlign = 'center';

    labels.forEach((label, index) => {
      const x = padding.left + (stepX * index);
      context.fillText(label, x, padding.top + plotHeight + 28);
    });

    context.textAlign = 'right';
    context.fillText(formatNumber(maxValue, decimals), padding.left - 10, padding.top + 8);
    context.fillText(formatNumber(minValue, decimals), padding.left - 10, padding.top + plotHeight);
  });
}

function setupTodayButton() {
  const button = document.getElementById('fillToday');
  const input = document.getElementById('sessionDate');
  const daySelect = document.querySelector('select[name="day_name"]');

  const applyToday = () => {
    const today = new Date();
    const offset = today.getTimezoneOffset() * 60000;
    const localDate = new Date(today.getTime() - offset);
    const dateValue = localDate.toISOString().slice(0, 10);

    if (input) {
      input.value = dateValue;
    }

    if (daySelect) {
      const weekdayMap = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      daySelect.value = weekdayMap[localDate.getDay()] || 'Monday';
    }
  };

  applyToday();

  if (input && daySelect) {
    input.addEventListener('input', () => {
      daySelect.value = getDayName(input.value);
    });

    input.addEventListener('change', () => {
      daySelect.value = getDayName(input.value);
    });
  }

  if (!button && !input) {
    return;
  }

  if (button) {
    button.addEventListener('click', applyToday);
  }
}

function getDayName(dateValue) {
  const weekdayMap = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  const date = new Date(dateValue);

  if (Number.isNaN(date.getTime())) {
    return 'Monday';
  }

  return weekdayMap[date.getDay()] || 'Monday';
}

function drawGrid(context, padding, width, height, lines) {
  context.save();
  context.strokeStyle = 'rgba(255,255,255,0.08)';
  context.lineWidth = 1;

  for (let index = 0; index <= lines; index += 1) {
    const y = padding.top + ((height - padding.top - padding.bottom) * (index / lines));
    context.beginPath();
    context.moveTo(padding.left, y);
    context.lineTo(width - padding.right, y);
    context.stroke();
  }

  context.restore();
}

function drawRoundedRect(context, x, y, width, height, radius, fillStyle) {
  context.save();
  context.fillStyle = fillStyle;
  context.beginPath();
  context.moveTo(x + radius, y);
  context.arcTo(x + width, y, x + width, y + height, radius);
  context.arcTo(x + width, y + height, x, y + height, radius);
  context.arcTo(x, y + height, x, y, radius);
  context.arcTo(x, y, x + width, y, radius);
  context.closePath();
  context.fill();
  context.restore();
}

function formatNumber(value, decimals = 0) {
  return new Intl.NumberFormat('es-ES', {
    maximumFractionDigits: decimals,
    minimumFractionDigits: decimals,
  }).format(Number(value) || 0);
}

function debounce(callback, delay) {
  let timeoutId;
  return () => {
    window.clearTimeout(timeoutId);
    timeoutId = window.setTimeout(() => callback(), delay);
  };
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}
