<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 */

class Migration_Create_group_bookings extends EA_Migration
{
    /**
     * Create group booking records and link their appointment details.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('group_bookings')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'auto_increment' => true,
                ],
                'id_users_customer' => [
                    'type' => 'INT',
                    'constraint' => '11',
                ],
                'booking_date' => [
                    'type' => 'DATE',
                ],
                'start_time' => [
                    'type' => 'TIME',
                ],
                'timezone' => [
                    'type' => 'VARCHAR',
                    'constraint' => '128',
                ],
                'total_guests' => [
                    'type' => 'TINYINT',
                    'constraint' => '3',
                    'unsigned' => true,
                ],
                'total_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                ],
                'create_datetime' => [
                    'type' => 'DATETIME',
                ],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_users_customer');
            $this->dbforge->create_table('group_bookings', true, ['engine' => 'InnoDB']);

            $this->db->query(
                '
                ALTER TABLE `' .
                    $this->db->dbprefix('group_bookings') .
                    '`
                ADD CONSTRAINT `group_bookings_customers`
                    FOREIGN KEY (`id_users_customer`) REFERENCES `' .
                    $this->db->dbprefix('users') .
                    '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ',
            );
        }

        if (!$this->db->field_exists('id_group_bookings', 'appointments')) {
            $this->dbforge->add_column('appointments', [
                'id_group_bookings' => [
                    'type' => 'INT',
                    'constraint' => '11',
                    'null' => true,
                ],
                'guest_index' => [
                    'type' => 'TINYINT',
                    'constraint' => '3',
                    'unsigned' => true,
                    'null' => true,
                ],
                'guest_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => '256',
                    'null' => true,
                ],
            ]);
            $this->db->query(
                'ALTER TABLE `' . $this->db->dbprefix('appointments') . '` ADD INDEX `appointments_group_booking_id` (`id_group_bookings`)',
            );
            $this->db->query(
                '
                ALTER TABLE `' .
                    $this->db->dbprefix('appointments') .
                    '`
                ADD CONSTRAINT `appointments_group_bookings`
                    FOREIGN KEY (`id_group_bookings`) REFERENCES `' .
                    $this->db->dbprefix('group_bookings') .
                    '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ',
            );
        }
    }

    /**
     * Remove group booking data structures.
     */
    public function down(): void
    {
        if ($this->db->field_exists('id_group_bookings', 'appointments')) {
            $this->db->query('ALTER TABLE `' . $this->db->dbprefix('appointments') . '` DROP FOREIGN KEY `appointments_group_bookings`');
            $this->db->query('ALTER TABLE `' . $this->db->dbprefix('appointments') . '` DROP INDEX `appointments_group_booking_id`');
            $this->dbforge->drop_column('appointments', 'guest_name');
            $this->dbforge->drop_column('appointments', 'guest_index');
            $this->dbforge->drop_column('appointments', 'id_group_bookings');
        }

        if ($this->db->table_exists('group_bookings')) {
            $this->db->query('ALTER TABLE `' . $this->db->dbprefix('group_bookings') . '` DROP FOREIGN KEY `group_bookings_customers`');
            $this->dbforge->drop_table('group_bookings');
        }
    }
}
