<?php extend('layouts/backend_layout'); ?>

<?php section('content'); ?>

<div id="plugins-page" class="container backend-page py-4">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div>
            <h3 class="mb-1 text-primary fw-bold">
                <i class="fas fa-puzzle-piece me-2"></i>Quản lý Plugin & Gói Mở Rộng
            </h3>
            <p class="text-muted mb-0 small">
                Cài đặt và kích hoạt các gói giao diện, danh mục dịch vụ và cấu hình chuyên biệt cho hệ thống Easy!Appointments.
            </p>
        </div>
    </div>

    <!-- Thông báo kết quả thao tác -->
    <?php if (!empty(vars('flash_message'))): ?>
        <div class="alert alert-<?= vars('flash_type') ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <?= e(vars('flash_message')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <!-- Khung Upload Cài đặt Plugin mới -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="fas fa-file-upload text-primary me-2"></i>Cài đặt Plugin mới (.ZIP)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Tải lên file nén <code>.zip</code> chứa gói plugin (bao gồm <code>plugin.json</code> và <code>services.json</code>) để cài đặt vào hệ thống.
                    </p>

                    <form action="<?= site_url('plugins/upload') ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= config('csrf_token') ?>">
                        
                        <div class="mb-3">
                            <label for="plugin-zip" class="form-label small fw-semibold">Chọn file plugin (.zip):</label>
                            <input class="form-control" type="file" id="plugin-zip" name="plugin_zip" accept=".zip" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-cloud-upload-alt me-2"></i>Tải lên & Cài đặt
                            </button>
                        </div>
                    </form>

                    <div class="alert alert-light border mt-4 mb-0 small text-muted">
                        <i class="fas fa-lightbulb text-warning me-1"></i>
                        <strong>Mẹo:</strong> File plugin <code>barbershop.zip</code> đã được tạo sẵn trong thư mục <code>plugins_package/barbershop.zip</code> của dự án. Bạn có thể chọn file này để tải lên ngay!
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách Plugin đã cài đặt -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold">
                        <i class="fas fa-boxes text-success me-2"></i>Plugin đã cài đặt trên hệ thống
                    </h5>
                    <span class="badge bg-secondary"><?= count(vars('installed_plugins')) ?> Plugin</span>
                </div>
                <div class="card-body">
                    <?php if (empty(vars('installed_plugins'))): ?>
                        <div class="text-center py-5">
                            <div class="text-muted mb-3">
                                <i class="fas fa-box-open fa-3x"></i>
                            </div>
                            <h6 class="text-muted">Chưa có plugin nào được cài đặt.</h6>
                            <p class="text-muted small">Hãy chọn file <code>barbershop.zip</code> ở khung bên trái và bấm <strong>Tải lên & Cài đặt</strong>.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach (vars('installed_plugins') as $id => $plugin): ?>
                                <?php $is_active = (vars('active_plugin_id') === $id); ?>
                                <div class="col-12">
                                    <div class="border rounded p-3 <?= $is_active ? 'border-primary bg-primary bg-opacity-10' : 'bg-white' ?> shadow-sm transition">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                     style="width: 50px; height: 50px; background-color: <?= e($plugin['company_color'] ?? '#c59b27') ?>; color: white;">
                                                    <i class="<?= e($plugin['icon'] ?? 'fas fa-cut') ?> fa-lg"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0 fw-bold">
                                                        <?= e($plugin['name']) ?>
                                                        <span class="badge bg-dark small ms-2">v<?= e($plugin['version'] ?? '1.0.0') ?></span>
                                                    </h5>
                                                    <small class="text-muted">Tác giả: <?= e($plugin['author'] ?? 'N/A') ?> · Phân loại: <?= e($plugin['category'] ?? 'Ngành nghề') ?></small>
                                                </div>
                                            </div>

                                            <div>
                                                <?php if ($is_active): ?>
                                                    <span class="badge bg-success px-3 py-2">
                                                        <i class="fas fa-check-circle me-1"></i>Đang kích hoạt
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary px-3 py-2">
                                                        Chưa kích hoạt
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <p class="text-muted small my-3">
                                            <?= e($plugin['description']) ?>
                                        </p>

                                        <div class="d-flex flex-wrap gap-3 align-items-center text-muted small border-top pt-3 mb-3">
                                            <div>
                                                <i class="fas fa-store me-1 text-primary"></i>
                                                Thương hiệu: <strong><?= e($plugin['company_name'] ?? 'Mặc định') ?></strong>
                                            </div>
                                            <div>
                                                <i class="fas fa-palette me-1 text-warning"></i>
                                                Màu nhận diện: 
                                                <span class="d-inline-block rounded border ms-1" style="width: 14px; height: 14px; vertical-align: middle; background-color: <?= e($plugin['company_color'] ?? '#c59b27') ?>;"></span>
                                                <code><?= e($plugin['company_color'] ?? '#c59b27') ?></code>
                                            </div>
                                            <div>
                                                <i class="fas fa-concierge-bell me-1 text-info"></i>
                                                Gói dịch vụ: <strong><?= (int) $plugin['service_count'] ?> dịch vụ</strong>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="<?= site_url('plugins/delete/' . $id) ?>" 
                                               class="btn btn-outline-secondary btn-sm px-3"
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa hoàn toàn plugin này khỏi hệ thống không?');"
                                               title="Xóa plugin">
                                                <i class="fas fa-trash-alt me-1"></i>Xóa
                                            </a>

                                            <?php if ($is_active): ?>
                                                <a href="<?= site_url('plugins/deactivate/' . $id) ?>" class="btn btn-outline-danger btn-sm px-3">
                                                    <i class="fas fa-power-off me-1"></i>Hủy kích hoạt
                                                </a>
                                                <a href="<?= base_url() ?>" target="_blank" class="btn btn-primary btn-sm px-3">
                                                    <i class="fas fa-external-link-alt me-1"></i>Xem trang đặt lịch
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= site_url('plugins/activate/' . $id) ?>" class="btn btn-success btn-sm px-4">
                                                    <i class="fas fa-play me-1"></i>Kích hoạt Plugin
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

