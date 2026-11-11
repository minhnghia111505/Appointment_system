<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Booking controller.
 *
 * Handles the booking related operations.
 *
 * Notice: This file used to have the booking page related code which since v1.5 has now moved to the Booking.php
 * controller for improved consistency.
 *
 * @package Controllers
 */
class Booking extends EA_Controller
{
    public array $allowed_customer_fields = [
        // Security: 'id' is intentionally excluded. Customer record identity must be resolved on the
        // backend via exists()/find_record_id()
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'address',
        'city',
        'state',
        'zip_code',
        'timezone',
        'language',
        'custom_field_1',
        'custom_field_2',
        'custom_field_3',
        'custom_field_4',
        'custom_field_5',
    ];
    public mixed $allowed_provider_fields = ['id', 'first_name', 'last_name', 'services', 'timezone'];
    public array $allowed_appointment_fields = [
        'id',
        'start_datetime',
        'end_datetime',
        'location',
        'meeting_link',
        'notes',
        'color',
        'status',
        'is_unavailability',
        'id_users_provider',
        'id_users_customer',
        'id_services',
        'id_group_bookings',
        'guest_index',
        'guest_name',
    ];

    /**
     * Booking constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('providers_model');
        $this->load->model('admins_model');
        $this->load->model('secretaries_model');
        $this->load->model('service_categories_model');
        $this->load->model('services_model');
        $this->load->model('customers_model');
        $this->load->model('settings_model');
        $this->load->model('consents_model');

        $this->load->library('timezones');
        $this->load->library('synchronization');
        $this->load->library('notifications');
        $this->load->library('availability');
        $this->load->library('webhooks_client');
        $this->load->library('jitsi_client');
    }

    /**
     * Render the booking page and display the selected appointment.
     *
     * This method will call the "index" callback to handle the page rendering.
     *
     * @param string $appointment_hash
     */
    public function reschedule(string $appointment_hash): void
    {
        html_vars(['appointment_hash' => $appointment_hash]);

        $this->index();
    }

