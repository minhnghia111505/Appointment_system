<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 */

class Migration_Create_appointment_services_table extends EA_Migration
{
    /**
     * Store the services selected for each appointment, including booking-time price and duration.
     */
    public function up(): void
    {
        if ($this->db->table_exists('appointment_services')) {
            return;
        }

        $this->dbforge->add_field([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_appointments' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
            ],
            'id_services' => [
                'type' => 'BIGINT',
                'constraint' => '20',
                'unsigned' => true,
                'null' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
            ],
            'duration' => [
                'type' => 'INT',
                'constraint' => '11',
            ],
            'price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
        ]);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('id_appointments');
        $this->dbforge->add_key('id_services');
        $this->dbforge->create_table('appointment_services', true, ['engine' => 'InnoDB']);

        $this->db->query(
            '
            ALTER TABLE `' .
                $this->db->dbprefix('appointment_services') .
                '`
                ADD CONSTRAINT `appointment_services_appointments`
                    FOREIGN KEY (`id_appointments`) REFERENCES `' .
                $this->db->dbprefix('appointments') .
                '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                ADD CONSTRAINT `appointment_services_services`
                    FOREIGN KEY (`id_services`) REFERENCES `' .
                $this->db->dbprefix('services') .
                '` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
        ',
        );
    }

    /**
     * Remove the appointment service selections table.
     */
    public function down(): void
    {
        if ($this->db->table_exists('appointment_services')) {
            $this->dbforge->drop_table('appointment_services');
        }
    }
}
