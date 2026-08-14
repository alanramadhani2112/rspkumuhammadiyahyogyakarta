import '../css/admin.css';

const addButtonSelector = '[data-rspku-add-schedule]';
const removeButtonSelector = '[data-rspku-remove-schedule]';
const rowsSelector = '[data-rspku-schedule-rows]';
const templateSelector = '[data-rspku-schedule-template]';

function getContainer(target) {
  return target.closest('.rspku-doctor-fields, [data-rspku-schedule-admin]');
}

function addRow(container) {
  const rows = container.querySelector(rowsSelector);
  const template = container.querySelector(templateSelector);

  if (!rows || !template) {
    return;
  }

  const index = String(Date.now());
  const markup = template.innerHTML.replaceAll('__INDEX__', index);
  rows.insertAdjacentHTML('beforeend', markup);
}

function removeRow(button) {
  const row = button.closest('tr');
  if (row) {
    row.remove();
  }
}

document.addEventListener('click', (event) => {
  const target = event.target;

  if (!(target instanceof HTMLElement)) {
    return;
  }

  if (target.matches(addButtonSelector)) {
    event.preventDefault();
    const container = getContainer(target);
    if (container) {
      addRow(container);
      container.dispatchEvent(new Event('rspku:schedule-change', { bubbles: true }));
    }
  }

  if (target.matches(removeButtonSelector)) {
    event.preventDefault();
    const container = getContainer(target);
    removeRow(target);
    container?.dispatchEvent(new Event('rspku:schedule-change', { bubbles: true }));
  }
});

document.addEventListener('DOMContentLoaded', () => {
  if (!document.body.classList.contains('rspku-admin-structured')) {
    return;
  }

  const yoastBox = document.querySelector('#wpseo_meta');
  const acfBoxes = Array.from(document.querySelectorAll('.acf-postbox'));

  if (yoastBox instanceof HTMLElement && acfBoxes.length > 0) {
    acfBoxes[acfBoxes.length - 1].after(yoastBox);
    yoastBox.classList.add('closed');
  }
});

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-rspku-schedule-admin-form]');
  const status = document.querySelector('#rspku-doctor-schedule-status');

  if (!(form instanceof HTMLFormElement) || !(status instanceof HTMLElement)) {
    return;
  }

  let isDirty = false;

  form.addEventListener('input', () => {
    isDirty = true;
  });

  form.addEventListener('change', () => {
    isDirty = true;
  });

  form.addEventListener('rspku:schedule-change', () => {
    isDirty = true;
  });

  form.addEventListener('submit', (event) => {
    const errors = Array.from(form.querySelectorAll('[data-rspku-schedule-rows] tr')).flatMap((row, index) => {
      const term = row.querySelector('select[name$="[specialization_term_id]"]')?.value || '';
      const day = row.querySelector('select[name$="[day]"]')?.value || '';
      const start = row.querySelector('input[name$="[start_time]"]')?.value || '';
      const end = row.querySelector('input[name$="[end_time]"]')?.value || '';

      if (!term && !day && !start && !end) {
        return [];
      }

      if (!term || !day || !start || !end) {
        return [`Slot ${index + 1}: spesialisasi, hari, jam mulai, jam selesai wajib diisi.`];
      }

      if (start >= end) {
        return [`Slot ${index + 1}: jam mulai harus lebih awal dari jam selesai.`];
      }

      return [];
    });

    if (errors.length === 0) {
      isDirty = false;
      return;
    }

    event.preventDefault();

    status.className = 'notice notice-error';
    status.innerHTML = `<p>${errors.join('<br>')}</p>`;
  });

  window.addEventListener('beforeunload', (event) => {
    if (!isDirty) {
      return;
    }

    event.preventDefault();
    event.returnValue = '';
  });
});
