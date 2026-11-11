<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 */

class Migration_Add_default_barber_services extends EA_Migration
{
    /**
     * Add the default barber-shop categories and services.
     */
    public function up(): void
    {
        $catalog = [
            'Cắt & Tạo kiểu' => [
                ['Cắt tóc nam tiêu chuẩn', 30, 100000],
                ['Cắt tóc trẻ em (dưới 12 tuổi)', 25, 80000],
                ['Cắt tỉa viền & Chân tóc', 15, 50000],
                ['Kẻ line / Tạo hình hoa văn (Hair Tattoo)', 20, 50000],
            ],
            'Hóa chất (Uốn/Nhuộm/Ép)' => [
                ['Uốn xoăn Hàn Quốc / Uốn sóng', 60, 300000],
                ['Uốn Premlock / Afro', 90, 500000],
                ['Uốn phồng chân tóc', 45, 250000],
                ['Nhuộm phủ bạc / Màu tự nhiên', 45, 200000],
                ['Tẩy tóc & Nhuộm màu thời trang', 120, 500000],
                ['Ép Side (Side Down Perm)', 40, 150000],
            ],
            'Chăm sóc Râu & Mặt' => [
                ['Cạo râu & Bấm tỉa dáng râu', 20, 60000],
                ['Cạo mặt & Tẩy tế bào chết', 20, 50000],
                ['Lột mụn cám & Đắp mặt nạ', 20, 70000],
            ],
            'Gội & Thư giãn' => [
                ['Gội xả & Massage đầu cơ bản', 20, 50000],
                ['Gội đầu dưỡng sinh nam', 45, 150000],
                ['Ráy tai thư giãn', 30, 80000],
                ['Phục hồi tóc xơ rối (Hấp Keratin)', 45, 200000],
            ],
            'Gói Combo Tiết Kiệm' => [
                ['Combo Basic (Cắt + Gội + Sấy sáp)', 45, 130000],
                ['Combo Gentleman (Cắt + Gội + Cạo râu + Massage mặt)', 60, 180000],
                ['Combo Fashion (Cắt + Gội + Uốn hoặc Nhuộm + Ép side)', 120, 550000],
            ],
        ];

        $now = date('Y-m-d H:i:s');
        $service_ids = [];

        foreach ($catalog as $category_name => $services) {
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

            foreach ($services as [$name, $duration, $price]) {
                $service = $this->db
                    ->get_where('services', ['name' => $name, 'id_service_categories' => $category_id])
                    ->row_array();

                if (!$service) {
                    $this->db->insert('services', [
                        'name' => $name,
                        'duration' => $duration,
                        'price' => $price,
                        'currency' => 'VNĐ',
                        'id_service_categories' => $category_id,
                        'slot_interval' => 15,
                        'attendants_number' => 1,
                        'is_private' => 0,
                        'color' => '#7cbae8',
                        'create_datetime' => $now,
                        'update_datetime' => $now,
                    ]);
                    $service_ids[] = $this->db->insert_id();
                } else {
                    $service_ids[] = (int) $service['id'];
                }
            }
        }

        $providers = $this->db
            ->select('users.id')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles')
            ->where('roles.slug', DB_SLUG_PROVIDER)
            ->get()
            ->result_array();

        $provider_ids = array_column($providers, 'id');

        if (!$provider_ids || !$service_ids) {
            return;
        }

        $existing_assignments = $this->db
            ->select('id_users, id_services')
            ->where_in('id_users', $provider_ids)
            ->where_in('id_services', $service_ids)
            ->get('services_providers')
            ->result_array();

        $existing = [];

        foreach ($existing_assignments as $assignment) {
            $existing[$assignment['id_users'] . ':' . $assignment['id_services']] = true;
        }

        $assignments = [];

        foreach ($provider_ids as $provider_id) {
            foreach ($service_ids as $service_id) {
                if (!isset($existing[$provider_id . ':' . $service_id])) {
                    $assignments[] = [
                        'id_users' => $provider_id,
                        'id_services' => $service_id,
                    ];
                }
            }
        }

        if ($assignments) {
            $this->db->insert_batch('services_providers', $assignments);
        }
    }

    /**
     * Keep catalog entries on downgrade because they may have been edited or booked.
     */
    public function down(): void
    {
    }
}
