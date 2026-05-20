(function ($) {
  'use strict';

  $(document).ready(function () {
    // Color picker
    $('.rspku-color-picker').wpColorPicker();

    // Image upload
    $(document).on('click', '.rspku-image-select', function (e) {
      e.preventDefault();
      const $container = $(this).closest('.rspku-image-upload');
      const $input = $container.find('input[type="hidden"]');
      const $preview = $container.find('.rspku-image-preview');
      const $img = $container.find('.rspku-image-preview-img');
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
        $selectBtn.addClass('hidden');
      });

      frame.open();
    });

    // Image remove
    $(document).on('click', '.rspku-image-remove', function (e) {
      e.preventDefault();
      const $container = $(this).closest('.rspku-image-upload');
      $container.find('input[type="hidden"]').val('0');
      $container.find('.rspku-image-preview').addClass('hidden');
      $container.find('.rspku-image-select').removeClass('hidden');
    });

    // Repeater: add row
    $(document).on('click', '.rspku-repeater-add', function () {
      const $container = $(this).closest('.rspku-repeater');
      const name = $(this).data('name');
      const index = $container.find('.rspku-repeater-row').length;

      const html = `
        <div class="rspku-repeater-row">
          <input type="text" name="${name}[${index}][label]" value="" placeholder="Label (mis. IGD)">
          <input type="text" name="${name}[${index}][time]" value="" placeholder="Waktu (mis. 24 Jam)">
          <label class="rspku-repeater-highlight">
            <input type="checkbox" name="${name}[${index}][highlight]" value="1">
            <span>Highlight</span>
          </label>
          <button type="button" class="button-link-delete rspku-repeater-remove">Hapus</button>
        </div>
      `;

      $(this).before(html);
    });

    // Repeater: remove row
    $(document).on('click', '.rspku-repeater-remove', function () {
      if (!confirm('Hapus baris ini?')) return;
      $(this).closest('.rspku-repeater-row').remove();
    });

    // Repeater links: add row
    $(document).on('click', '.rspku-repeater-add-link', function () {
      const $container = $(this).closest('.rspku-repeater');
      const name = $(this).data('name');
      const index = $container.find('.rspku-repeater-row').length;

      const html = `
        <div class="rspku-repeater-row rspku-repeater-row--links">
          <input type="text" name="${name}[${index}][label]" value="" placeholder="Label (mis. Dokter)">
          <input type="text" name="${name}[${index}][url]" value="" placeholder="URL (mis. /dokter/)">
          <button type="button" class="button-link-delete rspku-repeater-remove">Hapus</button>
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
          <input type="text" name="${name}[${index}][name]" value="" placeholder="Nama reviewer">
          <select name="${name}[${index}][rating]">
            <option value="5">5 ★</option>
            <option value="4">4 ★</option>
            <option value="3">3 ★</option>
            <option value="2">2 ★</option>
            <option value="1">1 ★</option>
          </select>
          <input type="text" name="${name}[${index}][date_label]" value="" placeholder="Bulan Tahun (mis. Maret 2026)">
          <textarea name="${name}[${index}][excerpt]" rows="2" placeholder="Kutipan ulasan..."></textarea>
          <button type="button" class="button-link-delete rspku-repeater-remove">Hapus</button>
        </div>
      `;

      $(this).before(html);
    });

    // Post picker: search + select (debounced)
    let pickerTimer = null;
    $(document).on('input', '.rspku-post-picker-search', function () {
      const $input = $(this);
      clearTimeout(pickerTimer);
      pickerTimer = setTimeout(function () {
        const $picker = $input.closest('.rspku-post-picker');
        const $dropdown = $picker.find('.rspku-post-picker-dropdown');
        const postType = $picker.data('post-type');
        const query = $input.val().trim();

        if (query.length < 2) {
          $dropdown.hide().empty();
          return;
        }

        $dropdown.html('<div class="rspku-post-picker-empty">Mencari...</div>').show();

        $.ajax({
          url: ajaxurl,
          data: { action: 'rspku_search_posts', post_type: postType, q: query, _wpnonce: (typeof rspkuSettingsNonce !== 'undefined' ? rspkuSettingsNonce : '') },
          success: function (response) {
            if (!response.success || !response.data) {
              $dropdown.html('<div class="rspku-post-picker-empty">Gagal memuat</div>').show();
              return;
            }
            const currentIds = ($picker.find('.rspku-post-picker-value').val() || '').split(',').map(Number);
            let html = '';
            response.data.forEach(function (item) {
              if (currentIds.includes(item.id)) return;
              html += `<div class="rspku-post-picker-option" data-id="${item.id}" data-title="${item.title}">${item.title}</div>`;
            });
            $dropdown.html(html || '<div class="rspku-post-picker-empty">Tidak ditemukan</div>').show();
          },
          error: function () {
            $dropdown.html('<div class="rspku-post-picker-empty">Error — cek console</div>').show();
          }
        });
      }, 300);
    });

    $(document).on('click', '.rspku-post-picker-option', function () {
      const $picker = $(this).closest('.rspku-post-picker');
      const $value = $picker.find('.rspku-post-picker-value');
      const $selected = $picker.find('.rspku-post-picker-selected');
      const id = $(this).data('id');
      const title = $(this).data('title');

      const current = $value.val() ? $value.val().split(',').filter(Boolean) : [];
      current.push(String(id));
      $value.val(current.join(','));

      $selected.append(`<span class="rspku-post-picker-tag" data-id="${id}">${title}<button type="button" class="rspku-post-picker-remove" aria-label="Hapus">&times;</button></span>`);
      $picker.find('.rspku-post-picker-search').val('');
      $picker.find('.rspku-post-picker-dropdown').hide().empty();
    });

    $(document).on('click', '.rspku-post-picker-remove', function () {
      const $tag = $(this).closest('.rspku-post-picker-tag');
      const $picker = $tag.closest('.rspku-post-picker');
      const $value = $picker.find('.rspku-post-picker-value');
      const removeId = String($tag.data('id'));

      const current = $value.val().split(',').filter(v => v !== removeId && v !== '');
      $value.val(current.join(','));
      $tag.remove();
    });

    // Hide dropdown on outside click
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.rspku-post-picker').length) {
        $('.rspku-post-picker-dropdown').hide();
      }
    });
  });
})(jQuery);
