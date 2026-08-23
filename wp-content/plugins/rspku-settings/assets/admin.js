(function ($) {
  'use strict';

  function getNextRepeaterIndex($container, name) {
    let maxIndex = -1;

    $container.find(`[name^="${name}["]`).each(function () {
      const match = String(this.name || '').match(/\[(\d+)]\[(label|time|highlight)]$/);
      if (match) maxIndex = Math.max(maxIndex, Number(match[1]));
    });

    return maxIndex + 1;
  }

  $(document).ready(function () {
    let settingsDirty = false;
    const unsavedMessage = 'Perubahan belum disimpan. Tinggalkan halaman ini?';

    $('.rspku-settings-form').on('input change', ':input', function () {
      settingsDirty = true;
    }).on('submit', function () {
      settingsDirty = false;
    });

    $(window).on('beforeunload', function (e) {
      if (!settingsDirty) return undefined;

      e.preventDefault();
      e.returnValue = unsavedMessage;
      return unsavedMessage;
    });

    $('.rspku-settings-tabs .nav-tab').on('click', function (e) {
      if (settingsDirty && !window.confirm(unsavedMessage)) {
        e.preventDefault();
      }
    });

    // Color picker
    $('.rspku-color-picker').wpColorPicker();

    // Image upload
    $(document).on('click', '.rspku-image-select', function (e) {
      e.preventDefault();
      const $container = $(this).closest('.rspku-image-upload');
      const $input = $container.find('input[type="hidden"]');
      const $preview = $container.find('.rspku-image-preview');
      const $img = $container.find('.rspku-image-preview-img');
      const $empty = $container.find('.rspku-image-empty');
      const $selectBtn = $(this);

      const frame = wp.media({
        title: 'Pilih Gambar',
        button: { text: 'Gunakan Gambar Ini' },
        multiple: false,
        library: { type: 'image' }
      });

      frame.on('select', function () {
        const attachment = frame.state().get('selection').first().toJSON();
        $input.val(attachment.id);
        $img.attr('src', attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url);
        $preview.removeClass('hidden');
        $empty.addClass('hidden');
        $selectBtn.addClass('hidden');
      });

      frame.open();
    });

    // Image remove
    $(document).on('click', '.rspku-image-remove', function (e) {
      e.preventDefault();
      const $container = $(this).closest('.rspku-image-upload');
      $container.find('input[type="hidden"]').val('0');
      $container.find('.rspku-image-preview-img').attr('src', '');
      $container.find('.rspku-image-preview').addClass('hidden');
      $container.find('.rspku-image-empty').removeClass('hidden');
      $container.find('.rspku-image-select').removeClass('hidden');
    });

    // Repeater: add row
    $(document).on('click', '.rspku-repeater-add', function () {
      const $container = $(this).closest('.rspku-repeater');
      const name = $(this).data('name');
      const index = getNextRepeaterIndex($container, name);

      const html = `
        <div class="rspku-repeater-row rspku-repeater-row--hours">
          <label class="rspku-repeater-cell">
            <span class="rspku-repeater-cell__label">Unit Layanan</span>
            <input type="text" name="${name}[${index}][label]" value="" placeholder="IGD" class="regular-text">
          </label>
          <label class="rspku-repeater-cell">
            <span class="rspku-repeater-cell__label">Jam Operasional</span>
            <input type="text" name="${name}[${index}][time]" value="" placeholder="24 Jam" class="regular-text">
          </label>
          <label class="rspku-repeater-highlight">
            <input type="checkbox" name="${name}[${index}][highlight]" value="1">
            <span>Tampilkan sebagai utama</span>
          </label>
          <button type="button" class="button-link-delete rspku-repeater-remove" aria-label="Hapus jam operasional">Hapus</button>
        </div>
      `;

      $container.find('.rspku-repeater-empty').remove();
      $(this).before(html);
    });

    // Repeater: remove row
    $(document).on('click', '.rspku-repeater-remove', function () {
      if (!confirm('Hapus baris ini?')) return;
      const $container = $(this).closest('.rspku-repeater');
      $(this).closest('.rspku-repeater-row').remove();

      if ($container.hasClass('rspku-repeater--hours') && !$container.find('.rspku-repeater-row').length) {
        $container.prepend('<p class="rspku-repeater-empty">Belum ada jam operasional. Tambahkan baris untuk mulai mengisi.</p>');
      }
    });

    // Repeater links: add row
    $(document).on('click', '.rspku-repeater-add-link', function () {
      const $container = $(this).closest('.rspku-repeater');
      const name = $(this).data('name');
      const index = $container.find('.rspku-repeater-row').length;

      const html = `
        <div class="rspku-repeater-row rspku-repeater-row--links">
          <input type="text" name="${name}[${index}][label]" value="" placeholder="Label (mis. Dokter)" aria-label="Label link cepat" class="regular-text">
          <input type="text" name="${name}[${index}][url]" value="" placeholder="URL (mis. /dokter/)" aria-label="URL link cepat" class="regular-text">
          <button type="button" class="button-link-delete rspku-repeater-remove" aria-label="Hapus link cepat">Hapus</button>
        </div>
      `;

      $(this).before(html);
    });

    // Review repeater: add row
    $(document).on('click', '.rspku-repeater-add-review', function () {
      const $container = $(this).closest('.rspku-repeater');
      const name = $(this).data('name');
      const index = $container.find('.rspku-repeater-row').length;

      const html = `
        <div class="rspku-repeater-row rspku-repeater-row--review">
          <input type="text" name="${name}[${index}][name]" value="" placeholder="Nama reviewer" aria-label="Nama reviewer" class="regular-text">
          <select name="${name}[${index}][rating]" aria-label="Rating ulasan" class="regular-text">
            <option value="5">5 ★</option>
            <option value="4">4 ★</option>
            <option value="3">3 ★</option>
            <option value="2">2 ★</option>
            <option value="1">1 ★</option>
          </select>
          <input type="text" name="${name}[${index}][date_label]" value="" placeholder="Bulan Tahun (mis. Maret 2026)" aria-label="Bulan dan tahun ulasan" class="regular-text">
          <textarea name="${name}[${index}][excerpt]" rows="2" placeholder="Kutipan ulasan..." aria-label="Kutipan ulasan" class="large-text"></textarea>
          <button type="button" class="button-link-delete rspku-repeater-remove" aria-label="Hapus ulasan">Hapus</button>
        </div>
      `;

      $(this).before(html);
    });

    // Section collapse: progressive enhancement only. Inputs stay in the DOM.
    $(document).on('click', '.rspku-settings-section-toggle', function () {
      const $button = $(this);
      const $section = $button.closest('.rspku-settings-section');
      const collapsed = !$section.hasClass('is-collapsed');

      $section.toggleClass('is-collapsed', collapsed);
      $button.attr('aria-expanded', collapsed ? 'false' : 'true');
      $button.text(collapsed ? 'Tampilkan' : 'Sembunyikan');
    });

  });
})(jQuery);
