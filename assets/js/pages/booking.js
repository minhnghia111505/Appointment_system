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
 * Booking page.
 *
 * This module implements the functionality of the booking page
 *
 * Old Name: FrontendBook
 */
App.Pages.Booking = (function () {
    const $selectDate = $('#select-date');
    const $selectService = $('#select-service');
    const $selectProvider = $('#select-provider');
    const $bookingPackageMode = $('#booking-package-mode');
    const $bookingAddons = $('#booking-addons');
    const $bookingAttendanceMode = $('#booking-attendance-mode');
    const $bookingGroupSize = $('#booking-group-size');
    const $bookingGroupGuests = $('#booking-group-guests');
    const $selectTimezone = $('#select-timezone');
    const $firstName = $('#first-name');
    const $lastName = $('#last-name');
    const $email = $('#email');
    const $phoneNumber = $('#phone-number');
    const $address = $('#address');
    const $city = $('#city');
    const $zipCode = $('#zip-code');
    const $notes = $('#notes');
    const $captchaTitle = $('.captcha-title');
    const $availableHours = $('#available-hours');
    const $bookAppointmentSubmit = $('#book-appointment-submit');
    const $deletePersonalInformation = $('#delete-personal-information');
    const $customField1 = $('#custom-field-1');
    const $customField2 = $('#custom-field-2');
    const $customField3 = $('#custom-field-3');
    const $customField4 = $('#custom-field-4');
    const $customField5 = $('#custom-field-5');
    const $displayBookingSelection = $('.display-booking-selection');
    const $rememberMe = $('#remember-me');
    const tippy = window.tippy;
    const moment = window.moment;

    const STORAGE_KEY = 'EasyAppointments.CustomerInfo';
    const COMBO_CATEGORY_PATTERN = /combo/i;

    /**
     * Determines the functionality of the page.
     *
     * @type {Boolean}
     */
    let manageMode = vars('manage_mode') || false;

    /**
     * Determine whether a service is a fixed combo.
     *
     * @param {Object} service Service record.
     *
     * @return {Boolean}
     */
    function isComboService(service) {
        return Boolean(service && COMBO_CATEGORY_PATTERN.test(service.service_category_name || ''));
    }

    /**
     * Get all selected service IDs, including the primary service and add-ons.
     *
     * @return {Number[]}
     */
    function getSelectedServiceIds() {
        const serviceIds = [$selectService.val(), ...$bookingAddons.find(':checked').map((index, element) => element.value).get()]
            .filter(Boolean)
            .map(Number);

        return [...new Set(serviceIds)];
    }

    /**
     * Calculate the selected services' combined price.
     *
     * @return {Number}
     */
    function calculateTotalPrice() {
        const selectedIds = getSelectedServiceIds();

        return vars('available_services')
            .filter((service) => selectedIds.includes(Number(service.id)))
            .reduce((total, service) => total + Number(service.price || 0), 0);
    }

    /**
     * Calculate the selected services' combined duration.
     *
     * @return {Number}
     */
    function calculateTotalDuration() {
        const selectedIds = getSelectedServiceIds();

        return vars('available_services')
            .filter((service) => selectedIds.includes(Number(service.id)))
            .reduce((total, service) => total + Number(service.duration || 0), 0);
    }

    /**
     * Filter the main service options to match the selected booking mode.
     */
    function applyBookingPackageMode() {
        const mode = $bookingPackageMode.val();
        const previousServiceId = $selectService.val();

        $selectService.find('option').each((index, option) => {
            if (!option.value) {
                $(option).prop('hidden', false);
                return;
            }

            const service = vars('available_services').find(
                (availableService) => Number(availableService.id) === Number(option.value),
            );

            $(option).prop('hidden', mode === 'combo' ? !isComboService(service) : isComboService(service));
        });

        if ($selectService.find(`option[value="${previousServiceId}"]:not([hidden])`).length) {
            $selectService.val(previousServiceId);
        } else {
            $selectService.val('');
        }

        renderBookingAddons();
    }

    /**
     * Render add-on checkboxes from the available standalone services.
     */
    function renderBookingAddons() {
        const primaryServiceId = Number($selectService.val());
        const selectedAddonIds = $bookingAddons.find(':checked').map((index, element) => Number(element.value)).get();
        const addons = vars('available_services').filter(
            (service) => !isComboService(service) && Number(service.id) !== primaryServiceId,
        );

        $bookingAddons.empty();
        $('#booking-addons-section').prop('hidden', !$selectService.val() || !addons.length);

        addons.forEach((service) => {
            const checked = selectedAddonIds.includes(Number(service.id));
            const $input = $('<input/>', {
                type: 'checkbox',
                class: 'form-check-input booking-addon',
                id: `booking-addon-${service.id}`,
                value: service.id,
                checked,
            });
            const $label = $('<label/>', {
                class: 'form-check-label d-flex justify-content-between gap-3',
                for: `booking-addon-${service.id}`,
            });

            $('<span/>', {text: service.name}).appendTo($label);
            $('<span/>', {
                class: 'text-nowrap text-muted',
                text: `${Number(service.duration)} phút · ${Number(service.price).toLocaleString('vi-VN')} ₫`,
            }).appendTo($label);

            $('<div/>', {class: 'form-check booking-addon-option'})
                .append($input, $label)
                .appendTo($bookingAddons);
        });
    }

    /**
     * Update the live package total.
     */
    function updateBookingPackageSummary() {
        const serviceIds = getSelectedServiceIds();
        const services = vars('available_services').filter((service) => serviceIds.includes(Number(service.id)));
        const $summary = $('#booking-package-summary');

        $summary.prop('hidden', !services.length);
        $('#booking-package-summary-items').text(services.map((service) => service.name).join(' + '));
        $('#booking-package-total-duration').text(`${calculateTotalDuration()} phút`);
        $('#booking-package-total-price').text(`${calculateTotalPrice().toLocaleString('vi-VN')} ₫`);
    }

    /**
     * Whether the customer is configuring multiple seats.
     *
     * @return {Boolean}
     */
    function isGroupBooking() {
        return $bookingAttendanceMode.val() === 'group';
    }

    /**
     * List the selected services for one group guest.
     *
     * @param {jQuery} $guest Guest configuration card.
     *
     * @return {Number[]}
     */
    function getGroupGuestServiceIds($guest) {
        const primaryId = Number($guest.find('.booking-group-primary-service').val());
        const addonIds = $guest
            .find('.booking-group-addon:checked')
            .map((index, element) => Number(element.value))
            .get();

        return [...new Set([primaryId, ...addonIds].filter(Boolean))];
    }

    /**
     * Build the per-guest service and barber configuration forms.
     */
    function renderGroupGuests() {
        const guestCount = Number($bookingGroupSize.val()) || 2;
        const services = vars('available_services');
        const addons = services.filter((service) => !isComboService(service));
        $bookingGroupGuests.empty();

        for (let index = 0; index < guestCount; index++) {
            const $card = $('<div/>', {class: 'booking-group-guest border rounded p-3 mb-3', 'data-guest-index': index});
            const $heading = $('<h3/>', {class: 'fs-5', text: `Khách ${index + 1}`});
            const $name = $('<input/>', {
                class: 'form-control mb-3 booking-group-guest-name',
                maxlength: 256,
                value: `Khách ${index + 1}`,
                'aria-label': `Tên khách ${index + 1}`,
            });
            const $serviceSelect = $('<select/>', {class: 'form-select mb-3 booking-group-primary-service'});
            $serviceSelect.append(new Option('Chọn dịch vụ chính', ''));
            services.forEach((service) => $serviceSelect.append(new Option(service.name, service.id)));

            const $addonList = $('<div/>', {class: 'booking-group-guest-services mb-3'});
            addons.forEach((service) => {
                const id = `booking-group-${index}-addon-${service.id}`;
                const $checkbox = $('<input/>', {
                    class: 'form-check-input booking-group-addon me-2',
                    id,
                    type: 'checkbox',
                    value: service.id,
                });
                const $label = $('<label/>', {
                    class: 'form-check-label d-flex justify-content-between gap-2 w-100',
                    for: id,
                }).append(
                    $('<span/>', {text: service.name}),
                    $('<span/>', {
                        class: 'text-nowrap text-muted',
                        text: `${Number(service.duration)} phút · ${Number(service.price).toLocaleString('vi-VN')} ₫`,
                    }),
                );
                $addonList.append($('<div/>', {class: 'form-check booking-addon-option'}).append($checkbox, $label));
            });

            const $provider = $('<select/>', {class: 'form-select booking-group-provider'});
            $provider.append(new Option('Thợ bất kỳ', 'any-provider'));
            vars('available_providers').forEach((provider) => {
                $provider.append(new Option(`${provider.first_name} ${provider.last_name}`.trim(), provider.id));
            });

            $card.append(
                $heading,
                $('<label/>', {class: 'form-label', text: 'Tên khách (không bắt buộc)'}),
                $name,
                $('<label/>', {class: 'form-label', text: 'Dịch vụ chính'}),
                $serviceSelect,
                $('<div/>', {class: 'small text-muted mb-2', text: 'Dịch vụ bổ sung'}),
                $addonList,
                $('<label/>', {class: 'form-label', text: 'Thợ phụ trách'}),
                $provider,
            );
            $bookingGroupGuests.append($card);
            updateGroupGuestOptions($card);
        }

        updateGroupBookingSummary();
    }

    /**
     * Keep each guest's barber choices limited to providers covering all selected services.
     *
     * @param {jQuery} $guest Guest configuration card.
     */
    function updateGroupGuestOptions($guest) {
        const serviceIds = getGroupGuestServiceIds($guest);
        const $provider = $guest.find('.booking-group-provider');
        const selectedProvider = $provider.val();

        $provider.empty().append(new Option('Thợ bất kỳ', 'any-provider'));

        vars('available_providers')
            .filter((provider) =>
                serviceIds.every((serviceId) => provider.services.map(Number).includes(Number(serviceId))),
            )
            .forEach((provider) => {
                $provider.append(new Option(`${provider.first_name} ${provider.last_name}`.trim(), provider.id));
            });

        $provider.val($provider.find(`option[value="${selectedProvider}"]`).length ? selectedProvider : 'any-provider');
    }

    /**
     * Update the visible estimate for all group seats.
     */
    function updateGroupBookingSummary() {
        let totalPrice = 0;
        let totalDuration = 0;
        const summary = [];

        $bookingGroupGuests.children('.booking-group-guest').each((index, guestElement) => {
            const $guest = $(guestElement);
            const selectedIds = getGroupGuestServiceIds($guest);
            const services = vars('available_services').filter((service) => selectedIds.includes(Number(service.id)));
            const price = services.reduce((total, service) => total + Number(service.price || 0), 0);
            const duration = services.reduce((total, service) => total + Number(service.duration || 0), 0);
            totalPrice += price;
            totalDuration += duration;
            summary.push(
                `<div class="d-flex justify-content-between"><span>Khách ${index + 1}: ${services
                    .map((service) => App.Utils.String.escapeHtml(service.name))
                    .join(' + ') || 'Chưa chọn dịch vụ'}</span><span>${duration} phút · ${price.toLocaleString('vi-VN')} ₫</span></div>`,
            );
        });

        $('#booking-group-summary').html(
            `${summary.join('')}<hr><div class="d-flex justify-content-between"><strong>Tổng thời lượng</strong><strong>${totalDuration} phút</strong></div><div class="d-flex justify-content-between"><strong>Tổng tiền</strong><strong>${totalPrice.toLocaleString('vi-VN')} ₫</strong></div>`,
        );
    }

    /**
     * Serialize the services and barber preference selected for each guest.
     *
     * @return {Object[]}
     */
    function getGroupGuestSelections() {
        return $bookingGroupGuests.children('.booking-group-guest').map((index, element) => {
            const $guest = $(element);

            return {
                guest_index: index + 1,
                guest_name: $guest.find('.booking-group-guest-name').val(),
                primary_service_id: Number($guest.find('.booking-group-primary-service').val()),
                service_ids: getGroupGuestServiceIds($guest),
                provider_id: $guest.find('.booking-group-provider').val(),
            };
        }).get();
    }

    /**
     * Whether each configured seat has a primary service selected.
     *
     * @return {Boolean}
     */
    function hasCompleteGroupServices() {
        return $bookingGroupGuests.children('.booking-group-guest').length >= 2 &&
            $bookingGroupGuests.find('.booking-group-primary-service').toArray().every((element) => Boolean($(element).val()));
    }

    /**
     * Build the server payload for the chosen group slot.
     *
     * @return {Object}
     */
    function buildGroupBookingPayload() {
        const selectedDate = App.Utils.UI.getDateTimePickerValue($selectDate);

        return {
            booking_date: moment(selectedDate).format('YYYY-MM-DD'),
            start_time: $('.selected-hour').data('value'),
            timezone: $selectTimezone.val(),
            notes: $notes.val(),
            guests: getGroupGuestSelections(),
        };
    }

    /**
     * Return barbers who can perform every selected service.
     *
     * @param {Number[]} serviceIds Selected service IDs.
     *
     * @return {Object[]}
     */
    function filterAvailableBarbers(serviceIds = getSelectedServiceIds()) {
        return vars('available_providers').filter((provider) =>
            serviceIds.every((serviceId) => provider.services.map(Number).includes(Number(serviceId))),
        );
    }

    /**
     * Rebuild the provider list so every selected service is covered.
     *
     * @param {String|null} previousProviderId Provider to keep if still qualified.
     */
    function updateProviderOptions(previousProviderId = $selectProvider.val()) {
        const serviceIds = getSelectedServiceIds();

        $selectProvider.parent().prop('hidden', !serviceIds.length);
        $selectProvider.empty().append(new Option(lang('please_select'), ''));

        const qualifiedProviders = filterAvailableBarbers(serviceIds);

        qualifiedProviders.forEach((provider) => {
            $selectProvider.append(new Option(provider.first_name + ' ' + provider.last_name, provider.id));
        });

        if (serviceIds.length && !qualifiedProviders.length) {
            $selectProvider.find('option[value=""]').text('Không có thợ phù hợp với dịch vụ đã chọn');
        }

        const previousProviderCanServe = qualifiedProviders.some(
            (provider) => String(provider.id) === String(previousProviderId),
        );

        if (qualifiedProviders.length === 1) {
            $selectProvider.find('option[value=""]').remove();
        }

        if (qualifiedProviders.length > 1 && Boolean(Number(vars('display_any_provider')))) {
            $(new Option(lang('any_provider'), 'any-provider')).insertAfter($selectProvider.find('option:first'));
        }

        if (previousProviderCanServe) {
            $selectProvider.val(previousProviderId);
        } else if (
            previousProviderId === 'any-provider' &&
            qualifiedProviders.length > 1 &&
            Boolean(Number(vars('display_any_provider')))
        ) {
            $selectProvider.val('any-provider');
        }

        $selectProvider.trigger('change');
    }

    /**
     * Detect the month step.
     *
     * @param previousDateTimeMoment
     * @param nextDateTimeMoment
     *
     * @returns {Number}
     */
    function detectDatepickerMonthChangeStep(previousDateTimeMoment, nextDateTimeMoment) {
        return previousDateTimeMoment.isAfter(nextDateTimeMoment) ? -1 : 1;
    }

    /**
     * Initialize the module.
     */
    function initialize() {
        if (Boolean(Number(vars('display_cookie_notice'))) && window?.cookieconsent) {
            cookieconsent.initialise({
                palette: {
                    popup: {
                        background: '#ffffffbd',
                        text: '#666666',
                    },
                    button: {
                        background: '#429a82',
                        text: '#ffffff',
                    },
                },
                content: {
                    message: lang('website_using_cookies_to_ensure_best_experience'),
                    dismiss: 'OK',
                },
            });

            const $cookieNoticeLink = $('.cc-link');

            $cookieNoticeLink.replaceWith(
                $('<a/>', {
                    'data-bs-toggle': 'modal',
                    'data-bs-target': '#cookie-notice-modal',
                    'href': '#',
                    'class': 'cc-link',
                    'text': $cookieNoticeLink.text(),
                }),
            );
        }

        manageMode = vars('manage_mode');

        // Initialize page's components (tooltips, date pickers etc).
        tippy('[data-tippy-content]');

        let monthTimeout;

        App.Utils.UI.initializeDatePicker($selectDate, {
            inline: true,
            minDate: moment().subtract(1, 'day').set({hours: 23, minutes: 59, seconds: 59}).toDate(),
            maxDate: moment().add(vars('future_booking_limit'), 'days').toDate(),
            onChange: (selectedDates) => {
                App.Http.Booking.getAvailableHours(moment(selectedDates[0]).format('YYYY-MM-DD'));
                App.Pages.Booking.updateConfirmFrame();
            },

            onMonthChange: (selectedDates, dateStr, instance) => {
                $selectDate.parent().fadeTo(400, 0.3); // Change opacity during loading

                if (monthTimeout) {
                    clearTimeout(monthTimeout);
                }

                monthTimeout = setTimeout(() => {
                    if (isGroupBooking() && !manageMode) {
                        $selectDate.parent().fadeTo(400, 1);
                        return;
                    }

                    const previousMoment = moment(instance.selectedDates[0]);

                    const displayedMonthMoment = moment(
                        instance.currentYearElement.value +
                            '-' +
                            String(Number(instance.monthsDropdownContainer.value) + 1).padStart(2, '0') +
                            '-01',
                    );

                    const monthChangeStep = detectDatepickerMonthChangeStep(previousMoment, displayedMonthMoment);

                    App.Http.Booking.getUnavailableDates(
                        $selectProvider.val(),
                        $selectService.val(),
                        displayedMonthMoment.format('YYYY-MM-DD'),
                        monthChangeStep,
                    );
                }, 500);
            },

            onYearChange: (selectedDates, dateStr, instance) => {
                setTimeout(() => {
                    if (isGroupBooking() && !manageMode) {
                        $selectDate.parent().fadeTo(400, 1);
                        return;
                    }

                    const previousMoment = moment(instance.selectedDates[0]);

                    const displayedMonthMoment = moment(
                        instance.currentYearElement.value +
                            '-' +
                            String(Number(instance.monthsDropdownContainer.value) + 1).padStart(2, '0') +
                            '-01',
                    );

                    const monthChangeStep = detectDatepickerMonthChangeStep(previousMoment, displayedMonthMoment);

                    App.Http.Booking.getUnavailableDates(
                        $selectProvider.val(),
                        $selectService.val(),
                        displayedMonthMoment.format('YYYY-MM-DD'),
                        monthChangeStep,
                    );
                }, 500);
            },
        });

        App.Utils.UI.setDateTimePickerValue($selectDate, new Date());

        const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const isTimezoneSupported = $selectTimezone.find(`option[value="${browserTimezone}"]`).length > 0;
        $selectTimezone.val(isTimezoneSupported ? browserTimezone : 'UTC');
        $bookingAttendanceMode.find('option[value="group"]').prop('disabled', Boolean(manageMode));
        if (manageMode) {
            $bookingAttendanceMode.closest('.mb-4').prop('hidden', true);
        }

        // Bind the event handlers (might not be necessary every time we use this class).
        addEventListeners();

        optimizeContactInfoDisplay();

        const selectedServiceId = App.Utils.Url.queryParam('service');
        const selectedService = vars('available_services').find(
            (service) => Number(service.id) === Number(selectedServiceId),
        );

        if (selectedService && !isComboService(selectedService)) {
            $bookingPackageMode.val('individual');
        } else if (!vars('available_services').some(isComboService)) {
            $bookingPackageMode.val('individual');
            $bookingPackageMode.find('option[value="combo"]').prop('disabled', true);
        }

        applyBookingPackageMode();

        const serviceOptionCount = $selectService.find('option:not([hidden])').length;

        if (serviceOptionCount === 2) {
            $selectService.find('option[value=""]').remove();
            const firstServiceId = $selectService.find('option:not([hidden]):first').attr('value');
            $selectService.val(firstServiceId).trigger('change');
        }

        // If the manage mode is true, the appointment data should be loaded by default.
        if (manageMode) {
            applyAppointmentData(vars('appointment_data'), vars('provider_data'), vars('customer_data'));

            $('#wizard-frame-1')
                .css({
                    'visibility': 'visible',
                    'display': 'none',
                })
                .fadeIn();
        } else {
            // Check if a specific service was selected (via URL parameter).
            if (selectedServiceId && $selectService.find(`option[value="${selectedServiceId}"]:not([hidden])`).length > 0) {
                $selectService.val(selectedServiceId);
            }

            $selectService.trigger('change'); // Load the available hours.

            // Check if a specific provider was selected.
            const selectedProviderId = App.Utils.Url.queryParam('provider');

            if (selectedProviderId && $selectProvider.find('option[value="' + selectedProviderId + '"]').length === 0) {
                // Select a service of this provider in order to make the provider available in the select box.
                for (const index in vars('available_providers')) {
                    const provider = vars('available_providers')[index];

                    if (Number(provider.id) === Number(selectedProviderId) && provider.services.length > 0) {
                        $selectService.val(provider.services[0]).trigger('change');
                    }
                }
            }

            if (selectedProviderId && $selectProvider.find('option[value="' + selectedProviderId + '"]').length > 0) {
                $selectProvider.val(selectedProviderId).trigger('change');
            }

            if (
                (selectedServiceId && selectedProviderId) ||
                (vars('available_services').length === 1 && vars('available_providers').length === 1)
            ) {
                if (!selectedServiceId) {
                    $selectService.val(vars('available_services')[0].id).trigger('change');
                }

                if (!selectedProviderId) {
                    $selectProvider.val(vars('available_providers')[0].id).trigger('change');
                }

                $('.active-step').removeClass('active-step');
                $('#step-2').addClass('active-step');
                $('#wizard-frame-1').hide();
                $('#wizard-frame-2').fadeIn();

                $selectService.closest('.wizard-frame').find('.button-next').trigger('click');

                $('#step-1').hide().removeClass('d-inline-block');

                $(document).find('.button-back:first').css('visibility', 'hidden');

                $('#steps .book-step:visible').each((index, bookStepEl) =>
                    $(bookStepEl)
                        .find('strong')
                        .text(index + 1),
                );
            } else {
                $('#wizard-frame-1')
                    .css({
                        'visibility': 'visible',
                        'display': 'none',
                    })
                    .fadeIn();
            }

            prefillFromQueryParam('#first-name', 'first_name');
            prefillFromQueryParam('#last-name', 'last_name');
            prefillFromQueryParam('#email', 'email');
            prefillFromQueryParam('#phone-number', 'phone');
            prefillFromQueryParam('#address', 'address');
            prefillFromQueryParam('#city', 'city');
            prefillFromQueryParam('#zip-code', 'zip');

            // Initialize remember me after prefilling from query params
            initializeRememberMe();
        }
    }

    function prefillFromQueryParam(field, param) {
        const $target = $(field);

        if (!$target.length) {
            return;
        }

        $target.val(App.Utils.Url.queryParam(param));
    }

    /**
     * Remove empty columns and center elements if needed.
     */
    function optimizeContactInfoDisplay() {
        // If a column has only one control shown then move the control to the other column.

        const $firstCol = $('#wizard-frame-3 .field-col:first');
        const $firstColControls = $firstCol.find('.form-control');
        const $secondCol = $('#wizard-frame-3 .field-col:last');
        const $secondColControls = $secondCol.find('.form-control');

        if ($firstColControls.length === 1 && $secondColControls.length > 1) {
            $firstColControls.each((index, controlEl) => {
                $(controlEl).parent().insertBefore($secondColControls.first().parent());
            });
        }

        if ($secondColControls.length === 1 && $firstColControls.length > 1) {
            $secondColControls.each((index, controlEl) => {
                $(controlEl).parent().insertAfter($firstColControls.last().parent());
            });
        }

        // Hide columns that do not have any controls displayed.

        const $fieldCols = $(document).find('#wizard-frame-3 .field-col');

        $fieldCols.each((index, fieldColEl) => {
            const $fieldCol = $(fieldColEl);

            if (!$fieldCol.find('.form-control').length) {
                $fieldCol.hide();
            }
        });
    }

    /**
     * Remove the missing service / provider selection prompt of the first step.
     */
    function clearServiceAndProviderPrompt() {
        $('#select-service-prompt').remove();

        $selectService.removeClass('is-invalid');

        $selectProvider.removeClass('is-invalid');
    }

    /**
     * Add the page event listeners.
     */
    function addEventListeners() {
        $bookingAttendanceMode.on('change', () => {
            const groupMode = isGroupBooking() && !manageMode;

            $('#booking-single-config').prop('hidden', groupMode);
            $('#booking-group-config').prop('hidden', !groupMode);

            if (groupMode) {
                renderGroupGuests();
                App.Http.Booking.clearUnavailableDates();
                $availableHours.empty();
            } else {
                $selectService.trigger('change');
            }

            const date = App.Utils.UI.getDateTimePickerValue($selectDate);

            if (date && groupMode) {
                App.Http.Booking.getAvailableHours(moment(date).format('YYYY-MM-DD'));
            } else if (date && $selectService.val() && $selectProvider.val()) {
                App.Http.Booking.getUnavailableDates(
                    $selectProvider.val(),
                    $selectService.val(),
                    moment(date).format('YYYY-MM-DD'),
                );
            }

            App.Pages.Booking.updateConfirmFrame();
        });

        $bookingGroupSize.on('change', () => {
            renderGroupGuests();
            $availableHours.empty();
            App.Pages.Booking.updateConfirmFrame();
        });

        $bookingGroupGuests.on('change', '.booking-group-primary-service, .booking-group-addon', (event) => {
            const $guest = $(event.target).closest('.booking-group-guest');
            updateGroupGuestOptions($guest);
            updateGroupBookingSummary();
            App.Pages.Booking.updateConfirmFrame();

            const date = App.Utils.UI.getDateTimePickerValue($selectDate);

            if (date) {
                App.Http.Booking.getAvailableHours(moment(date).format('YYYY-MM-DD'));
            }
        });

        $bookingGroupGuests.on('change', '.booking-group-provider', () => {
            App.Pages.Booking.updateConfirmFrame();
            const date = App.Utils.UI.getDateTimePickerValue($selectDate);

            if (date) {
                App.Http.Booking.getAvailableHours(moment(date).format('YYYY-MM-DD'));
            }
        });

        /**
         * Event: Timezone "Changed"
         */
        $selectTimezone.on('change', () => {
            const date = App.Utils.UI.getDateTimePickerValue($selectDate);

            if (!date) {
                return;
            }

            App.Http.Booking.getAvailableHours(moment(date).format('YYYY-MM-DD'));

            App.Pages.Booking.updateConfirmFrame();
        });

        /**
         * Event: Selected Provider "Changed"
         *
         * Whenever the provider changes the available appointment date - time periods must be updated.
         */
        $selectProvider.on('change', () => {
            clearServiceAndProviderPrompt();

            App.Pages.Booking.updateConfirmFrame();

            const date = App.Utils.UI.getDateTimePickerValue($selectDate);

            if (date && $selectService.val()) {
                App.Http.Booking.getAvailableHours(moment(date).format('YYYY-MM-DD'));
            }
        });

        $bookingPackageMode.on('change', () => {
            applyBookingPackageMode();
            $selectService.trigger('change');
        });

        $bookingAddons.on('change', '.booking-addon', () => {
            const previousProviderId = $selectProvider.val();

            updateBookingPackageSummary();
            updateProviderOptions(previousProviderId);
            App.Pages.Booking.updateConfirmFrame();

            const date = App.Utils.UI.getDateTimePickerValue($selectDate);

            if (date && $selectProvider.val()) {
                const selectedDate = moment(date).format('YYYY-MM-DD');

                App.Http.Booking.getUnavailableDates(
                    $selectProvider.val(),
                    $selectService.val(),
                    selectedDate,
                );
                App.Http.Booking.getAvailableHours(selectedDate);
            }
        });

        /**
         * Event: Selected Service "Changed"
         *
         * When the user clicks on a service, its available providers should
         * become visible.
         */
        $selectService.on('change', (event) => {
            clearServiceAndProviderPrompt();

            const serviceId = $selectService.val();
            const previousProviderId = $selectProvider.val();

            renderBookingAddons();
            updateBookingPackageSummary();
            updateProviderOptions(previousProviderId);
            App.Pages.Booking.updateConfirmFrame();

            App.Pages.Booking.updateServiceDescription(serviceId);
        });

        /**
         * Event: Next Step Button "Clicked"
         *
         * This handler is triggered every time the user pressed the "next" button on the book wizard.
         * Some special tasks might be performed, depending on the current wizard step.
         */
        $('.button-next').on('click', (event) => {
            const $target = $(event.currentTarget);

            // If we are on the first step and there is no provider selected do not continue with the next step.
            if ($target.attr('data-step_index') === '1') {
                if (isGroupBooking() && !manageMode) {
                    let missingService = false;
                    $bookingGroupGuests.find('.booking-group-primary-service').each((index, element) => {
                        if (!$(element).val()) {
                            $(element).addClass('is-invalid');
                            missingService = true;
                        } else {
                            $(element).removeClass('is-invalid');
                        }
                    });

                    if (missingService) {
                        if (!$('#select-service-prompt').length) {
                            $('<div/>', {
                                id: 'select-service-prompt',
                                class: 'text-danger mb-4',
                                text: 'Vui lòng chọn dịch vụ chính cho từng khách.',
                            }).appendTo('#wizard-frame-1 .frame-content .col');
                        }
                        return;
                    }
                } else if (!$selectProvider.val()) {
                    if (!$selectService.val()) {
                        $selectService.addClass('is-invalid');
                    } else {
                        $selectProvider.addClass('is-invalid');
                    }

                    if (!$('#select-service-prompt').length) {
                        $('<div/>', {
                            'id': 'select-service-prompt',
                            'class': 'text-danger mb-4',
                            'text': lang('service_and_provider_missing'),
                        }).appendTo('#wizard-frame-1 .frame-content .col');
                    }

                    return;
                }
            }

            // If we are on the first step, fetch unavailable dates and available hours for step 2.
            if ($target.attr('data-step_index') === '1') {
                // While in manage mode the appointment date must stay preselected, instead of resetting to today.
                const appointmentDate = manageMode ? App.Utils.UI.getDateTimePickerValue($selectDate) : null;

                const selectedMoment = appointmentDate ? moment(appointmentDate) : moment();

                App.Utils.UI.setDateTimePickerValue($selectDate, selectedMoment.toDate());

                if (isGroupBooking() && !manageMode) {
                    App.Http.Booking.getAvailableHours(selectedMoment.format('YYYY-MM-DD'));
                } else {
                    App.Http.Booking.getUnavailableDates(
                        $selectProvider.val(),
                        $selectService.val(),
                        selectedMoment.format('YYYY-MM-DD'),
                    );

                    if (appointmentDate) {
                        // The unavailable dates request will not refresh the hours while in manage mode.
                        App.Http.Booking.getAvailableHours(selectedMoment.format('YYYY-MM-DD'));
                    }
                }
            }

            // If we are on the 2nd tab then the user should have an appointment hour selected.
            if ($target.attr('data-step_index') === '2') {
                if (!$('.selected-hour').length) {
                    if (!$('#select-hour-prompt').length) {
                        $('<div/>', {
                            'id': 'select-hour-prompt',
                            'class': 'text-danger mb-4',
                            'text': lang('appointment_hour_missing'),
                        }).prependTo('#available-hours');
                    }
                    return;
                }
            }

            // If we are on the 3rd tab then we will need to validate the user's input before proceeding to the next
            // step.
            if ($target.attr('data-step_index') === '3') {
                if (!App.Pages.Booking.validateCustomerForm()) {
                    return; // Validation failed, do not continue.
                } else {
                    App.Pages.Booking.updateConfirmFrame();

                    // Initialize ALTCHA widget if present
                    if ($('#altcha-widget').length && App.Utils.Altcha) {
                        App.Utils.Altcha.initialize('altcha-widget');
                    }
                }
            }

            // Display the next step tab (uses jquery animation effect).
            const nextTabIndex = parseInt($target.attr('data-step_index')) + 1;

            // Update step indicator immediately
            $('.active-step').removeClass('active-step');
            $('#step-' + nextTabIndex).addClass('active-step');

            $target
                .parents()
                .eq(1)
                .fadeOut(() => {
                    $('#wizard-frame-' + nextTabIndex).fadeIn();
                });

            // Scroll to the top of the page. On a small screen, especially on a mobile device, this is very useful.
            const scrollingElement = document.scrollingElement || document.body;
            if (window.innerHeight < scrollingElement.scrollHeight) {
                scrollingElement.scrollTop = 0;
            }
        });

        /**
         * Event: Back Step Button "Clicked"
         *
         * This handler is triggered every time the user pressed the "back" button on the
         * book wizard.
         */
        $('.button-back').on('click', (event) => {
            const prevTabIndex = parseInt($(event.currentTarget).attr('data-step_index')) - 1;

            // Update step indicator immediately
            $('.active-step').removeClass('active-step');
            $('#step-' + prevTabIndex).addClass('active-step');

            $(event.currentTarget)
                .parents()
                .eq(1)
                .fadeOut(() => {
                    $('#wizard-frame-' + prevTabIndex).fadeIn();
                });
        });

        /**
         * Event: Available Hour "Click"
         *
         * Triggered whenever the user clicks on an available hour for his appointment.
         */
        $availableHours.on('click', '.available-hour', (event) => {
            $availableHours.find('.selected-hour').removeClass('selected-hour');
            $(event.target).addClass('selected-hour');
            App.Pages.Booking.updateConfirmFrame();
        });

        if (manageMode) {
            /**
             * Event: Cancel Appointment Button "Click"
             *
             * When the user clicks the "Cancel" button this form is going to be submitted. We need
             * the user to confirm this action because once the appointment is cancelled, it will be
             * deleted from the database.
             *
             * @param {jQuery.Event} event
             */
            $('#cancel-appointment').on('click', () => {
                const $cancelAppointmentForm = $('#cancel-appointment-form');

                let $cancellationReason;

                const buttons = [
                    {
                        text: lang('close'),
                        click: (event, messageModal) => {
                            messageModal.hide();
                        },
                    },
                    {
                        text: lang('confirm'),
                        click: () => {
                            if ($cancellationReason.val() === '') {
                                $cancellationReason.css('border', '2px solid #DC3545');
                                return;
                            }
                            $cancelAppointmentForm.find('#hidden-cancellation-reason').val($cancellationReason.val());
                            $cancelAppointmentForm.submit();
                        },
                    },
                ];

                App.Utils.Message.show(
                    lang('cancel_appointment_title'),
                    lang('write_appointment_removal_reason'),
                    buttons,
                );

                $cancellationReason = $('<textarea/>', {
                    'class': 'form-control mt-2',
                    'id': 'cancellation-reason',
                    'rows': '3',
                    'css': {
                        'width': '100%',
                    },
                }).appendTo('#message-modal .modal-body');

                return false;
            });

            $deletePersonalInformation.on('click', () => {
                const buttons = [
                    {
                        text: lang('cancel'),
                        click: (event, messageModal) => {
                            messageModal.hide();
                        },
                    },
                    {
                        text: lang('delete'),
                        click: () => {
                            App.Http.Booking.deletePersonalInformation(vars('customer_token'));
                        },
                    },
                ];

                App.Utils.Message.show(
                    lang('delete_personal_information'),
                    lang('delete_personal_information_prompt'),
                    buttons,
                );
            });
        }

        /**
         * Event: Book Appointment Form "Submit"
         *
         * Before the form is submitted to the server we need to make sure that in the meantime the selected appointment
         * date/time wasn't reserved by another customer or event.
         *
         * @param {jQuery.Event} event
         */
        $bookAppointmentSubmit.on('click', () => {
            const $acceptToTermsAndConditions = $('#accept-to-terms-and-conditions');

            $acceptToTermsAndConditions.removeClass('is-invalid');

            if ($acceptToTermsAndConditions.length && !$acceptToTermsAndConditions.prop('checked')) {
                $acceptToTermsAndConditions.addClass('is-invalid');
                return;
            }

            const $acceptToPrivacyPolicy = $('#accept-to-privacy-policy');

            $acceptToPrivacyPolicy.removeClass('is-invalid');

            if ($acceptToPrivacyPolicy.length && !$acceptToPrivacyPolicy.prop('checked')) {
                $acceptToPrivacyPolicy.addClass('is-invalid');
                return;
            }

            App.Http.Booking.registerAppointment();
        });

        /**
         * Event: Refresh captcha image.
         */
        $captchaTitle.on('click', 'button', () => {
            $('.captcha-image').attr('src', App.Utils.Url.siteUrl('captcha?' + Date.now()));
        });

        $selectDate.on('mousedown', '.ui-datepicker-calendar td', () => {
            setTimeout(() => {
                App.Http.Booking.applyPreviousUnavailableDates();
            }, 300);
        });
    }

    /**
     * This function validates the customer's data input. The user cannot continue without passing all the validation
     * checks.
     *
     * @return {Boolean} Returns the validation result.
     */
    function validateCustomerForm() {
        $('#wizard-frame-3 .is-invalid').removeClass('is-invalid');
        $('#wizard-frame-3 label.text-danger').removeClass('text-danger');

        // Validate required fields.
        let missingRequiredField = false;

        $('.required').each((index, requiredField) => {
            if (!$(requiredField).val()) {
                $(requiredField).addClass('is-invalid');
                missingRequiredField = true;
            }
        });

        if (missingRequiredField) {
            $('#form-message').text(lang('fields_are_required'));
            return false;
        }

        // Validate email address.
        if ($email.val() && !App.Utils.Validation.email($email.val())) {
            $email.addClass('is-invalid');
            $('#form-message').text(lang('invalid_email'));
            return false;
        }

        // Validate phone number.
        const phoneNumber = $phoneNumber.val();

        if (phoneNumber && !App.Utils.Validation.phone(phoneNumber)) {
            $phoneNumber.addClass('is-invalid');
            $('#form-message').text(lang('invalid_phone'));
            return false;
        }

        return true;
    }

    /**
     * Every time this function is executed, it updates the confirmation page with the latest
     * customer settings and input for the appointment booking.
     */
    function updateConfirmFrame() {
        const groupMode = isGroupBooking() && !manageMode;
        const serviceId = $selectService.val();
        const providerId = $selectProvider.val();

        if (groupMode) {
            $displayBookingSelection.text(`Đặt nhóm · ${$bookingGroupGuests.children('.booking-group-guest').length} khách`);
        } else {
            $displayBookingSelection.text(`${lang('service')} │ ${lang('provider')}`); // Notice: "│" is a custom ASCII char

            const serviceOptionText = serviceId ? $selectService.find('option:selected').text() : lang('service');
            const providerOptionText = providerId ? $selectProvider.find('option:selected').text() : lang('provider');

            if (serviceId || providerId) {
                $displayBookingSelection.text(`${serviceOptionText} │ ${providerOptionText}`);
            }
        }

        if (!$availableHours.find('.selected-hour').text()) {
            return; // No time is selected, skip the rest of this function...
        }

        // Render the appointment details

        const selectedDateObject = App.Utils.UI.getDateTimePickerValue($selectDate);
        const selectedDateMoment = moment(selectedDateObject);
        const selectedDate = selectedDateMoment.format('YYYY-MM-DD');
        const selectedTime = $availableHours.find('.selected-hour').text();

        let formattedSelectedDate = '';

        if (selectedDateObject) {
            formattedSelectedDate =
                App.Utils.Date.format(selectedDate, vars('date_format'), vars('time_format'), false) +
                ' ' +
                selectedTime;
        }

        const timezoneOptionText = $selectTimezone.find('option:selected').text();
        if (groupMode) {
            let groupPrice = 0;
            let groupDuration = 0;
            const guestDetails = $bookingGroupGuests.children('.booking-group-guest').map((index, element) => {
                const $guest = $(element);
                const ids = getGroupGuestServiceIds($guest);
                const services = vars('available_services').filter((item) => ids.includes(Number(item.id)));
                const duration = services.reduce((total, item) => total + Number(item.duration || 0), 0);
                const price = services.reduce((total, item) => total + Number(item.price || 0), 0);
                const providerName = $guest.find('.booking-group-provider option:selected').text();

                groupPrice += price;
                groupDuration += duration;

                return `<div class="mb-3"><strong>Khách ${index + 1} · ${App.Utils.String.escapeHtml(
                    $guest.find('.booking-group-guest-name').val(),
                )}</strong><div>${services.map((item) => App.Utils.String.escapeHtml(item.name)).join(' + ')}</div><div class="text-muted">${App.Utils.String.escapeHtml(
                    providerName,
                )} · ${duration} ${lang('minutes')} · ${price.toLocaleString('vi-VN')} VNĐ</div></div>`;
            }).get().join('');

            $('#appointment-details').html(`
                <div>
                    <div class="mb-2 fw-bold fs-3">Đặt lịch nhóm</div>
                    ${guestDetails}
                    <div class="mb-2"><i class="fas fa-calendar-day me-2"></i>${formattedSelectedDate}</div>
                    <div class="mb-2"><i class="fas fa-clock me-2"></i>${groupDuration} ${lang('minutes')} (mỗi khách có thời lượng riêng)</div>
                    <div class="mb-2"><i class="fas fa-globe me-2"></i>${App.Utils.String.escapeHtml(timezoneOptionText)}</div>
                    <div class="mb-2"><i class="fas fa-cash-register me-2"></i>${groupPrice.toLocaleString('vi-VN')} VNĐ</div>
                </div>
            `);
        } else {
            const service = vars('available_services').find(
                (availableService) => Number(availableService.id) === Number(serviceId),
            );
            const selectedServices = vars('available_services').filter((availableService) =>
                getSelectedServiceIds().includes(Number(availableService.id)),
            );

            if (!service) {
                return;
            }

            const totalDuration = calculateTotalDuration();
            const totalPrice = calculateTotalPrice();
            const serviceNames = selectedServices
                .map((selectedService) => App.Utils.String.escapeHtml(selectedService.name))
                .join(' + ');

            $('#appointment-details').html(`
                <div>
                    <div class="mb-2 fw-bold fs-3">${serviceNames || App.Utils.String.escapeHtml(
                        $selectService.find('option:selected').text(),
                    )}</div>
                    <div class="mb-2 fw-bold text-muted">${App.Utils.String.escapeHtml(
                        $selectProvider.find('option:selected').text(),
                    )}</div>
                    <div class="mb-2"><i class="fas fa-calendar-day me-2"></i>${formattedSelectedDate}</div>
                    <div class="mb-2"><i class="fas fa-clock me-2"></i>${totalDuration} ${lang('minutes')}</div>
                    <div class="mb-2"><i class="fas fa-globe me-2"></i>${App.Utils.String.escapeHtml(timezoneOptionText)}</div>
                    <div class="mb-2" ${!totalPrice ? 'hidden' : ''}><i class="fas fa-cash-register me-2"></i>${totalPrice.toLocaleString(
                        'vi-VN',
                    )} ${App.Utils.String.escapeHtml(service.currency || 'VNĐ')}</div>
                </div>
            `);
        }

        // Render the customer information

        const firstName = App.Utils.String.escapeHtml($firstName.val());
        const lastName = App.Utils.String.escapeHtml($lastName.val());
        const fullName = `${firstName} ${lastName}`.trim();
        const email = App.Utils.String.escapeHtml($email.val());
        const phoneNumber = App.Utils.String.escapeHtml($phoneNumber.val());
        const address = App.Utils.String.escapeHtml($address.val());
        const city = App.Utils.String.escapeHtml($city.val());
        const zipCode = App.Utils.String.escapeHtml($zipCode.val());

        const addressParts = [];

        if (city) {
            addressParts.push(city);
        }

        if (zipCode) {
            addressParts.push(zipCode);
        }

        $('#customer-details').html(`
            <div>
                <div class="mb-2 fw-bold fs-3">
                    ${lang('contact_info')}
                </div>
                <div class="mb-2 fw-bold text-muted" ${!fullName ? 'hidden' : ''}>
                    ${fullName}
                </div>
                <div class="mb-2" ${!email ? 'hidden' : ''}>
                    ${email}
                </div>
                <div class="mb-2" ${!phoneNumber ? 'hidden' : ''}>
                    ${phoneNumber}
                </div>
                <div class="mb-2" ${!address ? 'hidden' : ''}>
                    ${address}
                </div>
                <div class="mb-2" ${!addressParts.length ? 'hidden' : ''}>
                    ${addressParts.join(', ')}
                </div>
            </div>
        `);

        // Update appointment form data for submission to server when the user confirms the appointment.

        const data = {};

        data.customer = {
            last_name: $lastName.val(),
            first_name: $firstName.val(),
            email: $email.val(),
            phone_number: $phoneNumber.val(),
            address: $address.val(),
            city: $city.val(),
            zip_code: $zipCode.val(),
            timezone: $selectTimezone.val(),
            custom_field_1: $customField1.val(),
            custom_field_2: $customField2.val(),
            custom_field_3: $customField3.val(),
            custom_field_4: $customField4.val(),
            custom_field_5: $customField5.val(),
        };

        if (groupMode) {
            data.group_booking = buildGroupBookingPayload();
            data.manage_mode = 0;
        } else {
            data.appointment = {
                start_datetime:
                    moment(App.Utils.UI.getDateTimePickerValue($selectDate)).format('YYYY-MM-DD') +
                    ' ' +
                    moment($('.selected-hour').data('value'), 'HH:mm').format('HH:mm') +
                    ':00',
                end_datetime: calculateEndDatetime(),
                notes: $notes.val(),
                is_unavailability: false,
                id_users_provider: $selectProvider.val(),
                id_services: $selectService.val(),
                selected_service_ids: getSelectedServiceIds(),
            };
        }

        data.manage_mode = Number(manageMode);

        if (manageMode) {
            data.appointment.id = vars('appointment_data').id;
            data.appointment_hash = vars('appointment_data').hash;
            data.customer.id = vars('customer_data').id;
        }

        $('input[name="post_data"]').val(JSON.stringify(data));
    }

    /**
     * This method calculates the end datetime of the current appointment.
     *
     * End datetime is depending on the service and start datetime fields.
     *
     * @return {String} Returns the end datetime in string format.
     */
    function calculateEndDatetime() {
        // Add the duration to the start datetime.
        const selectedDate = moment(App.Utils.UI.getDateTimePickerValue($selectDate)).format('YYYY-MM-DD');

        const selectedHour = $('.selected-hour').data('value'); // HH:mm

        const startMoment = moment(selectedDate + ' ' + selectedHour);

        let endMoment;

        const totalDuration = calculateTotalDuration();

        if (totalDuration && startMoment) {
            endMoment = startMoment.clone().add({'minutes': totalDuration});
        } else {
            endMoment = moment();
        }

        return endMoment.format('YYYY-MM-DD HH:mm:ss');
    }

    /**
     * This method applies the appointment's data to the wizard so
     * that the user can start making changes on an existing record.
     *
     * @param {Object} appointment Selected appointment's data.
     * @param {Object} provider Selected provider's data.
     * @param {Object} customer Selected customer's data.
     *
     * @return {Boolean} Returns the operation result.
     */
    function applyAppointmentData(appointment, provider, customer) {
        try {
            // Select the primary service and any saved add-ons.
            const primaryService = vars('available_services').find(
                (service) => Number(service.id) === Number(appointment.id_services),
            );
            $bookingPackageMode.val(primaryService && isComboService(primaryService) ? 'combo' : 'individual');
            applyBookingPackageMode();
            $selectService.val(appointment.id_services).trigger('change');

            const selectedServiceIds = (appointment.selected_service_ids || [appointment.id_services]).map(Number);
            $bookingAddons.find('.booking-addon').each((index, element) => {
                $(element).prop('checked', selectedServiceIds.includes(Number(element.value)));
            });
            renderBookingAddons();
            updateBookingPackageSummary();
            updateProviderOptions(String(appointment.id_users_provider));
            $selectProvider.val(appointment.id_users_provider);

            // Set Appointment Date
            const startMoment = moment(appointment.start_datetime);
            App.Utils.UI.setDateTimePickerValue($selectDate, startMoment.toDate());
            App.Http.Booking.getAvailableHours(startMoment.format('YYYY-MM-DD'));

            // Update unavailable dates while in manage mode

            App.Http.Booking.getUnavailableDates(
                appointment.id_users_provider,
                appointment.id_services,
                startMoment.format('YYYY-MM-DD'),
            );

            // Apply Customer's Data
            $lastName.val(customer.last_name);
            $firstName.val(customer.first_name);
            $email.val(customer.email);
            $phoneNumber.val(customer.phone_number);
            $address.val(customer.address);
            $city.val(customer.city);
            $zipCode.val(customer.zip_code);
            if (customer.timezone) {
                $selectTimezone.val(customer.timezone);
            }
            const appointmentNotes = appointment.notes !== null ? appointment.notes : '';
            $notes.val(appointmentNotes);

            $customField1.val(customer.custom_field_1);
            $customField2.val(customer.custom_field_2);
            $customField3.val(customer.custom_field_3);
            $customField4.val(customer.custom_field_4);
            $customField5.val(customer.custom_field_5);

            App.Pages.Booking.updateConfirmFrame();

            return true;
        } catch (exc) {
            return false;
        }
    }

    /**
     * Update the service description and information.
     *
     * This method updates the HTML content with a brief description of the
     * user selected service (only if available in db). This is useful for the
     * customers upon selecting the correct service.
     *
     * @param {Number} serviceId The selected service record id.
     */
    function updateServiceDescription(serviceId) {
        const $serviceDescription = $('#service-description');

        $serviceDescription.empty();

        const service = vars('available_services').find(
            (availableService) => Number(availableService.id) === Number(serviceId),
        );

        if (!service) {
            return; // Service not found
        }

        // Render the additional service information

        const additionalInfoParts = [];

        if (service.duration) {
            additionalInfoParts.push(`${lang('duration')}: ${service.duration} ${lang('minutes')}`);
        }

        if (Number(service.price) > 0) {
            additionalInfoParts.push(`${lang('price')}: ${Number(service.price).toFixed(2)} ${App.Utils.String.escapeHtml(service.currency)}`);
        }

        if (service.location) {
            additionalInfoParts.push(`${lang('location')}: ${App.Utils.String.escapeHtml(service.location)}`);
        }

        if (additionalInfoParts.length) {
            $(`
                <div class="mb-2 fst-italic">
                    ${additionalInfoParts.join(', ')}
                </div>
            `).appendTo($serviceDescription);
        }

        // Render the service description

        if (service.description?.length) {
            const escapedDescription = App.Utils.String.escapeHtml(service.description);

            const multiLineDescription = escapedDescription.replaceAll('\n', '<br/>');

            $(`
                <div class="text-muted">
                    ${multiLineDescription}
                </div>
            `).appendTo($serviceDescription);
        }
    }

    /**
     * Save customer information to localStorage.
     */
    function saveCustomerInfo() {
        const customerInfo = {
            firstName: $firstName.val(),
            lastName: $lastName.val(),
            email: $email.val(),
            phoneNumber: $phoneNumber.val(),
            address: $address.val(),
            city: $city.val(),
            zipCode: $zipCode.val(),
            customField1: $customField1.val(),
            customField2: $customField2.val(),
            customField3: $customField3.val(),
            customField4: $customField4.val(),
            customField5: $customField5.val(),
            rememberMe: true,
        };

        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(customerInfo));
        } catch (e) {
            console.warn('Could not save customer info to localStorage:', e);
        }
    }

    /**
     * Load customer information from localStorage.
     * GET parameters have priority over stored values.
     */
    function loadCustomerInfo() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);

            if (!stored) {
                return;
            }

            const customerInfo = JSON.parse(stored);

            // Restore remember me checkbox state
            if (customerInfo.rememberMe) {
                $rememberMe.prop('checked', true);
            }

            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);

            // Only populate fields that don't have GET params and are empty
            if (!urlParams.has('first_name') && !$firstName.val()) {
                $firstName.val(customerInfo.firstName || '');
            }
            if (!urlParams.has('last_name') && !$lastName.val()) {
                $lastName.val(customerInfo.lastName || '');
            }
            if (!urlParams.has('email') && !$email.val()) {
                $email.val(customerInfo.email || '');
            }
            if (!urlParams.has('phone_number') && !$phoneNumber.val()) {
                $phoneNumber.val(customerInfo.phoneNumber || '');
            }
            if (!urlParams.has('address') && !$address.val()) {
                $address.val(customerInfo.address || '');
            }
            if (!urlParams.has('city') && !$city.val()) {
                $city.val(customerInfo.city || '');
            }
            if (!urlParams.has('zip_code') && !$zipCode.val()) {
                $zipCode.val(customerInfo.zipCode || '');
            }
            if (!urlParams.has('custom_field_1') && !$customField1.val()) {
                $customField1.val(customerInfo.customField1 || '');
            }
            if (!urlParams.has('custom_field_2') && !$customField2.val()) {
                $customField2.val(customerInfo.customField2 || '');
            }
            if (!urlParams.has('custom_field_3') && !$customField3.val()) {
                $customField3.val(customerInfo.customField3 || '');
            }
            if (!urlParams.has('custom_field_4') && !$customField4.val()) {
                $customField4.val(customerInfo.customField4 || '');
            }
            if (!urlParams.has('custom_field_5') && !$customField5.val()) {
                $customField5.val(customerInfo.customField5 || '');
            }
        } catch (e) {
            console.warn('Could not load customer info from localStorage:', e);
        }
    }

    /**
     * Clear customer information from localStorage.
     */
    function clearCustomerInfo() {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (e) {
            console.warn('Could not clear customer info from localStorage:', e);
        }
    }

    /**
     * Initialize the remember me functionality.
     */
    function initializeRememberMe() {
        // Skip if in manage mode (checkbox not present, use appointment data)
        if (manageMode || !$rememberMe.length) {
            return;
        }

        // Load stored customer info on page load
        loadCustomerInfo();

        // Handle remember me checkbox change
        $rememberMe.on('change', function () {
            if ($(this).prop('checked')) {
                saveCustomerInfo();
            } else {
                clearCustomerInfo();
            }
        });

        // Save customer info before form submission if remember me is checked
        $bookAppointmentSubmit.on('click', function () {
            if ($rememberMe.prop('checked')) {
                saveCustomerInfo();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initialize);

    return {
        manageMode,
        updateConfirmFrame,
        updateServiceDescription,
        validateCustomerForm,
        getSelectedServiceIds,
        calculateTotalPrice,
        calculateTotalDuration,
        filterAvailableBarbers,
        isGroupBooking,
        getGroupGuestSelections,
        hasCompleteGroupServices,
    };
})();
