<?php
require_once __DIR__ . '/request_helpers.php';
require_once __DIR__ . '/layout.php';
$user = require_login();
$logoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';
$today = service_today();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect_if_invalid_future_datetime_rules([
        ['date' => trim($_POST['date'] ?? ''), 'time' => trim($_POST['time'] ?? ''), 'allow_blank' => false],
    ], 'form_facilities_reservation.php');

    $data = [
        'requesting_group' => trim($_POST['requesting_group'] ?? ''),
        'name_of_head' => trim($_POST['name_of_head'] ?? ''),
        'authorized_representative' => trim($_POST['authorized_representative'] ?? ''),
        'contact_nos' => trim($_POST['contact_nos'] ?? ''),
        'purpose_activity' => trim($_POST['purpose_activity'] ?? ''),
        'participants' => trim($_POST['participants'] ?? ''),
        'facilities' => $_POST['facilities'] ?? [],
        'charges' => trim($_POST['charges'] ?? ''),
        'holy_cave_purposes' => $_POST['holy_cave_purposes'] ?? [],
        'equipment_needed' => trim($_POST['equipment_needed'] ?? ''),
        'total_charges' => trim($_POST['total_charges'] ?? ''),
        'terms_accepted' => isset($_POST['terms_accepted']) ? 'yes' : 'no',
        'signed_date' => trim($_POST['signed_date'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        'book_no' => trim($_POST['book_no'] ?? ''),
        'page_no' => trim($_POST['page_no'] ?? ''),
        'line_no' => trim($_POST['line_no'] ?? ''),
        'amount' => trim($_POST['amount'] ?? ''),
        'official_receipt_no' => trim($_POST['official_receipt_no'] ?? ''),
        'date_of_payment' => trim($_POST['date_of_payment'] ?? ''),
        'endorsed_by' => trim($_POST['endorsed_by'] ?? ''),
        'approved_by' => trim($_POST['approved_by'] ?? ''),
    ];

    $requestId = create_service_request(
        (int)$user['id'],
        'Facilities Reservation',
        'Facilities Reservation Form',
        $data,
        trim($_POST['date'] ?? ''),
        trim($_POST['time'] ?? '')
    );

    notify_user((int)$user['id'], 'Facilities reservation submitted. Reference #' . $requestId . '.');
    set_flash('success', 'Facilities reservation submitted.');
    header('Location: request_success.php?id=' . $requestId);
    exit();
}

render_header('Facilities Reservation Form', 'services_facilities_reservation');
?>
<div class="request-paper-wrap">
    <form method="POST" class="request-paper service-form" data-service-form>
        <div class="paper-header service-form-header">
            <div class="paper-brand">
                <img class="paper-brand-logo" src="<?php echo e($logoPath); ?>" alt="Basilica Logo">
                <div>Basilica Menor de<br>San Pedro Bautista</div>
            </div>
            <div class="service-form-intro">
                <p class="service-eyebrow">Facility Request</p>
                <h1 class="paper-title">Facilities Reservation</h1>
                <p class="paper-subhead">This reservation flow is organized around the event first, with facility and payment details grouped separately for easier completion on mobile and desktop.</p>
            </div>
        </div>

        <div class="service-form-banner">
            <strong>What to prepare:</strong> ministry or group name, event purpose, preferred schedule, and the facility spaces you need.
        </div>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 1</p>
                <h2>Reservation Details</h2>
                <p>Start with the requesting group and the planned activity.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="requesting_group">Requesting ministry / organization / group <span class="service-required">Required</span></label>
                    <input
                        id="requesting_group"
                        class="paper-line-input"
                        type="text"
                        name="requesting_group"
                        autocomplete="organization"
                        placeholder="Name of ministry, organization, or group"
                        value="<?php echo e(service_form_value('requesting_group')); ?>"
                        required
                        data-required-message="Please enter the requesting group or organization.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="name_of_head">Coordinator / head</label>
                    <input id="name_of_head" class="paper-line-input" type="text" name="name_of_head" autocomplete="name" value="<?php echo e(service_form_value('name_of_head')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="authorized_representative">Authorized representative</label>
                    <input id="authorized_representative" class="paper-line-input" type="text" name="authorized_representative" autocomplete="name" value="<?php echo e(service_form_value('authorized_representative')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="contact_nos">Contact number</label>
                    <input id="contact_nos" class="paper-line-input" type="tel" name="contact_nos" autocomplete="tel" inputmode="tel" placeholder="e.g. 0917 123 4567" value="<?php echo e(service_form_value('contact_nos')); ?>" pattern="^[0-9+()\\-\\s]{7,20}$" data-pattern-message="Use digits and common phone symbols only.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="purpose_activity">Purpose or activity <span class="service-required">Required</span></label>
                    <textarea
                        id="purpose_activity"
                        class="paper-line-input"
                        name="purpose_activity"
                        rows="3"
                        placeholder="What activity will be held and what is it for?"
                        required
                        data-required-message="Please describe the purpose or activity."><?php echo e(service_form_value('purpose_activity')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="date">Preferred date <span class="service-required">Required</span></label>
                    <input
                        id="date"
                        class="paper-line-input paper-date"
                        type="date"
                        name="date"
                        data-datetime-future="true"
                        data-datetime-pair="facility-reservation-schedule"
                        data-datetime-role="date"
                        value="<?php echo e(service_form_value('date')); ?>"
                        required
                        data-required-message="Please choose a preferred reservation date.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="time">Preferred time range <span class="service-required">Required</span></label>
                    <select
                        id="time"
                        class="paper-line-input"
                        name="time"
                        data-datetime-future="true"
                        data-datetime-pair="facility-reservation-schedule"
                        data-datetime-role="time-range"
                        required
                        data-required-message="Please choose a preferred time range.">
                        <?php render_service_time_range_select('time', 'Choose a time range'); ?>
                    </select>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="participants">Estimated participants</label>
                    <input id="participants" class="paper-line-input" type="number" min="1" name="participants" inputmode="numeric" placeholder="Approximate headcount" value="<?php echo e(service_form_value('participants')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 2</p>
                <h2>Facilities Needed</h2>
                <p>Select the spaces required for the activity. At least one facility is needed to submit the reservation.</p>
            </div>
            <fieldset class="service-choice-group" data-require-one data-group-message="Please choose at least one facility to reserve.">
                <legend class="service-label">Facilities requested <span class="service-required">Required</span></legend>
                <div class="paper-checklist service-check-grid">
                    <label><input type="checkbox" name="facilities[]" value="St. Francis of Assisi Hall (2nd Floor)" <?php echo service_form_checked('facilities', 'St. Francis of Assisi Hall (2nd Floor)'); ?>> St. Francis of Assisi Hall (2nd Floor)</label>
                    <label><input type="checkbox" name="facilities[]" value="St. Peter of Alcantara (Peach Room)" <?php echo service_form_checked('facilities', 'St. Peter of Alcantara (Peach Room)'); ?>> St. Peter of Alcantara (Peach Room)</label>
                    <label><input type="checkbox" name="facilities[]" value="St. Margaret of Cortona (Green Room)" <?php echo service_form_checked('facilities', 'St. Margaret of Cortona (Green Room)'); ?>> St. Margaret of Cortona (Green Room)</label>
                    <label><input type="checkbox" name="facilities[]" value="St. Louis IX (Blue Room)" <?php echo service_form_checked('facilities', 'St. Louis IX (Blue Room)'); ?>> St. Louis IX (Blue Room)</label>
                    <label><input type="checkbox" name="facilities[]" value="Main Church" <?php echo service_form_checked('facilities', 'Main Church'); ?>> Main Church</label>
                    <label><input type="checkbox" name="facilities[]" value="Holy Cave" <?php echo service_form_checked('facilities', 'Holy Cave'); ?>> Holy Cave</label>
                    <label><input type="checkbox" name="facilities[]" value="Portiuncula Formation and Renewal Hall" <?php echo service_form_checked('facilities', 'Portiuncula Formation and Renewal Hall'); ?>> Portiuncula Formation and Renewal Hall</label>
                    <label><input type="checkbox" name="facilities[]" value="Brother Sun Sister Moon Garden" <?php echo service_form_checked('facilities', 'Brother Sun Sister Moon Garden'); ?>> Brother Sun Sister Moon Garden</label>
                    <label><input type="checkbox" name="facilities[]" value="San Damiano Garden" <?php echo service_form_checked('facilities', 'San Damiano Garden'); ?>> San Damiano Garden</label>
                    <label><input type="checkbox" name="facilities[]" value="Chamber Room" <?php echo service_form_checked('facilities', 'Chamber Room'); ?>> Chamber Room</label>
                </div>
                <p class="form-group-error" aria-live="polite"></p>
            </fieldset>

            <fieldset class="service-choice-group">
                <legend class="service-label">Holy Cave purpose</legend>
                <p class="form-helper">Optional. Only use this if the Holy Cave is part of your reservation.</p>
                <div class="paper-checklist service-check-grid">
                    <label><input type="checkbox" name="holy_cave_purposes[]" value="Mass" <?php echo service_form_checked('holy_cave_purposes', 'Mass'); ?>> Mass</label>
                    <label><input type="checkbox" name="holy_cave_purposes[]" value="Talks/Recollections" <?php echo service_form_checked('holy_cave_purposes', 'Talks/Recollections'); ?>> Talks / recollections</label>
                </div>
            </fieldset>

            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="equipment_needed">Equipment or items needed</label>
                    <textarea id="equipment_needed" class="paper-line-input" name="equipment_needed" rows="3" placeholder="Tables, chairs, projector, sound system, etc."><?php echo e(service_form_value('equipment_needed')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="notes">Notes</label>
                    <textarea id="notes" class="paper-line-input" name="notes" rows="3" placeholder="Add anything the parish office should know"><?php echo e(service_form_value('notes')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 3</p>
                <h2>Terms And Confirmation</h2>
                <p>The reservation can be submitted once the requesting party agrees to the parish terms.</p>
            </div>
            <div class="service-terms-card">
                <ol>
                    <li>The time of usage of parish facilities should be up to 10:00 PM only.</li>
                    <li>Programs must end before the time limit to give enough time for packing and cleaning.</li>
                    <li>Reservations are honored with endorsed forms approved by the Parish Priest.</li>
                    <li>Activities should support major parish initiatives.</li>
                    <li>Users must maintain cleanliness and orderliness.</li>
                    <li>All fees or donations must be given to the Parish Office before use.</li>
                    <li>Requesting parties are liable for any damage caused by misuse.</li>
                </ol>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field service-field-full service-checkbox-field" data-field>
                    <label class="service-checkbox-label" for="terms_accepted">
                        <input
                            id="terms_accepted"
                            type="checkbox"
                            name="terms_accepted"
                            value="1"
                            <?php echo service_form_value('terms_accepted') === '1' ? 'checked' : ''; ?>
                            required
                            data-required-message="Please confirm that you agree to the reservation terms.">
                        <span>I agree to observe the terms and conditions stated above. <span class="service-required">Required</span></span>
                    </label>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="signed_date">Signed date</label>
                    <input id="signed_date" class="paper-line-input paper-date" type="date" name="signed_date" value="<?php echo e(service_form_value('signed_date', $today)); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <details class="service-optional-panel">
            <summary>Charges, payment, and parish office fields</summary>
            <p class="paper-subhead">These are retained for the current workflow but are optional for most users at the initial request stage.</p>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="charges">Charges</label>
                    <input id="charges" class="paper-line-input" type="text" name="charges" value="<?php echo e(service_form_value('charges')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="total_charges">Total charges</label>
                    <input id="total_charges" class="paper-line-input" type="text" name="total_charges" value="<?php echo e(service_form_value('total_charges')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="official_receipt_no">Official receipt number</label>
                    <input id="official_receipt_no" class="paper-line-input" type="text" name="official_receipt_no" value="<?php echo e(service_form_value('official_receipt_no')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_payment">Date of payment</label>
                    <input id="date_of_payment" class="paper-line-input paper-date" type="date" name="date_of_payment" value="<?php echo e(service_form_value('date_of_payment')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="amount">Amount</label>
                    <input id="amount" class="paper-line-input" type="text" name="amount" value="<?php echo e(service_form_value('amount')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="book_no">Book number</label>
                    <input id="book_no" class="paper-line-input" type="text" name="book_no" value="<?php echo e(service_form_value('book_no')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="page_no">Page number</label>
                    <input id="page_no" class="paper-line-input" type="text" name="page_no" value="<?php echo e(service_form_value('page_no')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="line_no">Line number</label>
                    <input id="line_no" class="paper-line-input" type="text" name="line_no" value="<?php echo e(service_form_value('line_no')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="endorsed_by">Endorsed by</label>
                    <input id="endorsed_by" class="paper-line-input" type="text" name="endorsed_by" autocomplete="name" value="<?php echo e(service_form_value('endorsed_by')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="approved_by">Approved by</label>
                    <input id="approved_by" class="paper-line-input" type="text" name="approved_by" autocomplete="name" value="<?php echo e(service_form_value('approved_by')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </details>

        <div class="paper-submit">
            <button class="btn btn-warning" type="submit">Submit reservation request</button>
        </div>
    </form>
</div>
<?php render_footer(); ?>
