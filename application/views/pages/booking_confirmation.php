<?php extend('layouts/message_layout'); ?>

<?php section('content'); ?>

<div class="d-flex align-items-center justify-content-center min-vh-100-">
    <div class="text-center py-4 px-3">
        <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mx-auto mb-4" style="width: 100px; height: 100px;">
            <i class="fas fa-calendar-check fa-3x text-success"></i>
        </div>

        <h3 class="text-success fw-semibold mb-4"><?= lang('appointment_registered') ?></h3>

        <p class="fs-5 text-muted mb-1">
            <?= lang('appointment_details_was_sent_to_you') ?>
        </p>

        <p class="text-muted small mb-4">
            <?= lang('check_spam_folder') ?>
        </p>

        <?php if (!empty(vars('group_booking'))): ?>
        <div class="text-start border rounded p-3 mb-4">
            <h4 class="fs-5">Chi tiết đặt lịch nhóm</h4>
            <p class="mb-3">
                <?= e(vars('group_booking')['booking_date']) ?>
                · <?= e(substr(vars('group_booking')['start_time'], 0, 5)) ?>
                (<?= e(vars('group_booking')['timezone']) ?>)
                · <?= number_format((float) vars('group_booking')['total_amount'], 0, ',', '.') ?> ₫
            </p>
            <?php foreach (vars('group_appointments') as $group_appointment): ?>
            <div class="border-top py-2">
                <strong>Khách <?= (int) $group_appointment['guest_index'] ?>:
                    <?= e($group_appointment['guest_name'] ?: 'Khách') ?></strong>
                <div><?= e(implode(' + ', array_column($group_appointment['services'], 'name'))) ?></div>
                <div class="text-muted small">
                    <?= e($group_appointment['provider_name']) ?>
                    · <?= (int) $group_appointment['duration'] ?> phút
                    · <?= number_format((float) $group_appointment['price'], 0, ',', '.') ?> ₫
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mt-4">
            <a href="<?= base_url() ?>" class="btn btn-primary px-4 py-2">
                <i class="fas fa-calendar-alt me-2"></i>
                <?= lang('go_to_booking_page') ?>
            </a>

            <?php if (vars('display_add_to_google_calendar') === '1'): ?>
            <a href="<?= vars(
                'add_to_google_url',
            ) ?>" id="add-to-google-calendar" class="btn btn-outline-primary px-4 py-2" target="_blank">
                <i class="fab fa-google me-2"></i>
                <?= lang('add_to_google_calendar') ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<?php component('google_analytics_script', ['google_analytics_code' => vars('google_analytics_code')]); ?>
<?php component('matomo_analytics_script', [
    'matomo_analytics_url' => vars('matomo_analytics_url'),
    'matomo_analytics_site_id' => vars('matomo_analytics_site_id'),
]); ?>

<?php end_section('scripts'); ?>
