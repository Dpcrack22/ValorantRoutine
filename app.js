document.addEventListener('DOMContentLoaded', () => {
  setupAuthConsole();
  setupRoutineRows();
  setupMatchRows();
  setupTodayButton();
  renderChart();

  window.addEventListener('resize', debounce(renderChart, 150));
});

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

function setupRoutineRows() {
  const container = document.getElementById('routineRows');
  const addButton = document.getElementById('addRoutine');

  if (!container) {
    return;
  }

  container.querySelectorAll('.routine-row').forEach((row) => bindRemoveButton(row, container));

  if (addButton) {
    addButton.addEventListener('click', () => {
      const row = createRoutineRow();
      container.appendChild(row);
      bindRemoveButton(row, container);
    });
  }
}

function setupMatchRows() {
  const container = document.getElementById('matchRows');
  const addButton = document.getElementById('addMatch');

  if (!container) {
    return;
  }

  container.querySelectorAll('.match-row').forEach((row) => bindRemoveButton(row, container));

  if (addButton) {
    addButton.addEventListener('click', () => {
      const row = createMatchRow();
      container.appendChild(row);
      bindRemoveButton(row, container);
    });
  }
}

function createRoutineRow() {
  const row = document.createElement('article');
  row.className = 'entry-row routine-row';
  row.innerHTML = `
    <div class="row-line row-line-three">
      <label class="inline-field">
        <span>Seccion</span>
        <select name="routine_section[]">
          ${routineSectionOptions()}
        </select>
      </label>
      <label class="inline-field grow">
        <span>Ejercicio</span>
        <input type="text" name="routine_name[]" placeholder="Ej: 1w6targets small" />
      </label>
      <label class="inline-field small">
        <span>Puntos</span>
        <input type="text" name="routine_points[]" placeholder="12340" />
      </label>
    </div>
    <div class="row-line row-line-four">
      <label class="inline-field small">
        <span>Min</span>
        <input type="text" name="routine_minutes[]" placeholder="12.5" />
      </label>
      <label class="inline-field small">
        <span>Accuracy %</span>
        <input type="text" name="routine_accuracy[]" placeholder="86.4" />
      </label>
      <label class="inline-field grow">
        <span>Notas</span>
        <input type="text" name="routine_notes[]" placeholder="Sensacion, foco, error concreto..." />
      </label>
      <button class="remove-row" type="button" title="Eliminar rutina">-</button>
    </div>
  `;

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
      <label class="inline-field grow">
        <span>Mapa / modo</span>
        <input type="text" name="match_map[]" placeholder="Ej: Ascent, DM, Ranked..." />
      </label>
      <label class="inline-field small">
        <span>Puntos</span>
        <input type="text" name="match_score[]" placeholder="0" />
      </label>
      <label class="inline-field small">
        <span>Resultado</span>
        <input type="text" name="match_result[]" placeholder="W / L / OT" />
      </label>
    </div>
    <div class="row-line row-line-six">
      <label class="inline-field small">
        <span>K</span>
        <input type="text" name="match_kills[]" placeholder="24" />
      </label>
      <label class="inline-field small">
        <span>D</span>
        <input type="text" name="match_deaths[]" placeholder="18" />
      </label>
      <label class="inline-field small">
        <span>A</span>
        <input type="text" name="match_assists[]" placeholder="5" />
      </label>
      <label class="inline-field small">
        <span>KDA</span>
        <input type="text" name="match_kda[]" placeholder="1.61" />
      </label>
      <label class="inline-field small">
        <span>HS %</span>
        <input type="text" name="match_hs[]" placeholder="28.4" />
      </label>
      <label class="inline-field grow">
        <span>Notas</span>
        <input type="text" name="match_notes[]" placeholder="Lectura, entradas, errores, etc." />
      </label>
      <button class="remove-row" type="button" title="Eliminar partida">-</button>
    </div>
  `;

  return row;
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

function renderChart() {
  const canvas = document.getElementById('progressChart');
  if (!canvas) {
    return;
  }

  const data = Array.isArray(window.__CHART_DATA__) ? window.__CHART_DATA__ : [];
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
  const points = data.map((item) => Number(item.points) || 0);
  const labels = data.map((item) => String(item.label || ''));
  const maxValue = Math.max(...points);
  const minValue = Math.min(...points);
  const range = Math.max(maxValue - minValue, 1);
  const stepX = points.length > 1 ? plotWidth / (points.length - 1) : 0;

  drawGrid(context, padding, width, height, 5);

  context.save();
  const fillGradient = context.createLinearGradient(0, padding.top, 0, padding.top + plotHeight);
  fillGradient.addColorStop(0, 'rgba(255, 70, 85, 0.28)');
  fillGradient.addColorStop(1, 'rgba(255, 70, 85, 0.03)');

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

  context.strokeStyle = '#ff4655';
  context.lineWidth = 4;
  context.lineJoin = 'round';
  context.lineCap = 'round';
  context.shadowColor = 'rgba(255, 70, 85, 0.28)';
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

    context.fillStyle = '#ff4655';
    context.beginPath();
    context.arc(x, y, 3, 0, Math.PI * 2);
    context.fill();
  });
  context.restore();

  context.fillStyle = '#ecf2ff';
  context.font = '700 22px "Space Grotesk", sans-serif';
  context.textAlign = 'left';
  context.fillText('Total de puntos por dia', 24, 30);

  context.fillStyle = '#9aa7c0';
  context.font = '600 16px "Space Grotesk", sans-serif';
  context.textAlign = 'center';

  labels.forEach((label, index) => {
    const x = padding.left + (stepX * index);
    context.fillText(label, x, padding.top + plotHeight + 28);
  });

  context.textAlign = 'right';
  context.fillText(formatNumber(maxValue), padding.left - 10, padding.top + 8);
  context.fillText(formatNumber(minValue), padding.left - 10, padding.top + plotHeight);
}

function setupTodayButton() {
  const button = document.getElementById('fillToday');
  const input = document.getElementById('sessionDate');

  if (!button || !input) {
    return;
  }

  button.addEventListener('click', () => {
    const today = new Date();
    const offset = today.getTimezoneOffset() * 60000;
    const localDate = new Date(today.getTime() - offset);
    input.value = localDate.toISOString().slice(0, 10);
  });
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

function formatNumber(value) {
  return new Intl.NumberFormat('es-ES').format(Math.round(Number(value) || 0));
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
