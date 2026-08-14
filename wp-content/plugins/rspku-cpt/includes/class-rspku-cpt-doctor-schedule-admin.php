<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class RSPKU_CPT_DoctorScheduleAdmin
{
    private const PAGE_SLUG = 'rspku-doctor-schedule';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_post_rspku_save_doctor_schedule', [self::class, 'save']);
    }

    public static function registerPage(): void
    {
        add_submenu_page(
            'edit.php?post_type=dokter',
            __('Jadwal Dokter', 'rspku-theme'),
            __('Jadwal Dokter', 'rspku-theme'),
            'edit_posts',
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('Anda tidak memiliki izin mengakses halaman ini.', 'rspku-theme'));
        }

        $doctors = self::doctors();
        $terms = self::specializationTerms();
        $selectedDoctorId = self::selectedDoctorId($doctors);
        $selectedSchedule = $selectedDoctorId > 0 ? self::schedule($selectedDoctorId) : [];
        $notice = self::notice();

        echo '<div class="wrap rspku-doctor-schedule-admin" data-rspku-schedule-admin>';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Jadwal Dokter', 'rspku-theme') . '</h1> ';
        printf(
            '<a class="page-title-action" href="%s">%s</a>',
            esc_url(admin_url('edit-tags.php?taxonomy=spesialisasi-dokter&post_type=dokter')),
            esc_html__('Kelola Spesialisasi', 'rspku-theme')
        );
        echo '<hr class="wp-header-end">';
        echo '<div id="rspku-doctor-schedule-status" class="notice ' . esc_attr($notice['class']) . '" role="status" aria-live="polite"><p>' . esc_html($notice['message']) . '</p></div>';

        self::renderOverview($doctors);
        self::renderEditor($doctors, $terms, $selectedDoctorId, $selectedSchedule);

        echo '</div>';
    }

    /**
     * @param array<int,WP_Post> $doctors
     */
    private static function renderOverview(array $doctors): void
    {
        echo '<h2>' . esc_html__('Ringkasan Jadwal', 'rspku-theme') . '</h2>';
        echo '<table class="widefat striped"><caption class="screen-reader-text">' . esc_html__('Daftar dokter dan ringkasan jadwal praktik', 'rspku-theme') . '</caption>';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('Dokter', 'rspku-theme') . '</th>';
        echo '<th scope="col">' . esc_html__('Spesialisasi', 'rspku-theme') . '</th>';
        echo '<th scope="col">' . esc_html__('Hari/Jam', 'rspku-theme') . '</th>';
        echo '<th scope="col">' . esc_html__('Status', 'rspku-theme') . '</th>';
        echo '<th scope="col">' . esc_html__('Aksi', 'rspku-theme') . '</th>';
        echo '</tr></thead><tbody>';

        if ($doctors === []) {
            echo '<tr><td colspan="5">' . esc_html__('Belum ada dokter. Tambahkan dokter dari menu Dokter sebelum mengatur jadwal.', 'rspku-theme') . '</td></tr>';
        }

        foreach ($doctors as $doctor) {
            $doctorId = (int) $doctor->ID;
            $schedule = self::schedule($doctorId);
            $editUrl = add_query_arg([
                'post_type' => 'dokter',
                'page' => self::PAGE_SLUG,
                'doctor_id' => $doctorId,
            ], admin_url('edit.php'));

            echo '<tr>';
            echo '<th scope="row"><a href="' . esc_url(get_edit_post_link($doctorId, '')) . '">' . esc_html(get_the_title($doctor)) . '</a></th>';
            echo '<td>' . esc_html(self::termList($doctorId)) . '</td>';
            echo '<td>' . esc_html(self::scheduleSummary($schedule)) . '</td>';
            echo '<td>' . esc_html($schedule === [] ? __('Belum ada jadwal', 'rspku-theme') : sprintf(_n('%d slot', '%d slot', count($schedule), 'rspku-theme'), count($schedule))) . '</td>';
            echo '<td><a class="button" href="' . esc_url($editUrl) . '">' . esc_html__('Edit Jadwal', 'rspku-theme') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * @param array<int,WP_Post> $doctors
     * @param array<int,WP_Term> $terms
     * @param array<int,array<string,mixed>> $schedule
     */
    private static function renderEditor(array $doctors, array $terms, int $selectedDoctorId, array $schedule): void
    {
        echo '<h2>' . esc_html__('Editor Jadwal', 'rspku-theme') . '</h2>';

        if ($doctors === [] || $terms === []) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html__('Editor membutuhkan minimal satu dokter dan satu spesialisasi yang sudah ada.', 'rspku-theme') . '</p></div>';
            return;
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" data-rspku-schedule-admin-form aria-describedby="rspku-doctor-schedule-status">';
        echo '<input type="hidden" name="action" value="rspku_save_doctor_schedule">';
        wp_nonce_field('rspku_doctor_schedule_admin', 'rspku_doctor_schedule_nonce');

        echo '<table class="form-table" role="presentation"><tbody><tr>';
        echo '<th scope="row"><label for="rspku_schedule_doctor_id">' . esc_html__('Dokter', 'rspku-theme') . '</label></th><td>';
        echo '<select id="rspku_schedule_doctor_id" name="rspku_schedule_doctor_id">';
        foreach ($doctors as $doctor) {
            printf('<option value="%1$d"%3$s>%2$s</option>', (int) $doctor->ID, esc_html(get_the_title($doctor)), selected($selectedDoctorId, (int) $doctor->ID, false));
        }
        echo '</select> <span class="description">' . esc_html__('Memuat dokter yang sudah ada saja.', 'rspku-theme') . '</span></td></tr></tbody></table>';

        echo '<table class="widefat striped"><caption class="screen-reader-text">' . esc_html__('Slot jadwal dokter', 'rspku-theme') . '</caption>';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('Spesialisasi', 'rspku-theme') . '</th>';
        echo '<th scope="col">' . esc_html__('Hari', 'rspku-theme') . '</th>';
        echo '<th scope="col">' . esc_html__('Mulai', 'rspku-theme') . '</th>';
        echo '<th scope="col">' . esc_html__('Selesai', 'rspku-theme') . '</th>';
        echo '<th scope="col">' . esc_html__('Catatan', 'rspku-theme') . '</th>';
        echo '<th scope="col">' . esc_html__('Aksi', 'rspku-theme') . '</th>';
        echo '</tr></thead><tbody data-rspku-schedule-rows>';

        if ($schedule === []) {
            echo self::scheduleRow('__INDEX__', [], $terms);
        } else {
            foreach (array_values($schedule) as $index => $row) {
                echo self::scheduleRow((string) $index, $row, $terms);
            }
        }

        echo '</tbody></table>';
        echo '<template data-rspku-schedule-template>' . self::scheduleRow('__INDEX__', [], $terms) . '</template>';
        echo '<p><button type="button" class="button button-secondary" data-rspku-add-schedule>' . esc_html__('Tambah Slot', 'rspku-theme') . '</button> ';
        echo '<button type="submit" class="button button-primary">' . esc_html__('Simpan Jadwal', 'rspku-theme') . '</button></p>';
        echo '</form>';
    }

    public static function save(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('Anda tidak memiliki izin menyimpan jadwal dokter.', 'rspku-theme'));
        }

        check_admin_referer('rspku_doctor_schedule_admin', 'rspku_doctor_schedule_nonce');

        $doctorId = absint($_POST['rspku_schedule_doctor_id'] ?? 0);
        if ($doctorId <= 0 || get_post_type($doctorId) !== 'dokter' || !current_user_can('edit_post', $doctorId)) {
            self::setNotice('error', __('Dokter tidak valid atau tidak dapat diedit.', 'rspku-theme'));
            self::redirect($doctorId);
        }

        $postedRows = isset($_POST['rspku_doctor_schedule']) && is_array($_POST['rspku_doctor_schedule'])
            ? wp_unslash($_POST['rspku_doctor_schedule'])
            : [];
        $postedRows = array_values(array_filter($postedRows, static fn ($row): bool => is_array($row) && !self::isEmptyRow($row)));
        $validated = RSPKU_CPT_DoctorSchedule::validateRows($postedRows);

        if ($validated['errors'] !== []) {
            self::setNotice('error', self::errorMessage($validated['errors']));
            self::redirect($doctorId);
        }

        foreach ($validated['rows'] as $index => $row) {
            if (absint($row['specialization_term_id'] ?? 0) <= 0) {
                self::setNotice('error', sprintf(
                    /* translators: %d: row number. */
                    __('Jadwal belum disimpan. Baris %d: spesialisasi wajib dipilih.', 'rspku-theme'),
                    $index + 1
                ));
                self::redirect($doctorId);
            }
        }

        self::persistSchedule($doctorId, $validated['rows']);
        self::setNotice('success', $validated['rows'] === [] ? __('Jadwal dikosongkan. Profil dokter tetap publish.', 'rspku-theme') : __('Jadwal dokter tersimpan.', 'rspku-theme'));
        self::redirect($doctorId);
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,WP_Term> $terms
     */
    private static function scheduleRow(string $index, array $row, array $terms): string
    {
        $selectedTermId = absint($row['specialization_term_id'] ?? 0);
        $day = sanitize_key((string) ($row['day'] ?? ''));

        ob_start();
        ?>
        <tr>
            <td>
                <label class="screen-reader-text" for="rspku_schedule_term_<?php echo esc_attr($index); ?>"><?php echo esc_html__('Spesialisasi', 'rspku-theme'); ?></label>
                <select id="rspku_schedule_term_<?php echo esc_attr($index); ?>" name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][specialization_term_id]">
                    <option value=""><?php echo esc_html__('Pilih spesialisasi', 'rspku-theme'); ?></option>
                    <?php foreach ($terms as $term) : ?>
                        <option value="<?php echo esc_attr((string) $term->term_id); ?>" <?php selected($selectedTermId, (int) $term->term_id); ?>><?php echo esc_html($term->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <label class="screen-reader-text" for="rspku_schedule_day_<?php echo esc_attr($index); ?>"><?php echo esc_html__('Hari', 'rspku-theme'); ?></label>
                <select id="rspku_schedule_day_<?php echo esc_attr($index); ?>" name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][day]">
                    <option value=""><?php echo esc_html__('Pilih hari', 'rspku-theme'); ?></option>
                    <?php foreach (RSPKU_CPT_DoctorSchedule::days() as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($day, $value); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><label class="screen-reader-text" for="rspku_schedule_start_<?php echo esc_attr($index); ?>"><?php echo esc_html__('Jam mulai', 'rspku-theme'); ?></label><input id="rspku_schedule_start_<?php echo esc_attr($index); ?>" type="time" name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][start_time]" value="<?php echo esc_attr((string) ($row['start_time'] ?? '')); ?>"></td>
            <td><label class="screen-reader-text" for="rspku_schedule_end_<?php echo esc_attr($index); ?>"><?php echo esc_html__('Jam selesai', 'rspku-theme'); ?></label><input id="rspku_schedule_end_<?php echo esc_attr($index); ?>" type="time" name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][end_time]" value="<?php echo esc_attr((string) ($row['end_time'] ?? '')); ?>"></td>
            <td><label class="screen-reader-text" for="rspku_schedule_note_<?php echo esc_attr($index); ?>"><?php echo esc_html__('Catatan', 'rspku-theme'); ?></label><input id="rspku_schedule_note_<?php echo esc_attr($index); ?>" type="text" class="regular-text" name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][note]" value="<?php echo esc_attr((string) ($row['note'] ?? '')); ?>"></td>
            <td><button type="button" class="button-link-delete" data-rspku-remove-schedule><?php echo esc_html__('Hapus slot', 'rspku-theme'); ?></button></td>
        </tr>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * @return array<int,WP_Post>
     */
    private static function doctors(): array
    {
        return get_posts([
            'post_type' => 'dokter',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 300,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
    }

    /**
     * @return array<int,WP_Term>
     */
    private static function specializationTerms(): array
    {
        $terms = get_terms([
            'taxonomy' => 'spesialisasi-dokter',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        return is_array($terms) ? array_values(array_filter($terms, static fn ($term): bool => $term instanceof WP_Term)) : [];
    }

    /**
     * @param array<int,WP_Post> $doctors
     */
    private static function selectedDoctorId(array $doctors): int
    {
        $requested = absint($_GET['doctor_id'] ?? 0);
        foreach ($doctors as $doctor) {
            if ((int) $doctor->ID === $requested) {
                return $requested;
            }
        }

        return isset($doctors[0]) ? (int) $doctors[0]->ID : 0;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function schedule(int $doctorId): array
    {
        $schedule = get_post_meta($doctorId, RSPKU_CPT_DoctorSchedule::META_KEY, true);

        return is_array($schedule) ? RSPKU_CPT_DoctorSchedule::sanitizeRows($schedule) : [];
    }

    /**
     * @param array<int,array<string,mixed>> $schedule
     */
    private static function scheduleSummary(array $schedule): string
    {
        if ($schedule === []) {
            return __('Belum tersedia', 'rspku-theme');
        }

        return implode(', ', array_map(static function (array $row): string {
            $day = (string) ($row['day_label'] ?? RSPKU_CPT_DoctorSchedule::days()[(string) ($row['day'] ?? '')] ?? '');
            $time = trim((string) ($row['start_time'] ?? '') . '-' . (string) ($row['end_time'] ?? ''), '-');

            return trim($day . ' ' . $time);
        }, $schedule));
    }

    private static function termList(int $doctorId): string
    {
        $terms = wp_get_post_terms($doctorId, 'spesialisasi-dokter', ['fields' => 'names']);

        return is_array($terms) && $terms !== [] ? implode(', ', array_map('strval', $terms)) : __('Belum ada', 'rspku-theme');
    }

    /**
     * @param array<string,mixed> $row
     */
    private static function isEmptyRow(array $row): bool
    {
        foreach (['specialization_term_id', 'day', 'start_time', 'end_time', 'note'] as $key) {
            if (trim((string) ($row[$key] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private static function persistSchedule(int $doctorId, array $rows): void
    {
        if ($rows === []) {
            delete_post_meta($doctorId, RSPKU_CPT_DoctorSchedule::META_KEY);
            delete_post_meta($doctorId, RSPKU_CPT_DoctorSchedule::LEGACY_META_KEY);
            delete_post_meta($doctorId, RSPKU_CPT_DoctorSchedule::DAY_INDEX_META_KEY);
            self::syncManagedTerms($doctorId, []);
            self::flushCaches($doctorId);

            return;
        }

        update_post_meta($doctorId, RSPKU_CPT_DoctorSchedule::META_KEY, $rows);
        update_post_meta($doctorId, RSPKU_CPT_DoctorSchedule::LEGACY_META_KEY, $rows);
        self::replaceIndexedMeta($doctorId, RSPKU_CPT_DoctorSchedule::DAY_INDEX_META_KEY, array_column($rows, 'day'));
        self::syncManagedTerms($doctorId, self::termIds($rows));
        self::flushCaches($doctorId);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,int>
     */
    private static function termIds(array $rows): array
    {
        $termIds = [];
        foreach ($rows as $row) {
            $termId = absint($row['specialization_term_id'] ?? 0);
            if ($termId <= 0 || !term_exists($termId, 'spesialisasi-dokter')) {
                continue;
            }

            $termIds[] = $termId;
        }

        return array_values(array_unique($termIds));
    }

    /**
     * @param array<int,int> $newManagedTerms
     */
    private static function syncManagedTerms(int $doctorId, array $newManagedTerms): void
    {
        $oldManagedTerms = wp_parse_id_list(get_post_meta($doctorId, RSPKU_CPT_DoctorSchedule::MANAGED_TERMS_META_KEY, true));
        $currentTerms = wp_get_post_terms($doctorId, 'spesialisasi-dokter', ['fields' => 'ids']);
        $currentTerms = is_array($currentTerms) ? wp_parse_id_list($currentTerms) : [];
        $curatedTerms = array_values(array_diff($currentTerms, $oldManagedTerms));
        $mergedTerms = array_values(array_unique(array_merge($curatedTerms, $newManagedTerms)));

        wp_set_object_terms($doctorId, $mergedTerms, 'spesialisasi-dokter', false);

        if ($newManagedTerms === []) {
            delete_post_meta($doctorId, RSPKU_CPT_DoctorSchedule::MANAGED_TERMS_META_KEY);

            return;
        }

        update_post_meta($doctorId, RSPKU_CPT_DoctorSchedule::MANAGED_TERMS_META_KEY, $newManagedTerms);
    }

    /**
     * @param array<int,string> $values
     */
    private static function replaceIndexedMeta(int $postId, string $metaKey, array $values): void
    {
        delete_post_meta($postId, $metaKey);
        foreach (array_values(array_unique(array_filter(array_map('strval', $values)))) as $value) {
            add_post_meta($postId, $metaKey, $value, false);
        }
    }

    private static function flushCaches(int $doctorId): void
    {
        clean_post_cache($doctorId);

        if (class_exists('Rspku\\Repositories\\DoctorRepository')) {
            \Rspku\Repositories\DoctorRepository::flushCache($doctorId);
        }
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     */
    private static function errorMessage(array $errors): string
    {
        $first = $errors[0] ?? [];
        $row = isset($first['row']) ? ((int) $first['row'] + 1) : 1;

        return sprintf(
            /* translators: %1$d: row number, %2$s: validation message. */
            __('Jadwal belum disimpan. Baris %1$d: %2$s', 'rspku-theme'),
            $row,
            (string) ($first['message'] ?? __('data tidak valid', 'rspku-theme'))
        );
    }

    private static function setNotice(string $type, string $message): void
    {
        set_transient(self::noticeKey(), ['type' => $type, 'message' => $message], 60);
    }

    /**
     * @return array{class:string,message:string}
     */
    private static function notice(): array
    {
        $notice = get_transient(self::noticeKey());
        delete_transient(self::noticeKey());

        if (is_array($notice) && isset($notice['type'], $notice['message'])) {
            return [
                'class' => (string) $notice['type'] === 'error' ? 'notice-error' : 'notice-success',
                'message' => (string) $notice['message'],
            ];
        }

        return [
            'class' => 'notice-info',
            'message' => __('Editor jadwal native siap.', 'rspku-theme'),
        ];
    }

    private static function noticeKey(): string
    {
        return 'rspku_doctor_schedule_notice_' . get_current_user_id();
    }

    private static function redirect(int $doctorId): void
    {
        wp_safe_redirect(add_query_arg([
            'post_type' => 'dokter',
            'page' => self::PAGE_SLUG,
            'doctor_id' => max(0, $doctorId),
        ], admin_url('edit.php')));
        exit;
    }
}
