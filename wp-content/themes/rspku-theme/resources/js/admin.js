import '../css/admin.css';

const addButtonSelector = '[data-rspku-add-schedule]';
const removeButtonSelector = '[data-rspku-remove-schedule]';
const rowsSelector = '[data-rspku-schedule-rows]';
const templateSelector = '[data-rspku-schedule-template]';

function getContainer(target) {
  return target.closest('.rspku-doctor-fields');
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
    }
  }

  if (target.matches(removeButtonSelector)) {
    event.preventDefault();
    removeRow(target);
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
