<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * ---------------------------------------------------------------------------- */

/**
 * Plugins Controller.
 *
 * Quan ly, cai dat va kich hoat cac goi Plugin/Template mo rong cho he thong.
 *
 * @package Controllers
 */
class Plugins extends EA_Controller
{
    /**
     * Thu muc luu tru cac plugin duoc cai dat.
     */
    protected string $plugins_path;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings_model');
        $this->load->model('services_model');
        $this->load->model('service_categories_model');
        $this->load->model('providers_model');

        $this->load->library('accounts');

        $this->plugins_path = APPPATH . 'plugins' . DIRECTORY_SEPARATOR;

        if (!is_dir($this->plugins_path)) {
            mkdir($this->plugins_path, 0777, true);
        }
    }

    /**
     * Trang quan tri Plugin: Hien thi danh sach plugin va form upload.
     */
    public function index(): void
    {
        method('get');

        session(['dest_url' => site_url('plugins')]);

        $user_id = session('user_id');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            if ($user_id) {
                abort(403, 'Forbidden');
            }

            redirect('login');
            return;
        }

        // Quet thu muc application/plugins/ de lay danh sach plugin da cai dat
        $installed_plugins = $this->get_installed_plugins();
        $active_plugin_id = setting('active_plugin', '');

        $flash_message = session('plugin_flash_message');
        $flash_type = session('plugin_flash_type') ?: 'success';

        // Xoa flash session sau khi da lay
        session(['plugin_flash_message' => null, 'plugin_flash_type' => null]);

        html_vars([
            'page_title' => 'Quản lý Plugin',
            'active_menu' => 'plugins',
            'user_display_name' => $this->accounts->get_user_display_name($user_id),
            'installed_plugins' => $installed_plugins,
            'active_plugin_id' => $active_plugin_id,
            'flash_message' => $flash_message,
            'flash_type' => $flash_type,
        ]);

        $this->load->view('pages/plugins');
    }

    /**
     * Xu ly tai len va cai dat Plugin tu file .ZIP.
     */
    public function upload(): void
    {
        method('post');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        if (empty($_FILES['plugin_zip']['name']) || $_FILES['plugin_zip']['error'] !== UPLOAD_ERR_OK) {
            session([
                'plugin_flash_message' => 'Vui lòng chọn một file .ZIP hợp lệ để tải lên.',
                'plugin_flash_type' => 'danger',
            ]);
            redirect('plugins');
            return;
        }

        $file_name = $_FILES['plugin_zip']['name'];
        $tmp_file = $_FILES['plugin_zip']['tmp_name'];
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($extension !== 'zip') {
            session([
                'plugin_flash_message' => 'Hệ thống chỉ hỗ trợ cài đặt plugin thông qua định dạng file nén .ZIP.',
                'plugin_flash_type' => 'danger',
            ]);
            redirect('plugins');
            return;
        }

        if (!class_exists('ZipArchive')) {
            session([
                'plugin_flash_message' => 'Máy chủ PHP chưa bật extension ZipArchive.',
                'plugin_flash_type' => 'danger',
            ]);
            redirect('plugins');
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp_file) === true) {
            $zip->extractTo($this->plugins_path);
            $zip->close();

            session([
                'plugin_flash_message' => 'Tải lên và cài đặt Plugin thành công! Bạn có thể kích hoạt plugin ngay bên dưới.',
                'plugin_flash_type' => 'success',
            ]);
        } else {
            session([
                'plugin_flash_message' => 'Không thể giải nén file ZIP. Vui lòng kiểm tra lại tính hợp lệ của file.',
                'plugin_flash_type' => 'danger',
            ]);
        }

        redirect('plugins');
    }

    /**
     * Kich hoat mot Plugin da cai dat.
     */
    public function activate(string $plugin_id): void
    {
        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $manifest_file = $this->plugins_path . $plugin_id . DIRECTORY_SEPARATOR . 'plugin.json';

        if (!file_exists($manifest_file)) {
            session([
                'plugin_flash_message' => 'Không tìm thấy file cấu hình plugin.json của plugin: ' . $plugin_id,
                'plugin_flash_type' => 'danger',
            ]);
            redirect('plugins');
            return;
        }

        $manifest = json_decode(file_get_contents($manifest_file), true);

        if (!$manifest) {
            session([
                'plugin_flash_message' => 'File plugin.json không đúng định dạng JSON.',
                'plugin_flash_type' => 'danger',
            ]);
            redirect('plugins');
            return;
        }

        // 1. Cap nhat thiet lap he thong (Setting)
        $settings_update = [
            'active_plugin' => $plugin_id,
        ];

        if (!empty($manifest['company_name'])) {
            $settings_update['company_name'] = $manifest['company_name'];
        }

        if (!empty($manifest['company_color'])) {
            $settings_update['company_color'] = $manifest['company_color'];
        }

        setting($settings_update);

        // 2. Dong bo danh muc va dich vu tu file services.json (neu co)
        $services_file = $this->plugins_path . $plugin_id . DIRECTORY_SEPARATOR . 'services.json';
        $synced_count = 0;

        if (file_exists($services_file)) {
            $catalog = json_decode(file_get_contents($services_file), true);

            if (is_array($catalog)) {
                $synced_count = $this->sync_catalog($catalog);
            }
        }

        $plugin_name = $manifest['name'] ?? $plugin_id;
        $msg = 'Đã kích hoạt thành công Plugin "' . $plugin_name . '"!';
        if ($synced_count > 0) {
            $msg .= ' Đồng thời đã đồng bộ ' . $synced_count . ' dịch vụ vào hệ thống.';
        }

        session([
            'plugin_flash_message' => $msg,
            'plugin_flash_type' => 'success',
        ]);

        redirect('plugins');
    }

    /**
     * Huy kich hoat Plugin hien tai.
     */
    public function deactivate(string $plugin_id): void
    {
        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        setting([
            'active_plugin' => '',
            'company_color' => '#35A768', // Tra ve mau xanh mac dinh
        ]);

        session([
            'plugin_flash_message' => 'Đã hủy kích hoạt plugin và đưa giao diện về trạng thái tiêu chuẩn.',
            'plugin_flash_type' => 'info',
        ]);

        redirect('plugins');
    }

    /**
     * Xoa hoan toan mot Plugin khoi he thong.
     */
    public function delete(string $plugin_id): void
    {
        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $folder = $this->plugins_path . $plugin_id;

        if (!is_dir($folder)) {
            session([
                'plugin_flash_message' => 'Plugin không tồn tại hoặc đã bị xóa trước đó.',
                'plugin_flash_type' => 'warning',
            ]);
            redirect('plugins');
            return;
        }

        // Neu plugin nay dang duoc kich hoat, tu dong huy kich hoat truoc khi xoa
        if (setting('active_plugin') === $plugin_id) {
            setting([
                'active_plugin' => '',
                'company_color' => '#35A768',
            ]);
        }

        // Xoa thu muc plugin
        $this->delete_directory($folder);

        session([
            'plugin_flash_message' => 'Đã xóa hoàn toàn plugin "' . $plugin_id . '" khỏi hệ thống.',
            'plugin_flash_type' => 'success',
        ]);

        redirect('plugins');
    }

    /**
     * Ham de quy xoa thu muc va toan bo tap tin con ben trong.
     */
    protected function delete_directory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (!$this->delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Quet thu muc de lay thong tin tat ca plugin da cai dat.
     */
    protected function get_installed_plugins(): array
    {
        $plugins = [];
        $folders = glob($this->plugins_path . '*', GLOB_ONLYDIR);

        foreach ($folders as $folder) {
            $manifest_file = $folder . DIRECTORY_SEPARATOR . 'plugin.json';

            if (file_exists($manifest_file)) {
                $manifest = json_decode(file_get_contents($manifest_file), true);

                if ($manifest && !empty($manifest['name'])) {
                    $plugin_id = $manifest['id'] ?? basename($folder);
                    $manifest['id'] = $plugin_id;
                    $manifest['folder'] = basename($folder);

                    // Dem so luong dich vu trong plugin
                    $services_file = $folder . DIRECTORY_SEPARATOR . 'services.json';
                    $manifest['service_count'] = 0;

                    if (file_exists($services_file)) {
                        $services_data = json_decode(file_get_contents($services_file), true);
                        if (is_array($services_data)) {
                            foreach ($services_data as $group) {
                                $manifest['service_count'] += count($group['services'] ?? []);
                            }
                        }
                    }

                    $plugins[$plugin_id] = $manifest;
                }
            }
        }

        return $plugins;
    }

    /**
     * Dong bo danh muc va dich vu vao Database.
     */
    protected function sync_catalog(array $catalog): int
    {
        $now = date('Y-m-d H:i:s');
        $new_service_ids = [];

        foreach ($catalog as $group) {
            $category_name = $group['category'] ?? 'Dịch vụ Barbershop';
            $services = $group['services'] ?? [];

            // Tim hoac tao moi Category
            $category = $this->db->get_where('service_categories', ['name' => $category_name])->row_array();

            if (!$category) {
                $this->db->insert('service_categories', [
                    'name' => $category_name,
                    'create_datetime' => $now,
                    'update_datetime' => $now,
                ]);
                $category_id = $this->db->insert_id();
            } else {
                $category_id = (int) $category['id'];
            }

            // Tim hoac them tung Service
            foreach ($services as $srv) {
                $service_name = $srv['name'];
                $existing = $this->db
                    ->get_where('services', [
                        'name' => $service_name,
                        'id_service_categories' => $category_id,
                    ])
                    ->row_array();

                if (!$existing) {
                    $this->db->insert('services', [
                        'name' => $service_name,
                        'duration' => $srv['duration'] ?? 30,
                        'price' => $srv['price'] ?? 0,
                        'currency' => 'VNĐ',
                        'id_service_categories' => $category_id,
                        'slot_interval' => 15,
                        'attendants_number' => 1,
                        'is_private' => 0,
                        'color' => $srv['color'] ?? '#c59b27',
                        'create_datetime' => $now,
                        'update_datetime' => $now,
                    ]);
                    $service_id = $this->db->insert_id();
                } else {
                    $service_id = (int) $existing['id'];
                }

                $new_service_ids[] = $service_id;
            }
        }

        // Gan toan bo cac dich vu tren cho tat ca Providers (Tho cat toc)
        if (!empty($new_service_ids)) {
            $providers = $this->db
                ->select('users.id')
                ->from('users')
                ->join('roles', 'roles.id = users.id_roles')
                ->where('roles.slug', DB_SLUG_PROVIDER)
                ->get()
                ->result_array();

            foreach ($providers as $provider) {
                foreach ($new_service_ids as $sid) {
                    $exists = $this->db
                        ->get_where('services_providers', [
                            'id_users' => $provider['id'],
                            'id_services' => $sid,
                        ])
                        ->num_rows();

                    if (!$exists) {
                        $this->db->insert('services_providers', [
                            'id_users' => $provider['id'],
                            'id_services' => $sid,
                        ]);
                    }
                }
            }
        }

        return count($new_service_ids);
    }
}

