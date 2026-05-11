<?php

declare(strict_types=1);

namespace Rspku\Fields;

final class DoctorFields
{
    public static function register(): void
    {
        add_action('add_meta_boxes', [self::class, 'registerMetaBox']);
        add_action('save_post_dokter', [self::class, 'save']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);

        self::registerPostMeta();
    }

    public static function registerMetaBox(): void
    {
        add_meta_box(
            'rspku-doctor-details',
            __('Detail Dokter', 'rspku-theme'),
            [self::class, 'renderMetaBox'],
            'dokter',
            'normal',
            'high'
        );
    }

    public static function enqueueAssets(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'dokter') {
            return;
        }

        $assetPath = RSPKU_THEME_PATH . '/public/build/manifest.json';
        if (!file_exists($assetPath)) {
            return;
        }
    }

    public static function renderMetaBox(\WP_Post $post): void
    {
        wp_nonce_field('rspku_doctor_fields', 'rspku_doctor_fields_nonce');

        $branches = self::branchOptions();
        $services = self::serviceOptions();
        $schedule = self::scheduleValue((int) $post->ID);
        $isSyncedFromSchedule = (string) get_post_meta((int) $post->ID, '_rspku_synced_from_schedule', true) === '1';

        echo '<div class="rspku-doctor-fields">';
        if ($isSyncedFromSchedule) {
            echo '<div style="margin-bottom:1rem;padding:1rem 1.125rem;border:1px solid #d7e3da;background:#f7fbf8;color:#166534;">';
            echo '<strong>' . esc_html__('Profil dokter ini tersinkron dari tabel Jadwal Dokter.', 'rspku-theme') . '</strong>';
            echo '<p style="margin:0.5rem 0 0;">' . esc_html__('Perubahan nama dokter, spesialisasi, dan jadwal praktik sebaiknya dilakukan dari tabel jadwal utama agar data profil tetap konsisten.', 'rspku-theme') . '</p>';
            echo '</div>';
        }
        self::fieldInput('rspku_degree', __('Gelar', 'rspku-theme'), self::value((int) $post->ID, '_rspku_degree'));
        self::fieldTextarea('rspku_doctor_biography', __('Biografi Singkat', 'rspku-theme'), self::value((int) $post->ID, '_rspku_doctor_biography', 'profil_dokter'));
        self::fieldInput('rspku_sub_specialization', __('Sub Spesialisasi', 'rspku-theme'), self::value((int) $post->ID, '_rspku_sub_specialization'));
        self::fieldInput('rspku_license', __('SIP / Nomor Izin', 'rspku-theme'), self::value((int) $post->ID, '_rspku_license'));
        self::fieldTextarea('rspku_education', __('Pendidikan', 'rspku-theme'), self::value((int) $post->ID, '_rspku_education', 'pendidikan_dokter'));
        self::fieldTextarea('rspku_experience', __('Pengalaman', 'rspku-theme'), self::value((int) $post->ID, '_rspku_experience', 'pengalaman_dokter'));
        self::fieldTextarea('rspku_training', __('Pelatihan', 'rspku-theme'), self::value((int) $post->ID, '_rspku_training', 'pelatihan_dokter'));
        self::fieldInput('rspku_appointment_url', __('URL Appointment', 'rspku-theme'), self::value((int) $post->ID, '_rspku_appointment_url'));
        self::fieldInput('rspku_consultation_type', __('Tipe Konsultasi', 'rspku-theme'), self::value((int) $post->ID, '_rspku_consultation_type'));
        self::fieldInput('rspku_social_instagram', __('Instagram', 'rspku-theme'), self::value((int) $post->ID, '_rspku_social_instagram'), 'url');
        self::fieldInput('rspku_social_facebook', __('Facebook', 'rspku-theme'), self::value((int) $post->ID, '_rspku_social_facebook'), 'url');
        self::fieldInput('rspku_social_linkedin', __('LinkedIn', 'rspku-theme'), self::value((int) $post->ID, '_rspku_social_linkedin'), 'url');
        self::fieldInput('rspku_social_whatsapp', __('WhatsApp', 'rspku-theme'), self::value((int) $post->ID, '_rspku_social_whatsapp'), 'url');
        self::fieldServiceSelect($services, (array) get_post_meta((int) $post->ID, '_rspku_related_service', false));

        echo '<hr style="margin: 1.5rem 0;">';
        echo '<h3 style="margin-top:0;">' . esc_html__('Jadwal Praktik', 'rspku-theme') . '</h3>';

        if ($isSyncedFromSchedule) {
            echo '<p style="margin-top:-0.25rem;color:#6b7280;">' . esc_html__('Jadwal praktik di bawah ini ditampilkan sebagai referensi dan akan diperbarui otomatis dari tabel jadwal dokter.', 'rspku-theme') . '</p>';
            echo '<table class="widefat striped" style="margin-bottom:1rem;">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Hari', 'rspku-theme') . '</th>';
            echo '<th>' . esc_html__('Mulai', 'rspku-theme') . '</th>';
            echo '<th>' . esc_html__('Selesai', 'rspku-theme') . '</th>';
            echo '<th>' . esc_html__('Ruangan', 'rspku-theme') . '</th>';
            echo '<th>' . esc_html__('Jenis Konsultasi', 'rspku-theme') . '</th>';
            echo '</tr></thead><tbody>';

            if ($schedule === []) {
                echo '<tr><td colspan="5">' . esc_html__('Jadwal praktik belum tersedia dari tabel sumber.', 'rspku-theme') . '</td></tr>';
            } else {
                foreach ($schedule as $row) {
                    echo '<tr>';
                    echo '<td>' . esc_html((string) ($row['day_label'] ?? self::days()[(string) ($row['day'] ?? '')] ?? '')) . '</td>';
                    echo '<td>' . esc_html((string) ($row['start_time'] ?? '')) . '</td>';
                    echo '<td>' . esc_html((string) ($row['end_time'] ?? '')) . '</td>';
                    echo '<td>' . esc_html((string) ($row['room'] ?? '-')) . '</td>';
                    echo '<td>' . esc_html((string) ($row['consultation_type'] ?? '-')) . '</td>';
                    echo '</tr>';
                }
            }

            echo '</tbody></table>';
        } else {
            echo '<p style="margin-top:-0.25rem;color:#6b7280;">' . esc_html__('Gunakan field terstruktur, bukan textarea. Setiap baris mewakili satu slot praktik.', 'rspku-theme') . '</p>';
            echo '<table class="widefat striped" style="margin-bottom:1rem;">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Hari', 'rspku-theme') . '</th>';
            echo '<th>' . esc_html__('Mulai', 'rspku-theme') . '</th>';
            echo '<th>' . esc_html__('Selesai', 'rspku-theme') . '</th>';
            echo '<th>' . esc_html__('Cabang', 'rspku-theme') . '</th>';
            echo '<th>' . esc_html__('Ruangan', 'rspku-theme') . '</th>';
            echo '<th>' . esc_html__('Jenis Konsultasi', 'rspku-theme') . '</th>';
            echo '<th></th>';
            echo '</tr></thead>';
            echo '<tbody data-rspku-schedule-rows>';

            if ($schedule === []) {
                echo self::scheduleRow('__INDEX__', [], $branches);
            } else {
                foreach (array_values($schedule) as $index => $row) {
                    echo self::scheduleRow((string) $index, $row, $branches);
                }
            }

            echo '</tbody>';
            echo '</table>';
            echo '<template data-rspku-schedule-template>' . self::scheduleRow('__INDEX__', [], $branches) . '</template>';
            echo '<p><button type="button" class="button button-secondary" data-rspku-add-schedule>' . esc_html__('Tambah Jadwal', 'rspku-theme') . '</button></p>';
        }
        echo '</div>';
    }

    public static function save(int $postId): void
    {
        if (!isset($_POST['rspku_doctor_fields_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rspku_doctor_fields_nonce'])), 'rspku_doctor_fields')) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($postId)) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        self::saveTextMeta($postId, '_rspku_degree', 'rspku_degree');
        self::saveTextMeta($postId, '_rspku_doctor_biography', 'rspku_doctor_biography');
        self::saveTextMeta($postId, '_rspku_sub_specialization', 'rspku_sub_specialization');
        self::saveTextMeta($postId, '_rspku_license', 'rspku_license');
        self::saveTextMeta($postId, '_rspku_education', 'rspku_education');
        self::saveTextMeta($postId, '_rspku_experience', 'rspku_experience');
        self::saveTextMeta($postId, '_rspku_training', 'rspku_training');
        self::saveTextMeta($postId, '_rspku_appointment_url', 'rspku_appointment_url', true);
        self::saveTextMeta($postId, '_rspku_consultation_type', 'rspku_consultation_type');
        self::saveTextMeta($postId, '_rspku_social_instagram', 'rspku_social_instagram', true);
        self::saveTextMeta($postId, '_rspku_social_facebook', 'rspku_social_facebook', true);
        self::saveTextMeta($postId, '_rspku_social_linkedin', 'rspku_social_linkedin', true);
        self::saveTextMeta($postId, '_rspku_social_whatsapp', 'rspku_social_whatsapp', true);

        $services = isset($_POST['rspku_related_services']) && is_array($_POST['rspku_related_services'])
            ? wp_parse_id_list(wp_unslash($_POST['rspku_related_services']))
            : [];
        update_post_meta($postId, '_rspku_related_services', $services);
        self::replaceIndexedMeta($postId, '_rspku_related_service', $services);

        $isSyncedFromSchedule = (string) get_post_meta($postId, '_rspku_synced_from_schedule', true) === '1';
        if (!$isSyncedFromSchedule && isset($_POST['rspku_doctor_schedule']) && is_array($_POST['rspku_doctor_schedule'])) {
            $schedule = self::sanitizeSchedule(wp_unslash($_POST['rspku_doctor_schedule']));
            update_post_meta($postId, '_rspku_doctor_schedule', $schedule);
            self::replaceIndexedMeta($postId, '_rspku_schedule_day', array_column($schedule, 'day'));
            self::replaceIndexedMeta($postId, '_rspku_schedule_branch', array_column($schedule, 'branch_id'));
        }
    }

    private static function registerPostMeta(): void
    {
        $textMeta = [
            '_rspku_degree',
            '_rspku_doctor_biography',
            '_rspku_sub_specialization',
            '_rspku_license',
            '_rspku_education',
            '_rspku_experience',
            '_rspku_training',
            '_rspku_appointment_url',
            '_rspku_consultation_type',
            '_rspku_social_instagram',
            '_rspku_social_facebook',
            '_rspku_social_linkedin',
            '_rspku_social_whatsapp',
        ];

        foreach ($textMeta as $key) {
            register_post_meta('dokter', $key, [
                'single' => true,
                'type' => 'string',
                'show_in_rest' => true,
                'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
            ]);
        }

        register_post_meta('dokter', '_rspku_related_services', [
            'single' => true,
            'type' => 'array',
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                ],
            ],
            'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
        ]);

        register_post_meta('dokter', '_rspku_doctor_schedule', [
            'single' => true,
            'type' => 'array',
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                    ],
                ],
            ],
            'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
        ]);
    }

    private static function fieldInput(string $name, string $label, string $value, string $type = 'text'): void
    {
        printf(
            '<p><label for="%1$s"><strong>%2$s</strong></label><input type="%4$s" class="widefat" id="%1$s" name="%1$s" value="%3$s"></p>',
            esc_attr($name),
            esc_html($label),
            esc_attr($value),
            esc_attr($type)
        );
    }

    private static function fieldTextarea(string $name, string $label, string $value): void
    {
        printf(
            '<p><label for="%1$s"><strong>%2$s</strong></label><textarea class="widefat" rows="4" id="%1$s" name="%1$s">%3$s</textarea></p>',
            esc_attr($name),
            esc_html($label),
            esc_textarea($value)
        );
    }

    /**
     * @param array<int,array<string,mixed>> $services
     * @param array<int,int|string> $selected
     */
    private static function fieldServiceSelect(array $services, array $selected): void
    {
        echo '<p><label for="rspku_related_services"><strong>' . esc_html__('Layanan Terkait', 'rspku-theme') . '</strong></label>';
        echo '<select id="rspku_related_services" name="rspku_related_services[]" class="widefat" multiple size="6">';

        foreach ($services as $service) {
            $id = (int) ($service['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            printf(
                '<option value="%1$d"%3$s>%2$s</option>',
                $id,
                esc_html((string) ($service['title'] ?? '')),
                selected(in_array($id, array_map('absint', $selected), true), true, false)
            );
        }

        echo '</select></p>';
    }

    /**
     * @param array<int,array<string,mixed>> $branches
     * @param array<string,mixed> $row
     */
    private static function scheduleRow(string $index, array $row, array $branches): string
    {
        $days = self::days();
        $day = sanitize_key((string) ($row['day'] ?? ''));
        $start = (string) ($row['start_time'] ?? '');
        $end = (string) ($row['end_time'] ?? '');
        $branchId = absint($row['branch_id'] ?? $row['branch'] ?? 0);
        $room = (string) ($row['room'] ?? '');
        $consultation = (string) ($row['consultation_type'] ?? '');

        ob_start();
        ?>
        <tr>
            <td>
                <select name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][day]">
                    <option value=""><?php echo esc_html__('Pilih hari', 'rspku-theme'); ?></option>
                    <?php foreach ($days as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($day, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="time" name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][start_time]" value="<?php echo esc_attr($start); ?>"></td>
            <td><input type="time" name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][end_time]" value="<?php echo esc_attr($end); ?>"></td>
            <td>
                <select name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][branch]">
                    <option value=""><?php echo esc_html__('Pilih cabang', 'rspku-theme'); ?></option>
                    <?php foreach ($branches as $branch) : ?>
                        <option value="<?php echo esc_attr((string) ($branch['id'] ?? 0)); ?>" <?php selected($branchId, (int) ($branch['id'] ?? 0)); ?>>
                            <?php echo esc_html((string) ($branch['title'] ?? '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="text" class="widefat" name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][room]" value="<?php echo esc_attr($room); ?>"></td>
            <td><input type="text" class="widefat" name="rspku_doctor_schedule[<?php echo esc_attr($index); ?>][consultation_type]" value="<?php echo esc_attr($consultation); ?>"></td>
            <td><button type="button" class="button-link-delete" data-rspku-remove-schedule><?php echo esc_html__('Hapus', 'rspku-theme'); ?></button></td>
        </tr>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function branchOptions(): array
    {
        $posts = get_posts([
            'post_type' => 'cabang-rs',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        return array_map(static fn (\WP_Post $post): array => [
            'id' => (int) $post->ID,
            'title' => get_the_title($post),
        ], $posts);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function serviceOptions(): array
    {
        $posts = get_posts([
            'post_type' => 'layanan',
            'post_status' => 'publish',
            'posts_per_page' => 300,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        return array_map(static fn (\WP_Post $post): array => [
            'id' => (int) $post->ID,
            'title' => get_the_title($post),
        ], $posts);
    }

    private static function value(int $postId, string $modernKey, string $legacyKey = ''): string
    {
        $value = get_post_meta($postId, $modernKey, true);
        if (($value === '' || $value === null) && $legacyKey !== '') {
            $value = function_exists('get_field') ? get_field($legacyKey, $postId) : get_post_meta($postId, $legacyKey, true);
        }

        if (is_array($value) || is_object($value)) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * @param array<string,mixed> $rows
     * @return array<int,array<string,mixed>>
     */
    private static function sanitizeSchedule(array $rows): array
    {
        $sanitized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $day = sanitize_key((string) ($row['day'] ?? ''));
            $start = self::time((string) ($row['start_time'] ?? ''));
            $end = self::time((string) ($row['end_time'] ?? ''));
            $branchId = absint($row['branch'] ?? $row['branch_id'] ?? 0);
            $room = sanitize_text_field((string) ($row['room'] ?? ''));
            $consultation = sanitize_text_field((string) ($row['consultation_type'] ?? ''));

            if ($day === '' && $start === '' && $end === '' && $branchId === 0 && $room === '' && $consultation === '') {
                continue;
            }

            if (!isset(self::days()[$day])) {
                continue;
            }

            $sanitized[] = [
                'day' => $day,
                'start_time' => $start,
                'end_time' => $end,
                'branch_id' => $branchId,
                'room' => $room,
                'consultation_type' => $consultation,
            ];
        }

        return array_values($sanitized);
    }

    private static function time(string $value): string
    {
        return preg_match('/^\d{2}:\d{2}$/', $value) ? $value : '';
    }

    /**
     * @return array<string,string>
     */
    private static function days(): array
    {
        return [
            'monday' => __('Senin', 'rspku-theme'),
            'tuesday' => __('Selasa', 'rspku-theme'),
            'wednesday' => __('Rabu', 'rspku-theme'),
            'thursday' => __('Kamis', 'rspku-theme'),
            'friday' => __('Jumat', 'rspku-theme'),
            'saturday' => __('Sabtu', 'rspku-theme'),
            'sunday' => __('Minggu', 'rspku-theme'),
        ];
    }

    /**
     * @param array<int,mixed> $values
     */
    private static function replaceIndexedMeta(int $postId, string $metaKey, array $values): void
    {
        delete_post_meta($postId, $metaKey);

        foreach (array_values(array_unique(array_filter(array_map('strval', $values)))) as $value) {
            add_post_meta($postId, $metaKey, $value, false);
        }
    }

    private static function saveTextMeta(int $postId, string $metaKey, string $fieldName, bool $isUrl = false): void
    {
        if (!isset($_POST[$fieldName])) {
            return;
        }

        $value = wp_unslash($_POST[$fieldName]);
        $value = $isUrl ? esc_url_raw((string) $value) : sanitize_textarea_field((string) $value);

        if ($value === '') {
            delete_post_meta($postId, $metaKey);
            return;
        }

        update_post_meta($postId, $metaKey, $value);
    }

    /**
     * @param array<int,mixed> $schedule
     * @return array<int,array<string,mixed>>
     */
    private static function scheduleValue(int $postId): array
    {
        $value = get_post_meta($postId, '_rspku_doctor_schedule', true);
        if (is_array($value) && $value !== []) {
            return $value;
        }

        $legacy = function_exists('get_field') ? get_field('jadwal_praktek', $postId) : get_post_meta($postId, 'jadwal_praktek', true);
        return is_array($legacy) ? $legacy : [];
    }
}
