<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 */

class Migration_Add_default_barber_staff extends EA_Migration
{
    /**
     * Add the default barber staff as providers and assign their services.
     */
    public function up(): void
    {
        $staff = [
            [
                'employee_id' => 1,
                'name' => 'Nguyễn Văn Hùng',
                'title' => 'Master Barber (Thợ chính)',
                'experience' => '7 năm',
                'phone' => '0901234561',
                'services' => [
                    'Cắt tóc nam tiêu chuẩn',
                    'Cắt tỉa viền & Chân tóc',
                    'Kẻ line / Tạo hình hoa văn (Hair Tattoo)',
                    'Uốn xoăn Hàn Quốc / Uốn sóng',
                    'Uốn Premlock / Afro',
                    'Tẩy tóc & Nhuộm màu thời trang',
                    'Combo Basic (Cắt + Gội + Sấy sáp)',
                    'Combo Gentleman (Cắt + Gội + Cạo râu + Massage mặt)',
                    'Combo Fashion (Cắt + Gội + Uốn hoặc Nhuộm + Ép side)',
                ],
            ],
            [
                'employee_id' => 2,
                'name' => 'Trần Minh Tuấn',
                'title' => 'Senior Barber (Thợ chính)',
                'experience' => '5 năm',
                'phone' => '0901234562',
                'services' => [
                    'Cắt tóc nam tiêu chuẩn',
                    'Cắt tóc trẻ em (dưới 12 tuổi)',
                    'Cắt tỉa viền & Chân tóc',
                    'Uốn xoăn Hàn Quốc / Uốn sóng',
                    'Uốn phồng chân tóc',
                    'Nhuộm phủ bạc / Màu tự nhiên',
                    'Ép Side (Side Down Perm)',
                    'Combo Basic (Cắt + Gội + Sấy sáp)',
                    'Combo Gentleman (Cắt + Gội + Cạo râu + Massage mặt)',
                ],
            ],
            [
                'employee_id' => 3,
                'name' => 'Lê Hoàng Nam',
                'title' => 'Barber & Chemical Specialist (Chuyên gia Hóa chất)',
                'experience' => '4 năm',
                'phone' => '0901234563',
                'services' => [
                    'Cắt tóc nam tiêu chuẩn',
                    'Uốn xoăn Hàn Quốc / Uốn sóng',
                    'Uốn Premlock / Afro',
                    'Uốn phồng chân tóc',
                    'Nhuộm phủ bạc / Màu tự nhiên',
                    'Tẩy tóc & Nhuộm màu thời trang',
                    'Ép Side (Side Down Perm)',
                    'Phục hồi tóc xơ rối (Hấp Keratin)',
                    'Combo Fashion (Cắt + Gội + Uốn hoặc Nhuộm + Ép side)',
                ],
            ],
            [
                'employee_id' => 4,
                'name' => 'Pham Quốc Anh',
                'title' => 'Barber (Thợ cắt tóc)',
                'experience' => '3 năm',
                'phone' => '0901234564',
                'services' => [
                    'Cắt tóc nam tiêu chuẩn',
                    'Cắt tóc trẻ em (dưới 12 tuổi)',
                    'Cắt tỉa viền & Chân tóc',
                    'Cạo râu & Bấm tỉa dáng râu',
                    'Cạo mặt & Tẩy tế bào chết',
                    'Combo Basic (Cắt + Gội + Sấy sáp)',
                    'Combo Gentleman (Cắt + Gội + Cạo râu + Massage mặt)',
                ],
            ],
            [
                'employee_id' => 5,
                'name' => 'Vũ Đức Thắng',
                'title' => 'Junior Barber (Thợ trẻ)',
                'experience' => '2 năm',
                'phone' => '0901234565',
                'services' => [
                    'Cắt tóc nam tiêu chuẩn',
                    'Cắt tóc trẻ em (dưới 12 tuổi)',
                    'Cắt tỉa viền & Chân tóc',
                    'Ép Side (Side Down Perm)',
                    'Gội xả & Massage đầu cơ bản',
                    'Combo Basic (Cắt + Gội + Sấy sáp)',
                ],
            ],
            [
                'employee_id' => 6,
                'name' => 'Đặng Bảo Long',
                'title' => 'Beard & Shave Specialist (Chuyên gia Cạo râu & Skincare)',
                'experience' => '3 năm',
                'phone' => '0901234566',
                'services' => [
                    'Cạo râu & Bấm tỉa dáng râu',
                    'Cạo mặt & Tẩy tế bào chết',
                    'Lột mụn cám & Đắp mặt nạ',
                    'Gội xả & Massage đầu cơ bản',
                    'Ráy tai thư giãn',
                    'Combo Gentleman (Cắt + Gội + Cạo râu + Massage mặt)',
                ],
            ],
            [
                'employee_id' => 7,
                'name' => 'Hoàng Trọng Hiếu',
                'title' => 'Skincare & Massage Technician (Kỹ thuật viên Thư giãn)',
                'experience' => '2 năm',
                'phone' => '0901234567',
                'services' => [
                    'Cạo mặt & Tẩy tế bào chết',
                    'Lột mụn cám & Đắp mặt nạ',
                    'Gội xả & Massage đầu cơ bản',
                    'Gội đầu dưỡng sinh nam',
                    'Ráy tai thư giãn',
                    'Phục hồi tóc xơ rối (Hấp Keratin)',
                ],
            ],
        ];

        $provider_role = $this->db->get_where('roles', ['slug' => DB_SLUG_PROVIDER])->row_array();

        if (!$provider_role) {
            throw new RuntimeException('The provider role was not found in the database.');
        }

        $working_plan = $this->db->get_where('settings', ['name' => 'company_working_plan'])->row_array();
        $default_language = $this->db->get_where('settings', ['name' => 'default_language'])->row_array();
        $default_timezone = $this->db->get_where('settings', ['name' => 'default_timezone'])->row_array();
        $now = date('Y-m-d H:i:s');
        $service_ids = [];

        foreach ($staff as $employee) {
            foreach ($employee['services'] as $service_name) {
                if (isset($service_ids[$service_name])) {
                    continue;
                }

                $service = $this->db->get_where('services', ['name' => $service_name])->row_array();

                if (!$service) {
                    throw new RuntimeException('Default service not found: ' . $service_name);
                }

                $service_ids[$service_name] = (int) $service['id'];
            }
        }

        $this->db->trans_begin();

        try {
            foreach ($staff as $employee) {
                $provider = $this->db
                    ->get_where('users', ['phone_number' => $employee['phone'], 'id_roles' => $provider_role['id']])
                    ->row_array();

                if (!$provider) {
                    $name_parts = explode(' ', $employee['name']);
                    $first_name = array_shift($name_parts);
                    $last_name = implode(' ', $name_parts);
                    $salt = bin2hex(random_bytes(32));
                    $password = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT, ['cost' => 12]);
                    $username = 'barber_' . $employee['phone'];
                    $username_suffix = 1;

                    while ($this->db->get_where('user_settings', ['username' => $username])->num_rows()) {
                        $username = 'barber_' . $employee['phone'] . '_' . $username_suffix;
                        $username_suffix++;
                    }

                    if ($password === false) {
                        throw new RuntimeException('Could not generate a password for the default barber provider.');
                    }

                    $provider_data = [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $employee['phone'] . '@barbershop.invalid',
                        'phone_number' => $employee['phone'],
                        'timezone' => $default_timezone['value'] ?? date_default_timezone_get(),
                        'language' => $default_language['value'] ?? 'english',
                        'notes' => 'Mã nhân viên: ' .
                            $employee['employee_id'] .
                            "\nChức danh: " .
                            $employee['title'] .
                            "\nKinh nghiệm: " .
                            $employee['experience'],
                        'id_roles' => $provider_role['id'],
                        'create_datetime' => $now,
                        'update_datetime' => $now,
                    ];

                    if (!$this->db->insert('users', $provider_data)) {
                        throw new RuntimeException('Could not create default barber: ' . $employee['name']);
                    }

                    $provider_id = (int) $this->db->insert_id();

                    if (!$this->db->insert('user_settings', [
                        'id_users' => $provider_id,
                        'username' => $username,
                        'password' => $password,
                        'salt' => $salt,
                        'working_plan' => $working_plan['value'] ?? null,
                        'notifications' => 0,
                        'google_sync' => 0,
                        'sync_past_days' => 30,
                        'sync_future_days' => 90,
                        'calendar_view' => CALENDAR_VIEW_DEFAULT,
                    ])) {
                        throw new RuntimeException('Could not create settings for default barber: ' . $employee['name']);
                    }
                } else {
                    $provider_id = (int) $provider['id'];
                }

                foreach ($employee['services'] as $service_name) {
                    $service_id = $service_ids[$service_name];
                    $assignment = $this->db->get_where('services_providers', [
                        'id_users' => $provider_id,
                        'id_services' => $service_id,
                    ]);

                    if (!$assignment->num_rows() && !$this->db->insert('services_providers', [
                        'id_users' => $provider_id,
                        'id_services' => $service_id,
                    ])) {
                        throw new RuntimeException(
                            'Could not assign service "' . $service_name . '" to ' . $employee['name'],
                        );
                    }
                }
            }

            if (!$this->db->trans_status()) {
                throw new RuntimeException('Could not add the default barber staff.');
            }

            $this->db->trans_commit();
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * Keep default staff records on downgrade because they may have been edited or booked.
     */
    public function down(): void
    {
    }
}