    /**
     * Render the booking page.
    /**
     * Render the booking page.
     */
    public function index(): void
    {
        method('get');

        if (!is_app_installed()) {
            redirect('installation');

            return;
        }

        allow_iframe_embedding(); // The booking page is designed to be embeddable in third-party sites.

        $company_name = setting('company_name');
        $company_logo = setting('company_logo');
        $company_color = setting('company_color');
        $disable_booking = setting('disable_booking');
        $google_analytics_code = setting('google_analytics_code');
        $matomo_analytics_url = setting('matomo_analytics_url');
        $matomo_analytics_site_id = setting('matomo_analytics_site_id');

        if ($disable_booking) {
            $disable_booking_message = setting('disable_booking_message');

            html_vars([
                'show_message' => true,
                'page_title' => lang('page_title') . ' ' . e($company_name),
                'message_title' => lang('booking_is_disabled'),
                'message_text' => $disable_booking_message,
                'message_icon' => base_url('assets/img/error.png'),
                'google_analytics_code' => $google_analytics_code,
                'matomo_analytics_url' => $matomo_analytics_url,
                'matomo_analytics_site_id' => $matomo_analytics_site_id,
                'display_login_button' => setting('display_login_button'),
                'legal_notice_url' => setting('legal_notice_url'),
                'imprint_url' => setting('imprint_url'),
            ]);

            $this->load->view('pages/booking_message');

            return;
        }

        $available_services = $this->services_model->get_available_services(true);
        $available_providers = $this->providers_model->get_available_providers(true);

        foreach ($available_providers as &$available_provider) {
            // Only expose the required provider data.

            $this->providers_model->only($available_provider, $this->allowed_provider_fields);
        }

        $date_format = setting('date_format');
        $time_format = setting('time_format');
        $first_weekday = setting('first_weekday');
        $display_first_name = setting('display_first_name');
        $require_first_name = setting('require_first_name');
        $display_last_name = setting('display_last_name');
        $require_last_name = setting('require_last_name');
        $display_email = setting('display_email');
        $require_email = setting('require_email');
        $display_phone_number = setting('display_phone_number');
        $require_phone_number = setting('require_phone_number');
        $display_address = setting('display_address');
        $require_address = setting('require_address');
        $display_city = setting('display_city');
        $require_city = setting('require_city');
        $display_zip_code = setting('display_zip_code');
        $require_zip_code = setting('require_zip_code');
        $display_notes = setting('display_notes');
        $require_notes = setting('require_notes');
        $display_cookie_notice = setting('display_cookie_notice');
        $cookie_notice_content = setting('cookie_notice_content');
        $display_terms_and_conditions = setting('display_terms_and_conditions');
        $terms_and_conditions_content = setting('terms_and_conditions_content');
        $display_privacy_policy = setting('display_privacy_policy');
        $privacy_policy_content = setting('privacy_policy_content');
        $display_any_provider = setting('display_any_provider');
        $display_login_button = setting('display_login_button');
        $display_delete_personal_information = setting('display_delete_personal_information');
        $book_advance_timeout = setting('book_advance_timeout');
        $legal_notice_url = setting('legal_notice_url');
        $imprint_url = setting('imprint_url');
        $theme = request('theme', setting('theme', 'default'));

        // Sanitize theme parameter to prevent directory traversal
        if (!empty($theme)) {
            // Only allow alphanumeric characters, underscores, and hyphens
            $theme = preg_replace('/[^a-zA-Z0-9_\-]/', '', $theme);
        }

        if (empty($theme) || !file_exists(__DIR__ . '/../../assets/css/themes/' . $theme . '.min.css')) {
            $theme = 'default';
        }

        $timezones = $this->timezones->to_array();
        $grouped_timezones = $this->timezones->to_grouped_array();

        $appointment_hash = html_vars('appointment_hash');

        if (!empty($appointment_hash)) {
            // Load the appointments data and enable the manage mode of the booking page.

            $manage_mode = true;

            $results = $this->appointments_model->get(['hash' => $appointment_hash]);

            if (empty($results)) {
                html_vars([
                    'show_message' => true,
                    'page_title' => lang('page_title') . ' ' . $company_name,
                    'message_title' => lang('appointment_not_found'),
                    'message_text' => lang('appointment_does_not_exist_in_db'),
                    'message_icon' => base_url('assets/img/error.png'),
                    'google_analytics_code' => $google_analytics_code,
                    'matomo_analytics_url' => $matomo_analytics_url,
                    'matomo_analytics_site_id' => $matomo_analytics_site_id,
                    'display_login_button' => $display_login_button,
                    'legal_notice_url' => $legal_notice_url,
                    'imprint_url' => $imprint_url,
                ]);

                $this->load->view('pages/booking_message');

                return;
            }

            $appointment = $results[0];
            $appointment['selected_service_ids'] = array_column(
                $this->appointments_model->get_service_items((int) $appointment['id']),
                'id_services',
            );
            $appointment['selected_service_ids'] = array_values(
                array_filter($appointment['selected_service_ids'], static fn($id): bool => !empty($id)),
            );

            if (!$appointment['selected_service_ids']) {
                $appointment['selected_service_ids'] = [(int) $appointment['id_services']];
            }

            $provider = $this->providers_model->find($appointment['id_users_provider']);

            // Make sure the appointment can still be rescheduled.

            $provider_timezone = new DateTimeZone($provider['timezone']);

            $appointment_start = new DateTime($appointment['start_datetime'], $provider_timezone);

            $limit = new DateTime('now', $provider_timezone);

            $limit->modify('+' . $book_advance_timeout . ' minutes');

            if ($appointment_start < $limit) {
                $hours = floor($book_advance_timeout / 60);

                $minutes = $book_advance_timeout % 60;

                html_vars([
                    'show_message' => true,
                    'page_title' => lang('page_title') . ' ' . $company_name,
                    'message_title' => lang('appointment_locked'),
                    'message_text' => strtr(lang('appointment_locked_message'), [
                        '{$limit}' => sprintf('%02d:%02d', $hours, $minutes),
                    ]),
                    'message_icon' => base_url('assets/img/error.png'),
                    'google_analytics_code' => $google_analytics_code,
                    'matomo_analytics_url' => $matomo_analytics_url,
                    'matomo_analytics_site_id' => $matomo_analytics_site_id,
                    'display_login_button' => $display_login_button,
                    'legal_notice_url' => $legal_notice_url,
                    'imprint_url' => $imprint_url,
                ]);

                $this->load->view('pages/booking_message');

                return;
            }
            $customer = $this->customers_model->find($appointment['id_users_customer']);
            $this->customers_model->only($customer, $this->allowed_customer_fields);
            $customer_token = md5(uniqid(mt_rand(), true));

            // Cache the token for 10 minutes.
            $this->cache->save('customer-token-' . $customer_token, $appointment['id_users_customer'], 600);
        } else {
            $manage_mode = false;
            $customer_token = false;
            $appointment = null;
            $provider = null;
            $customer = null;
        }

        script_vars([
            'manage_mode' => $manage_mode,
            'available_services' => $available_services,
            'available_providers' => filter_sensitive_users_data($available_providers),
            'date_format' => $date_format,
            'time_format' => $time_format,
            'first_weekday' => $first_weekday,
            'display_cookie_notice' => $display_cookie_notice,
            'display_any_provider' => setting('display_any_provider'),
            'future_booking_limit' => setting('future_booking_limit'),
            'appointment_data' => $appointment,
            'provider_data' => $provider ? filter_sensitive_user_data($provider) : null,
            'customer_data' => $customer,
            'customer_token' => $customer_token,
            'default_language' => setting('default_language'),
            'default_timezone' => setting('default_timezone'),
        ]);

        html_vars([
            'available_services' => $available_services,
            'available_providers' => filter_sensitive_users_data($available_providers),
            'theme' => $theme,
            'company_name' => $company_name,
            'company_logo' => $company_logo,
            'company_color' => $company_color === '#ffffff' ? '' : $company_color,
            'date_format' => $date_format,
            'time_format' => $time_format,
            'first_weekday' => $first_weekday,
            'display_first_name' => $display_first_name,
            'require_first_name' => $require_first_name,
            'display_last_name' => $display_last_name,
            'require_last_name' => $require_last_name,
            'display_email' => $display_email,
            'require_email' => $require_email,
            'display_phone_number' => $display_phone_number,
            'require_phone_number' => $require_phone_number,
            'display_address' => $display_address,
            'require_address' => $require_address,
            'display_city' => $display_city,
            'require_city' => $require_city,
            'display_zip_code' => $display_zip_code,
            'require_zip_code' => $require_zip_code,
            'display_notes' => $display_notes,
            'require_notes' => $require_notes,
            'display_cookie_notice' => $display_cookie_notice,
            'cookie_notice_content' => $cookie_notice_content,
            'display_terms_and_conditions' => $display_terms_and_conditions,
            'terms_and_conditions_content' => $terms_and_conditions_content,
            'display_privacy_policy' => $display_privacy_policy,
            'privacy_policy_content' => $privacy_policy_content,
            'display_any_provider' => $display_any_provider,
            'display_login_button' => $display_login_button,
            'display_delete_personal_information' => $display_delete_personal_information,
            'legal_notice_url' => $legal_notice_url,
            'imprint_url' => $imprint_url,
            'google_analytics_code' => $google_analytics_code,
            'matomo_analytics_url' => $matomo_analytics_url,
            'matomo_analytics_site_id' => $matomo_analytics_site_id,
            'timezones' => $timezones,
            'grouped_timezones' => $grouped_timezones,
            'manage_mode' => $manage_mode,
            'appointment_data' => $appointment,
            'provider_data' => $provider ? filter_sensitive_user_data($provider) : null,
            'customer_data' => $customer,
        ]);

        $this->load->view('pages/booking');
    }

