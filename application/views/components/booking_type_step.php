<?php
/**
 * Local variables.
 *
 * @var array $available_services
 */
?>

<div id="wizard-frame-1" class="wizard-frame p-3 p-md-4" style="visibility: hidden;">
    <div class="frame-container py-3" style="min-height: 500px;">
        <h2 class="frame-title fw-light text-center mb-4 text-muted mt-md-5"><?= lang('service_and_provider') ?></h2>

        <div class="row frame-content">
            <div class="col col-lg-8 offset-md-2">
                <div class="mb-4">
                    <label for="booking-attendance-mode" class="fs-5 mb-2"><strong>Hình thức đặt lịch</strong></label>
                    <select id="booking-attendance-mode" class="form-select">
                        <option value="single">Đặt lịch cá nhân</option>
                        <option value="group">Đặt lịch nhóm (2-3 khách)</option>
                    </select>
                </div>

                <div id="booking-single-config">
                <div class="mb-3">
                    <label for="booking-package-mode" class="fs-5 mb-2">
                        <strong><?= lang('service') ?></strong>
                    </label>
                    <select id="booking-package-mode" class="form-select mb-3">
                        <option value="combo">Chọn combo</option>
                        <option value="individual">Tự chọn dịch vụ lẻ</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="select-service" class="fs-5 mb-2">
                        <strong>Gói chính</strong>
                    </label>

                    <select id="select-service" class="form-select mb-4">
                        <option value="">
                            <?= lang('please_select') ?>
                        </option>
                        <?php
                        // Group services by category, only if there is at least one service with a parent category.
                        $has_category = false;
                        foreach ($available_services as $service) {
                            if (!empty($service['service_category_id'])) {
                                $has_category = true;
                                break;
                            }
                        }

                        if ($has_category) {
                            $grouped_services = [];

                            foreach ($available_services as $service) {
                                if (!empty($service['service_category_id'])) {
                                    if (!isset($grouped_services[$service['service_category_name']])) {
                                        $grouped_services[$service['service_category_name']] = [];
                                    }

                                    $grouped_services[$service['service_category_name']][] = $service;
                                }
                            }

                            // We need the uncategorized services at the end of the list, so we will use another
                            // iteration only for the uncategorized services.
                            $grouped_services['uncategorized'] = [];
                            foreach ($available_services as $service) {
                                if ($service['service_category_id'] == null) {
                                    $grouped_services['uncategorized'][] = $service;
                                }
                            }

                            foreach ($grouped_services as $key => $group) {
                                $group_label =
                                    $key !== 'uncategorized' ? $group[0]['service_category_name'] : 'Uncategorized';

                                if (count($group) > 0) {
                                    echo '<optgroup label="' . e($group_label) . '">';
                                    foreach ($group as $service) {
                                        echo '<option value="' .
                                            $service['id'] .
                                            '" data-category="' .
                                            e($service['service_category_name'] ?? '') .
                                            '">' .
                                            e($service['name']) .
                                            '</option>';
                                    }
                                    echo '</optgroup>';
                                }
                            }
                        } else {
                            foreach ($available_services as $service) {
                                echo '<option value="' .
                                    $service['id'] .
                                    '" data-category="' .
                                    e($service['service_category_name'] ?? '') .
                                    '">' .
                                    e($service['name']) .
                                    '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div id="booking-addons-section" class="mb-4" hidden>
                    <h3 class="fs-5 mb-2">Dịch vụ bổ sung</h3>
                    <p class="text-muted small">Có thể chọn nhiều dịch vụ. Giá và thời gian được cộng vào gói chính.</p>
                    <div id="booking-addons" class="booking-addons-list"></div>
                </div>

                <div id="booking-package-summary" class="booking-package-summary mb-4 p-3 rounded border" aria-live="polite" hidden>
                    <strong>Tạm tính</strong>
                    <div id="booking-package-summary-items" class="small mt-2"></div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Tổng thời gian</span>
                        <strong id="booking-package-total-duration">0 phút</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Tổng tiền</span>
                        <strong id="booking-package-total-price">0 ₫</strong>
                    </div>
                </div>

                <div class="mb-3" hidden>
                    <label for="select-provider" class="fs-5 mb-2">
                        <strong><?= lang('provider') ?></strong>
                    </label>

                    <select id="select-provider" class="form-select mb-4">
                        <option value="">
                            <?= lang('please_select') ?>
                        </option>
                    </select>
                </div>

                <div id="service-description" class="small overflow-auto shadow-none" style="max-height: 153px;">
                    <!-- JS -->
                </div>
                </div>

                <section id="booking-group-config" class="mb-4" hidden>
                    <div class="mb-3">
                        <label for="booking-group-size" class="form-label fw-semibold">Số khách trong nhóm</label>
                        <select id="booking-group-size" class="form-select">
                            <option value="2">2 khách</option>
                            <option value="3">3 khách</option>
                        </select>
                    </div>
                    <div id="booking-group-guests" class="booking-group-guests"></div>
                    <div id="booking-group-summary" class="booking-package-summary mt-3 p-3 rounded border" aria-live="polite"></div>
                </section>

            </div>
        </div>
    </div>

    <div class="command-buttons text-center my-3 mx-auto d-md-flex justify-content-md-between">
        <span>&nbsp;</span>

        <button type="button" id="button-next-1" class="btn button-next btn-dark" style="min-width: 120px; margin-right: 10px;"
                data-step_index="1">
            <?= lang('next') ?>
            <i class="fas fa-chevron-right ms-2"></i>
        </button>
    </div>
</div>
