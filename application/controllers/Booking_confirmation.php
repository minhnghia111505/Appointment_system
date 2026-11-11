<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Booking confirmation controller.
 *
 * Handles the booking confirmation related operations.
 *
 * @package Controllers
 */
class Booking_confirmation extends EA_Controller
{
    /**
     * Booking_confirmation constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');

        $this->load->library('google_sync');
    }

    /**
     * Display the appointment registration success page.
     *
     * @throws Exception
     */
    public function of(): void
    {
        $appointment_hash = $this->uri->segment(3);

        allow_iframe_embedding(); // Reached from the booking page which may be embedded in an iframe.

        $occurrences = $this->appointments_model->get(['hash' => $appointment_hash]);

        if (empty($occurrences)) {
            redirect('appointments'); // The appointment does not exist.

            return;
        }

        $appointment = $occurrences[0];

        $add_to_google_url = $this->google_sync->get_add_to_google_url($appointment['id']);
        $group_booking = null;
        $group_appointments = [];

        if (!empty($appointment['id_group_bookings'])) {
            $group_booking = $this->db
                ->get_where('group_bookings', ['id' => $appointment['id_group_bookings']])
                ->row_array();

            foreach ($this->appointments_model->get(['id_group_bookings' => $appointment['id_group_bookings']]) as $group_appointment) {
                $provider = $this->providers_model->find($group_appointment['id_users_provider']);
                $service_items = $this->appointments_model->get_service_items((int) $group_appointment['id']);
                $group_appointments[] = [
                    'guest_index' => $group_appointment['guest_index'],
                    'guest_name' => $group_appointment['guest_name'],
                    'provider_name' => trim($provider['first_name'] . ' ' . $provider['last_name']),
                    'services' => $service_items,
                    'duration' => array_sum(array_column($service_items, 'duration')),
                    'price' => array_sum(array_column($service_items, 'price')),
                ];
            }
        }

        html_vars([
            'page_title' => lang('success'),
            'company_color' => setting('company_color'),
            'google_analytics_code' => setting('google_analytics_code'),
            'matomo_analytics_url' => setting('matomo_analytics_url'),
            'matomo_analytics_site_id' => setting('matomo_analytics_site_id'),
            'add_to_google_url' => $add_to_google_url,
            'display_add_to_google_calendar' => setting('display_add_to_google_calendar', '1'),
            'group_booking' => $group_booking,
            'group_appointments' => $group_appointments,
        ]);

        $this->load->view('pages/booking_confirmation');
    }
}