    /**
     * Register the appointment to the database.
     */
    public function register(): void
    {
        try {
            method('post');

            allow_iframe_embedding(); // Reached from the booking page which may be embedded in an iframe.

            $disable_booking = setting('disable_booking');

            if ($disable_booking) {
                abort(403);
            }

            check('post_data', 'array');
            check('captcha', 'string|null');

            $post_data = request('post_data');

            // Validate that post_data is an array
            if (!is_array($post_data)) {
                throw new InvalidArgumentException('Invalid request data format.');
            }

            $captcha = request('captcha');
            $appointment = $post_data['appointment'] ?? [];
            $customer = $post_data['customer'] ?? [];
            $manage_mode = filter_var($post_data['manage_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // Security: Verify hash when updating an existing appointment
            if ($manage_mode && !empty($appointment['id'])) {
                $appointment_hash = $post_data['appointment_hash'] ?? '';

                if (empty($appointment_hash)) {
                    throw new RuntimeException('Appointment hash is required for updates.');
                }

                // Verify the hash matches the appointment being updated
                $existing_appointments = $this->appointments_model->get([
                    'id' => $appointment['id'],
                    'hash' => $appointment_hash,
                ]);

                if (empty($existing_appointments)) {
                    throw new RuntimeException('Invalid appointment hash or appointment not found.');
                }
            }

            // Validate required appointment fields
            if (empty($appointment) || !is_array($appointment)) {
                throw new InvalidArgumentException('Invalid appointment data.');
            }

            // Validate required customer fields
            if (empty($customer) || !is_array($customer)) {
                throw new InvalidArgumentException('Invalid customer data.');
            }

            // Sanitize and validate customer email
            if (!empty($customer['email']) && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email address format.');
            }

            // Sanitize customer fields - only allow expected fields
            $customer = array_intersect_key($customer, array_flip($this->allowed_customer_fields));

            // Validate and resolve selected services on the server rather than trusting client totals.
            $service_ids = $this->normalize_service_ids(
                $appointment['id_services'] ?? null,
                $appointment['selected_service_ids'] ?? null,
            );
            $service_bundle = $this->get_service_bundle((int) $appointment['id_services'], $service_ids);

            // Sanitize appointment fields - only allow expected fields
            $appointment = array_intersect_key($appointment, array_flip($this->allowed_appointment_fields));
            $appointment['selected_service_ids'] = $service_ids;

            $appointment_start = new DateTime($appointment['start_datetime']);
            $appointment_end = clone $appointment_start;
            $appointment_end->add(new DateInterval('PT' . $service_bundle['service']['duration'] . 'M'));
            $appointment['end_datetime'] = $appointment_end->format('Y-m-d H:i:s');

            // Security: Remove appointment ID if not in manage mode to prevent IDOR attacks
            if (!$manage_mode && isset($appointment['id'])) {
                unset($appointment['id']);
            }

            if (!array_key_exists('address', $customer)) {
                $customer['address'] = '';
            }

            if (!array_key_exists('city', $customer)) {
                $customer['city'] = '';
            }

            if (!array_key_exists('zip_code', $customer)) {
                $customer['zip_code'] = '';
            }

            if (!array_key_exists('notes', $customer)) {
                $customer['notes'] = '';
            }

            if (!array_key_exists('phone_number', $customer)) {
                $customer['phone_number'] = '';
            }

            // Check appointment availability before registering it to the database.
            $appointment['id_users_provider'] = $this->check_datetime_availability($appointment);

            if (!$appointment['id_users_provider']) {
                throw new RuntimeException(lang('requested_hour_is_unavailable'));
            }

            $provider = $this->providers_model->find($appointment['id_users_provider']);

            $service = $service_bundle['service'];
            $service['name'] = implode(' + ', array_column($service_bundle['items'], 'name'));

            $require_captcha = (bool) setting('require_captcha');

            // Validate CAPTCHA or ALTCHA
            if ($require_captcha) {
                $altcha_enabled = setting('altcha_enabled') === '1';

                if ($altcha_enabled) {
                    // Validate ALTCHA
                    check('altcha_payload', 'string|null');
                    $altcha_payload = request('altcha_payload');

                    $this->load->library('altcha_client');

                    if (!$this->altcha_client->verify($altcha_payload)) {
                        json_response([
                            'altcha_verification' => false,
                        ]);
                        return;
                    }
                } else {
                    // Validate traditional CAPTCHA
                    $captcha_phrase = session('captcha_phrase');

                    // A phrase is valid for a single attempt, so that one solved image cannot be replayed
                    $this->session->unset_userdata('captcha_phrase');

                    if (
                        empty($captcha_phrase) ||
                        empty($captcha) ||
                        strtoupper($captcha_phrase) !== strtoupper($captcha)
                    ) {
                        json_response([
                            'captcha_verification' => false,
                        ]);
                        return;
                    }
                }
            }

            if ($this->customers_model->exists($customer)) {
                $customer['id'] = $this->customers_model->find_record_id($customer);

                $existing_appointments = $this->appointments_model->get([
                    'id !=' => $manage_mode ? $appointment['id'] : null,
                    'id_users_customer' => $customer['id'],
                    'start_datetime <=' => $appointment['start_datetime'],
                    'end_datetime >=' => $appointment['end_datetime'],
                ]);

                if (count($existing_appointments)) {
                    throw new RuntimeException(lang('customer_is_already_booked'));
                }
            }

            // Jitsi integration: if enabled, generate a Jitsi meeting link for the appointment
            if (setting('jitsi_enabled') === '1') {
                $appointment['meeting_link'] = $this->jitsi_client->generate_link();
            }

            if (empty($appointment['location']) && !empty($service['location'])) {
                $appointment['location'] = $service['location'];
            }

            if (empty($appointment['color']) && !empty($service['color'])) {
                $appointment['color'] = $service['color'];
            }

            $customer_ip = $this->input->ip_address();

            // Create the consents (if needed).
            $consent = [
                'first_name' => $customer['first_name'] ?? '-',
                'last_name' => $customer['last_name'] ?? '-',
                'email' => $customer['email'] ?? '-',
                'ip' => $customer_ip,
            ];

            if (setting('display_terms_and_conditions')) {
                $consent['type'] = 'terms-and-conditions';

                $this->consents_model->save($consent);
            }

            if (setting('display_privacy_policy')) {
                $consent['type'] = 'privacy-policy';

                $this->consents_model->save($consent);
            }

            // Save customer language (the language which is used to render the booking page).
            $customer['language'] = session('language') ?? config('language');

            $this->customers_model->only($customer, $this->allowed_customer_fields);

            $customer_id = $this->customers_model->save($customer);
            $customer = $this->customers_model->find($customer_id);

            $appointment['id_users_customer'] = $customer_id;
            $appointment['is_unavailability'] = false;
            $appointment['color'] = $service['color'];

            $appointment_status_options_json = setting('appointment_status_options', '[]');
            $appointment_status_options = json_decode($appointment_status_options_json, true) ?? [];
            $appointment['status'] = $appointment_status_options[0] ?? null;
            unset($appointment['selected_service_ids']);
            $this->appointments_model->only($appointment, $this->allowed_appointment_fields);

            $this->db->trans_begin();

            try {
                $this->lock_provider_records([(int) $appointment['id_users_provider']]);

                if ($this->check_datetime_availability($appointment) !== (int) $appointment['id_users_provider']) {
                    throw new RuntimeException(lang('requested_hour_is_unavailable'));
                }

                $appointment_id = $this->appointments_model->save($appointment);
                $this->appointments_model->set_service_items($appointment_id, $service_bundle['items']);

                if (!$this->db->trans_status()) {
                    throw new RuntimeException('Could not save appointment service selections.');
                }

                $this->db->trans_commit();
            } catch (Throwable $e) {
                $this->db->trans_rollback();
                throw $e;
            }

            $appointment = $this->appointments_model->find($appointment_id);

            $company_color = setting('company_color');

            $settings = [
                'company_name' => setting('company_name'),
                'company_link' => setting('company_link'),
                'company_email' => setting('company_email'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
                'date_format' => setting('date_format'),
                'time_format' => setting('time_format'),
            ];

            $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);

            $this->notifications->notify_appointment_saved(
                $appointment,
                $service,
                $provider,
                $customer,
                $settings,
                $manage_mode,
            );

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);

            $response = [
                'appointment_id' => $appointment['id'],
                'appointment_hash' => $appointment['hash'],
            ];

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Return common slots that can serve every guest with a distinct provider.
     */
    public function check_group_availability(): void
    {
        try {
            method('post');
            allow_iframe_embedding();

            if (setting('disable_booking')) {
                abort(403);
            }

            check('guests', 'array');
            check('selected_date', 'date');
            check('timezone', 'string');

            $guests = request('guests');

            if (count($guests) < 2 || count($guests) > 3) {
                throw new InvalidArgumentException('Group bookings must contain two or three guests.');
            }

            $guest_bundles = $this->prepare_group_guests($guests);
            $timezone = $this->validate_timezone(request('timezone'));
            $slot_provider_hours = $this->get_group_provider_hours(
                $guest_bundles,
                request('selected_date'),
                $timezone,
            );
            $available_hours = [];

            foreach ($slot_provider_hours as $guest_hours) {
                foreach (array_keys($guest_hours) as $hour) {
                    $available_hours[$hour] = true;
                }
            }

            $matching_hours = [];

            foreach (array_keys($available_hours) as $hour) {
                if ($this->match_group_providers($slot_provider_hours, $hour) !== null) {
                    $matching_hours[] = $hour;
                }
            }

            sort($matching_hours);
            json_response($matching_hours);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Save all guest appointments atomically as one group booking.
     */
    public function register_group(): void
    {
        try {
            method('post');
            allow_iframe_embedding();

            if (setting('disable_booking')) {
                abort(403);
            }

            check('post_data', 'array');
            check('captcha', 'string|null');

            $post_data = request('post_data');
            $group = $post_data['group_booking'] ?? null;
            $customer = $post_data['customer'] ?? null;

            if (!is_array($group) || !is_array($customer)) {
                throw new InvalidArgumentException('Invalid group booking data.');
            }

            $guests = $group['guests'] ?? null;

            if (!is_array($guests) || count($guests) < 2 || count($guests) > 3) {
                throw new InvalidArgumentException('Group bookings must contain two or three guests.');
            }

            if (!empty($customer['email']) && !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid email address format.');
            }

            $customer = array_intersect_key($customer, array_flip($this->allowed_customer_fields));
            $customer += ['address' => '', 'city' => '', 'zip_code' => '', 'phone_number' => '', 'notes' => ''];
            $guest_bundles = $this->prepare_group_guests($guests);
            $start_datetime = $this->validate_group_start_datetime(
                (string) ($group['booking_date'] ?? ''),
                (string) ($group['start_time'] ?? ''),
                (string) ($group['timezone'] ?? ''),
            );
            $booking_date = $start_datetime->format('Y-m-d');
            $start_time = $start_datetime->format('H:i:s');
            $group_notes = trim(strip_tags((string) ($group['notes'] ?? '')));

            if (setting('require_captcha')) {
                if (setting('altcha_enabled') === '1') {
                    check('altcha_payload', 'string|null');
                    $this->load->library('altcha_client');

                    if (!$this->altcha_client->verify(request('altcha_payload'))) {
                        json_response(['altcha_verification' => false]);
                        return;
                    }
                } else {
                    $captcha_phrase = session('captcha_phrase');
                    $this->session->unset_userdata('captcha_phrase');

                    if (empty($captcha_phrase) || strtoupper((string) request('captcha')) !== strtoupper($captcha_phrase)) {
                        json_response(['captcha_verification' => false]);
                        return;
                    }
                }
            }

            $this->db->trans_begin();

            try {
                $provider_ids = [];
                foreach ($guest_bundles as $guest_bundle) {
                    foreach (array_keys($guest_bundle['providers']) as $provider_id) {
                        $provider_ids[] = (int) $provider_id;
                    }
                }
                $this->lock_provider_records($provider_ids);

                $provider_hours = $this->get_group_provider_hours(
                    $guest_bundles,
                    $booking_date,
                    $start_datetime->getTimezone()->getName(),
                );
                $assignment = $this->match_group_providers($provider_hours, $start_datetime->format('H:i'));

                if ($assignment === null) {
                    throw new RuntimeException(lang('requested_hour_is_unavailable'));
                }

                foreach ($guest_bundles as $index => &$guest_bundle) {
                    $guest_bundle['provider_id'] = $assignment[$index];
                }
                unset($guest_bundle);

                if ($this->customers_model->exists($customer)) {
                    $customer['id'] = $this->customers_model->find_record_id($customer);
                }

                if (!empty($customer['id'])) {
                    $latest_guest_end = clone $start_datetime;

                    foreach ($guest_bundles as $guest_bundle) {
                        $guest_end = (clone $start_datetime)->add(
                            new DateInterval('PT' . $guest_bundle['service']['duration'] . 'M'),
                        );

                        if ($guest_end > $latest_guest_end) {
                            $latest_guest_end = $guest_end;
                        }
                    }

                    $conflicting_appointments = $this->appointments_model->get([
                        'id_users_customer' => $customer['id'],
                        'start_datetime <' => $latest_guest_end->format('Y-m-d H:i:s'),
                        'end_datetime >' => $start_datetime->format('Y-m-d H:i:s'),
                    ]);

                    if ($conflicting_appointments) {
                        throw new RuntimeException(lang('customer_is_already_booked'));
                    }
                }

                $customer['language'] = session('language') ?? config('language');
                $this->customers_model->only($customer, $this->allowed_customer_fields);
                $customer_id = $this->customers_model->save($customer);
                $customer = $this->customers_model->find($customer_id);

                $consent = [
                    'first_name' => $customer['first_name'] ?? '-',
                    'last_name' => $customer['last_name'] ?? '-',
                    'email' => $customer['email'] ?? '-',
                    'ip' => $this->input->ip_address(),
                    'id_users' => $customer_id,
                ];

                if (setting('display_terms_and_conditions')) {
                    $consent['type'] = 'terms-and-conditions';
                    $this->consents_model->save($consent);
                }

                if (setting('display_privacy_policy')) {
                    $consent['type'] = 'privacy-policy';
                    $this->consents_model->save($consent);
                }

                $total_amount = array_sum(array_map(
                    static fn(array $guest_bundle): float => (float) $guest_bundle['service']['price'],
                    $guest_bundles,
                ));

                $group_booking_id = $this->appointments_model->create_group_booking([
                    'id_users_customer' => $customer_id,
                    'booking_date' => $booking_date,
                    'start_time' => $start_time,
                    'timezone' => $start_datetime->getTimezone()->getName(),
                    'total_guests' => count($guest_bundles),
                    'total_amount' => $total_amount,
                    'create_datetime' => date('Y-m-d H:i:s'),
                ]);
                $status_options = json_decode(setting('appointment_status_options', '[]'), true) ?? [];
                $appointment_ids = [];

                foreach ($guest_bundles as $index => $guest_bundle) {
                    $provider = $this->providers_model->find($guest_bundle['provider_id']);
                    $service = $guest_bundle['service'];
                    $service['name'] = implode(' + ', array_column($guest_bundle['items'], 'name'));
                    $start = clone $start_datetime;
                    $provider_timezone = $provider['timezone'] ?: date_default_timezone_get();
                    $start->setTimezone(new DateTimeZone($provider_timezone));
                    $end = (clone $start)->add(new DateInterval('PT' . $service['duration'] . 'M'));
                    $appointment = [
                        'start_datetime' => $start->format('Y-m-d H:i:s'),
                        'end_datetime' => $end->format('Y-m-d H:i:s'),
                        'notes' => $group_notes,
                        'is_unavailability' => false,
                        'id_users_provider' => $guest_bundle['provider_id'],
                        'id_users_customer' => $customer_id,
                        'id_services' => $guest_bundle['primary_service_id'],
                        'id_group_bookings' => $group_booking_id,
                        'guest_index' => $index + 1,
                        'guest_name' => $guest_bundle['guest_name'],
                        'status' => $status_options[0] ?? null,
                        'color' => $service['color'] ?? null,
                        'location' => $service['location'] ?? null,
                    ];

                    $appointment_id = $this->appointments_model->save($appointment);
                    $this->appointments_model->set_service_items($appointment_id, $guest_bundle['items']);
                    $appointment_ids[] = $appointment_id;
                }

                if (!$this->db->trans_status()) {
                    throw new RuntimeException('Could not save all group booking details.');
                }

                $this->db->trans_commit();
            } catch (Throwable $e) {
                $this->db->trans_rollback();
                throw $e;
            }

            $company_color = setting('company_color');
            $settings = [
                'company_name' => setting('company_name'),
                'company_link' => setting('company_link'),
                'company_email' => setting('company_email'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
                'date_format' => setting('date_format'),
                'time_format' => setting('time_format'),
            ];

            $appointment_hash = null;

            foreach ($appointment_ids as $index => $appointment_id) {
                $appointment = $this->appointments_model->find($appointment_id);
                $appointment_hash ??= $appointment['hash'];
                $provider = $this->providers_model->find($guest_bundles[$index]['provider_id']);
                $service = $guest_bundles[$index]['service'];
                $service['name'] = implode(' + ', array_column($guest_bundles[$index]['items'], 'name'));

                $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);
                $this->notifications->notify_appointment_saved($appointment, $service, $provider, $customer, $settings, false);
                $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);
            }

            json_response([
                'group_booking_id' => $group_booking_id,
                'appointment_ids' => $appointment_ids,
                'appointment_hash' => $appointment_hash,
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Normalize and validate group guest choices using server-side service and provider data.
     */
    protected function prepare_group_guests(array $guests): array
    {
        $prepared_guests = [];
        $available_providers = $this->providers_model->get_available_providers(true);

        foreach ($guests as $index => $guest) {
            if (!is_array($guest)) {
                throw new InvalidArgumentException('A group guest entry is invalid.');
            }

            $primary_service_id = filter_var($guest['primary_service_id'] ?? null, FILTER_VALIDATE_INT);
            $service_ids = $this->normalize_service_ids($primary_service_id, $guest['service_ids'] ?? null);
            $service_bundle = $this->get_service_bundle($primary_service_id, $service_ids);
            $eligible_providers = [];

            foreach ($available_providers as $provider) {
                if ($this->provider_supports_services($provider, $service_ids)) {
                    $eligible_providers[(int) $provider['id']] = $provider;
                }
            }

            $requested_provider_id = (string) ($guest['provider_id'] ?? ANY_PROVIDER);

            if ($requested_provider_id !== (string) ANY_PROVIDER) {
                $requested_provider_id = filter_var($requested_provider_id, FILTER_VALIDATE_INT);

                if (!$requested_provider_id || !isset($eligible_providers[$requested_provider_id])) {
                    throw new InvalidArgumentException('A selected barber cannot perform all services for this guest.');
                }

                $eligible_providers = [$requested_provider_id => $eligible_providers[$requested_provider_id]];
            }

            if (!$eligible_providers) {
                throw new InvalidArgumentException('No barber is assigned to all selected services for a guest.');
            }

            $guest_name = trim(strip_tags((string) ($guest['guest_name'] ?? '')));

            if (mb_strlen($guest_name) > 256) {
                throw new InvalidArgumentException('A guest name is too long.');
            }

            $prepared_guests[] = [
                'guest_name' => $guest_name,
                'primary_service_id' => $primary_service_id,
                'service_ids' => $service_ids,
                'service' => $service_bundle['service'],
                'items' => $service_bundle['items'],
                'providers' => $eligible_providers,
            ];
        }

        return $prepared_guests;
    }

    /**
     * Get per-guest provider slots, applying each guest's provider preference.
     */
    protected function get_group_provider_hours(array $guests, string $date, string $timezone): array
    {
        $guest_hours = [];
        $booking_timezone = new DateTimeZone($timezone);

        foreach ($guests as $index => $guest) {
            $guest_hours[$index] = [];

            foreach ($guest['providers'] as $provider_id => $provider) {
                $provider_timezone = new DateTimeZone($provider['timezone'] ?: date_default_timezone_get());
                $booking_day = new DateTime($date . ' 12:00:00', $booking_timezone);
                $provider_day = (clone $booking_day)->setTimezone($provider_timezone);

                for ($day_offset = -1; $day_offset <= 1; $day_offset++) {
                    $current_provider_day = (clone $provider_day)->modify(($day_offset >= 0 ? '+' : '') . $day_offset . ' days');
                    $hours = $this->availability->get_available_hours(
                        $current_provider_day->format('Y-m-d'),
                        $guest['service'],
                        $provider,
                    );

                    foreach ($hours as $hour) {
                        $provider_start = new DateTime(
                            $current_provider_day->format('Y-m-d') . ' ' . $hour,
                            $provider_timezone,
                        );
                        $booking_start = (clone $provider_start)->setTimezone($booking_timezone);

                        if ($booking_start->format('Y-m-d') === $date) {
                            $booking_hour = $booking_start->format('H:i');
                            $guest_hours[$index][$booking_hour][] = (int) $provider_id;
                        }
                    }
                }
            }
        }

        return $guest_hours;
    }

    /**
     * Assign a different available provider to each guest at the requested time.
     */
    protected function match_group_providers(array $guest_hours, string $hour, int $guest_index = 0, array $used = []): ?array
    {
        if ($guest_index >= count($guest_hours)) {
            return [];
        }

        foreach ($guest_hours[$guest_index][$hour] ?? [] as $provider_id) {
            if (in_array($provider_id, $used, true)) {
                continue;
            }

            $assignment = $this->match_group_providers(
                $guest_hours,
                $hour,
                $guest_index + 1,
                [...$used, $provider_id],
            );

            if ($assignment !== null) {
                return [$provider_id, ...$assignment];
            }
        }

        return null;
    }

    /**
     * Lock provider rows so group and single bookings cannot reserve the same provider concurrently.
     */
    protected function lock_provider_records(array $provider_ids): void
    {
        $provider_ids = array_values(array_unique(array_filter(array_map('intval', $provider_ids))));

        if (!$provider_ids) {
            throw new RuntimeException('No eligible barbers are available.');
        }

        sort($provider_ids);
        $placeholders = implode(', ', array_fill(0, count($provider_ids), '?'));

        $this->db->query(
            'SELECT id FROM `' . $this->db->dbprefix('users') . '` WHERE id IN (' . $placeholders . ') ORDER BY id FOR UPDATE',
            $provider_ids,
        );
    }

    /**
     * Validate the common local start date and time submitted by the group booking form.
     */
    protected function validate_group_start_datetime(string $date, string $time, string $timezone): DateTime
    {
        $timezone = $this->validate_timezone($timezone);
        $start = DateTime::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, new DateTimeZone($timezone));
        $errors = DateTime::getLastErrors();

        if (
            !$start ||
            ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) ||
            $start->format('Y-m-d H:i') !== $date . ' ' . $time
        ) {
            throw new InvalidArgumentException('The group booking date or start time is invalid.');
        }

        return $start;
    }

    /**
     * Accept only installed IANA timezones.
     */
    protected function validate_timezone(string $timezone): string
    {
        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException('The group booking timezone is invalid.');
        }

        return $timezone;
    }

    /**
     * Normalize and validate the service IDs submitted for a booking.
     *
     * @param int|string|null $primary_service_id Primary service ID.
     * @param mixed $service_ids Selected service IDs from the request.
     *
     * @return int[]
     */
    protected function normalize_service_ids(int|string|null $primary_service_id, mixed $service_ids): array
    {
        $primary_service_id = filter_var($primary_service_id, FILTER_VALIDATE_INT);

        if (!$primary_service_id || $primary_service_id < 1) {
            throw new InvalidArgumentException('A valid primary service is required.');
        }

        if (is_string($service_ids)) {
            $service_ids = json_decode($service_ids, true);

            if (!is_array($service_ids)) {
                throw new InvalidArgumentException('The selected services are invalid.');
            }
        }

        if ($service_ids === null) {
            $service_ids = [$primary_service_id];
        }

        if (!is_array($service_ids) || count($service_ids) > 20) {
            throw new InvalidArgumentException('The selected services are invalid.');
        }

        $normalized_ids = [$primary_service_id];

        foreach ($service_ids as $service_id) {
            $service_id = filter_var($service_id, FILTER_VALIDATE_INT);

            if (!$service_id || $service_id < 1) {
                throw new InvalidArgumentException('The selected services are invalid.');
            }

            if (!in_array($service_id, $normalized_ids, true)) {
                $normalized_ids[] = $service_id;
            }
        }

        return $normalized_ids;
    }

    /**
     * Load selected services and calculate their combined appointment values.
     *
     * @param int $primary_service_id Primary service ID.
     * @param int[] $service_ids Selected service IDs.
     *
     * @return array
     */
    protected function get_service_bundle(int $primary_service_id, array $service_ids): array
    {
        $items = [];
        $primary_service = null;

        foreach ($service_ids as $service_id) {
            $service = $this->services_model->find($service_id);

            if (!empty($service['is_private'])) {
                throw new InvalidArgumentException('A selected service is not available for public booking.');
            }

            $category = !empty($service['id_service_categories'])
                ? $this->db->get_where('service_categories', ['id' => $service['id_service_categories']])->row_array()
                : null;
            $is_combo = !empty($category) && stripos((string) ($category['name'] ?? ''), 'combo') !== false;

            if ($is_combo && $service_id !== $primary_service_id) {
                throw new InvalidArgumentException('A combo can only be selected as the primary service.');
            }

            $items[] = [
                'id_services' => (int) $service['id'],
                'name' => $service['name'],
                'duration' => (int) $service['duration'],
                'price' => (float) $service['price'],
            ];

            if ($service_id === $primary_service_id) {
                $primary_service = $service;
            }
        }

        if ($primary_service === null) {
            throw new InvalidArgumentException('The primary service must be part of the selection.');
        }

        $primary_service['duration'] = array_sum(array_column($items, 'duration'));
        $primary_service['price'] = array_sum(array_column($items, 'price'));

        return [
            'service' => $primary_service,
            'service_ids' => $service_ids,
            'items' => $items,
        ];
    }

    /**
     * Determine whether a provider is assigned to every selected service.
     *
     * @param array $provider Provider data.
     * @param int[] $service_ids Selected service IDs.
     *
     * @return bool
     */
    protected function provider_supports_services(array $provider, array $service_ids): bool
    {
        $provider_service_ids = array_map('intval', $provider['services']);

        return !array_diff($service_ids, $provider_service_ids);
    }

    /**
     * Check whether the provider is still available in the selected appointment date.
     *
     * It is possible that two or more customers select the same appointment date and time concurrently. The app won't
     * allow this to happen, so one of the two will eventually get the selected date and the other one will have
     * to choose for another one.
     *
     * Use this method just before the customer confirms the appointment registration. If the selected date was reserved
     * in the meanwhile, the customer must be prompted to select another time.
     *
     * @param array $appointment Sanitized appointment data, its ID is only kept for a verified manage mode request.
     *
     * @return int|null Returns the ID of the provider that is available for the appointment.
     *
     * @throws Exception
     */
    protected function check_datetime_availability(array $appointment): ?int
    {
        $appointment_start = new DateTime($appointment['start_datetime']);

        $date = $appointment_start->format('Y-m-d');

        $hour = $appointment_start->format('H:i');
        $service_ids = $appointment['selected_service_ids'] ?? [(int) $appointment['id_services']];
        $service_bundle = $this->get_service_bundle((int) $appointment['id_services'], $service_ids);

        if ($appointment['id_users_provider'] === ANY_PROVIDER) {
            $appointment['id_users_provider'] = $this->search_any_provider($service_bundle, $date, $hour);

            return $appointment['id_users_provider'];
        }

        $exclude_appointment_id = $appointment['id'] ?? null;

        $provider = $this->providers_model->find($appointment['id_users_provider']);

        if (!$this->provider_supports_services($provider, $service_ids)) {
            return null;
        }

        $available_hours = $this->availability->get_available_hours(
            $date,
            $service_bundle['service'],
            $provider,
            $exclude_appointment_id,
        );

        $is_still_available = false;

        $appointment_hour = date('H:i', strtotime($appointment['start_datetime']));

        foreach ($available_hours as $available_hour) {
            if ($appointment_hour === $available_hour) {
                $is_still_available = true;
                break;
            }
        }

        return $is_still_available ? $appointment['id_users_provider'] : null;
    }

    /**
     * Search for any provider that can handle all selected services.
     *
     * This method will return the database ID of the provider with the most available periods.
     *
     * @param array $service_bundle Selected services and their combined values.
     * @param string $date Selected date (Y-m-d).
     * @param string|null $hour Selected hour (H:i).
     *
     * @return int|null Returns the ID of the provider that can provide the service at the selected date.
     *
     * @throws Exception
     */
    protected function search_any_provider(array $service_bundle, string $date, ?string $hour = null): ?int
    {
        $available_providers = $this->providers_model->get_available_providers(true);

        $provider_id = null;

        $max_hours_count = 0;

        foreach ($available_providers as $provider) {
            if (!$this->provider_supports_services($provider, $service_bundle['service_ids'])) {
                continue;
            }

            $available_hours = $this->availability->get_available_hours(
                $date,
                $service_bundle['service'],
                $provider,
            );

            if (
                count($available_hours) > $max_hours_count &&
                (empty($hour) || in_array($hour, $available_hours))
            ) {
                $provider_id = $provider['id'];
                $max_hours_count = count($available_hours);
            }
        }

        return $provider_id;
    }

    /**
     * Get the available appointment hours for the selected date.
     *
     * This method answers to an AJAX request. It calculates the available hours for the given service, provider and
     * date.
     */
    public function get_available_hours(): void
    {
        try {
            method('post');

            allow_iframe_embedding(); // Called from the booking page which may be embedded in an iframe.

            $disable_booking = setting('disable_booking');

            if ($disable_booking) {
                abort(403);
            }

            check('provider_id', 'string|numeric|null');
            check('service_id', 'numeric');
            check('service_ids', 'string|null');
            check('selected_date', 'date');
            check('manage_mode', 'bool|null');
            check('appointment_id', 'numeric|null');

            $provider_id = request('provider_id');
            $service_id = request('service_id');
            $service_ids = $this->normalize_service_ids($service_id, request('service_ids'));
            $selected_date = request('selected_date');

            // Do not continue if there was no provider selected (more likely there is no provider in the system).

            if (empty($provider_id)) {
                json_response();

                return;
            }

            // If manage mode is TRUE then the following we should not consider the selected appointment when
            // calculating the available time periods of the provider.

            $exclude_appointment_id = request('manage_mode') ? request('appointment_id') : null;

            // If the user has selected the "any-provider" option then we will need to search for an available provider
            // that will provide the requested service.

            $service_bundle = $this->get_service_bundle((int) $service_id, $service_ids);

            if ($provider_id === ANY_PROVIDER) {
                $providers = $this->providers_model->get_available_providers(true);

                $available_hours = [];

                foreach ($providers as $provider) {
                    if (!$this->provider_supports_services($provider, $service_ids)) {
                        continue;
                    }

                    $provider_available_hours = $this->availability->get_available_hours(
                        $selected_date,
                        $service_bundle['service'],
                        $provider,
                        $exclude_appointment_id,
                    );

                    $available_hours = array_merge($available_hours, $provider_available_hours);
                }

                $available_hours = array_unique(array_values($available_hours));

                sort($available_hours);

                $response = $available_hours;
            } else {
                $provider = $this->providers_model->find($provider_id);

                if (!$this->provider_supports_services($provider, $service_ids)) {
                    json_response([]);

                    return;
                }

                $response = $this->availability->get_available_hours(
                    $selected_date,
                    $service_bundle['service'],
                    $provider,
                    $exclude_appointment_id,
                );
            }

            json_response($response);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get the available appointment dates for the selected date period.
     *
     * Get an array with the available dates of a specific provider, service and month of the year. Provide the
     * "provider_id", "service_id" and "selected_date" as GET parameters to the request. The "selected_date" parameter
     * must have the "Y-m-d" format.
     *
     * Outputs a JSON string with the unavailability dates. that are unavailability.
     */
    public function get_unavailable_dates(): void
    {
        try {
            method('get');

            allow_iframe_embedding(); // Called from the booking page which may be embedded in an iframe.

            $disable_booking = setting('disable_booking');

            if ($disable_booking) {
                abort(403);
            }

            check('provider_id', 'string|numeric|null');
            check('service_id', 'numeric');
            check('service_ids', 'string|null');
            check('appointment_id', 'numeric|null');
            check('manage_mode', 'bool|null');
            check('selected_date', 'date');

            $provider_id = request('provider_id');
            $service_id = request('service_id');
            $service_ids = $this->normalize_service_ids($service_id, request('service_ids'));
            $appointment_id = request('appointment_id');
            $manage_mode = filter_var(request('manage_mode'), FILTER_VALIDATE_BOOLEAN);
            $selected_date_string = request('selected_date');
            $selected_date = new DateTime($selected_date_string);
            $number_of_days_in_month = (int) $selected_date->format('t');
            $unavailable_dates = [];

            $provider_ids =
                $provider_id === ANY_PROVIDER ? $this->search_providers_by_service($service_ids) : [$provider_id];

            $exclude_appointment_id = $manage_mode ? $appointment_id : null;

            // Get the service record.
            $service_bundle = $this->get_service_bundle((int) $service_id, $service_ids);

            for ($i = 1; $i <= $number_of_days_in_month; $i++) {
                $current_date = new DateTime($selected_date->format('Y-m') . '-' . $i);

                if ($current_date < new DateTime(date('Y-m-d 00:00:00'))) {
                    // Past dates become immediately unavailability.
                    $unavailable_dates[] = $current_date->format('Y-m-d');
                    continue;
                }

                // Finding at least one slot of availability.
                $available_hours = [];

                foreach ($provider_ids as $current_provider_id) {
                    $provider = $this->providers_model->find($current_provider_id);

                    if (!$this->provider_supports_services($provider, $service_ids)) {
                        continue;
                    }

                    $available_hours = $this->availability->get_available_hours(
                        $current_date->format('Y-m-d'),
                        $service_bundle['service'],
                        $provider,
                        $exclude_appointment_id,
                    );

                    if (!empty($available_hours)) {
                        break;
                    }
                }

                // No availability amongst all the provider.
                if (empty($available_hours)) {
                    $unavailable_dates[] = $current_date->format('Y-m-d');
                }
            }

            if (count($unavailable_dates) === $number_of_days_in_month) {
                json_response([
                    'is_month_unavailable' => true,
                ]);

                return;
            }

            json_response($unavailable_dates);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Search for providers that can handle every selected service.
     *
     * This method will return the database ID of the providers affected to the requested service.
     *
     * @param int[] $service_ids The requested service IDs.
     *
     * @return array Returns the ID of the provider that can provide the requested service.
     */
    protected function search_providers_by_service(array $service_ids): array
    {
        $available_providers = $this->providers_model->get_available_providers(true);
        $provider_list = [];

        foreach ($available_providers as $provider) {
            if ($this->provider_supports_services($provider, $service_ids)) {
                $provider_list[] = $provider['id'];
            }
        }

        return $provider_list;
    }
}
