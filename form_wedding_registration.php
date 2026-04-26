<?php
require_once __DIR__ . '/request_helpers.php';
require_once __DIR__ . '/layout.php';
$user = require_login();
$logoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect_if_invalid_future_datetime_rules([
        ['date' => trim($_POST['wedding_date'] ?? ''), 'time' => trim($_POST['wedding_time'] ?? ''), 'allow_blank' => false],
    ], 'form_wedding_registration.php');

    $data = [
        'groom_name' => trim($_POST['groom_name'] ?? ''),
        'groom_address' => trim($_POST['groom_address'] ?? ''),
        'groom_age' => trim($_POST['groom_age'] ?? ''),
        'bride_name' => trim($_POST['bride_name'] ?? ''),
        'bride_address' => trim($_POST['bride_address'] ?? ''),
        'bride_age' => trim($_POST['bride_age'] ?? ''),
        'groom_contact' => trim($_POST['groom_contact'] ?? ''),
        'bride_contact' => trim($_POST['bride_contact'] ?? ''),
        'reservation_fee' => trim($_POST['reservation_fee'] ?? ''),
        'receipt_no' => trim($_POST['receipt_no'] ?? ''),
        'reservation_date' => trim($_POST['reservation_date'] ?? ''),
        'requirements' => $_POST['requirements'] ?? [],
        'other_requirements' => trim($_POST['other_requirements'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        'book_no' => trim($_POST['book_no'] ?? ''),
        'page_no' => trim($_POST['page_no'] ?? ''),
        'line_no' => trim($_POST['line_no'] ?? ''),
        'amount' => trim($_POST['amount'] ?? ''),
        'minister_of_marriage' => trim($_POST['minister_of_marriage'] ?? ''),
        'received_by' => trim($_POST['received_by'] ?? '')
    ];

    $requestId = create_service_request(
        (int)$user['id'],
        'Wedding Registration',
        'Registration Form for Weddings',
        $data,
        $_POST['wedding_date'] ?? null,
        $_POST['wedding_time'] ?? null
    );

    notify_user((int)$user['id'], 'Wedding registration submitted. Reference #' . $requestId . '.');
    set_flash('success', 'Wedding registration submitted.');
    header('Location: request_success.php?id=' . $requestId);
    exit();
}

render_header('Wedding Registration Form', 'services_wedding_registration');
?>
<div class="request-paper-wrap">
    <form method="POST" class="request-paper service-form" data-service-form>
        <div class="paper-header service-form-header">
            <div class="paper-brand">
                <img class="paper-brand-logo" src="<?php echo e($logoPath); ?>" alt="Basilica Logo">
                <div>Basilica Menor de<br>San Pedro Bautista</div>
            </div>
            <div class="service-form-intro">
                <p class="service-eyebrow">Service Request</p>
                <h1 class="paper-title">Wedding Registration</h1>
                <p class="paper-subhead">The wedding form is now split into short sections, with internal payment and record fields moved out of the way unless they’re needed.</p>
            </div>
        </div>

        <div class="service-form-banner">
            <strong>Quick path:</strong> couple details, preferred schedule, then any requirements already prepared.
        </div>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 1</p>
                <h2>Couple Information</h2>
                <p>Start with the bride and groom details. Contact numbers stay optional to reduce friction.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="groom_name">Groom's full name <span class="service-required">Required</span></label>
                    <input
                        id="groom_name"
                        class="paper-line-input"
                        type="text"
                        name="groom_name"
                        autocomplete="name"
                        placeholder="Full name"
                        value="<?php echo e(service_form_value('groom_name')); ?>"
                        required
                        minlength="3"
                        data-required-message="Please enter the groom's full name.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="groom_age">Groom's age</label>
                    <input id="groom_age" class="paper-line-input" type="number" min="0" max="120" name="groom_age" inputmode="numeric" value="<?php echo e(service_form_value('groom_age')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="groom_address">Groom's address</label>
                    <textarea id="groom_address" class="paper-line-input" name="groom_address" rows="3" autocomplete="street-address" placeholder="Street, barangay, city"><?php echo e(service_form_value('groom_address')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="groom_contact">Groom's contact number</label>
                    <input id="groom_contact" class="paper-line-input" type="tel" name="groom_contact" autocomplete="tel" inputmode="tel" placeholder="e.g. 0917 123 4567" value="<?php echo e(service_form_value('groom_contact')); ?>" pattern="^[0-9+()\\-\\s]{7,20}$" data-pattern-message="Use digits and common phone symbols only.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="bride_name">Bride's full name <span class="service-required">Required</span></label>
                    <input
                        id="bride_name"
                        class="paper-line-input"
                        type="text"
                        name="bride_name"
                        autocomplete="name"
                        placeholder="Full name"
                        value="<?php echo e(service_form_value('bride_name')); ?>"
                        required
                        minlength="3"
                        data-required-message="Please enter the bride's full name.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="bride_age">Bride's age</label>
                    <input id="bride_age" class="paper-line-input" type="number" min="0" max="120" name="bride_age" inputmode="numeric" value="<?php echo e(service_form_value('bride_age')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="bride_address">Bride's address</label>
                    <textarea id="bride_address" class="paper-line-input" name="bride_address" rows="3" autocomplete="street-address" placeholder="Street, barangay, city"><?php echo e(service_form_value('bride_address')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="bride_contact">Bride's contact number</label>
                    <input id="bride_contact" class="paper-line-input" type="tel" name="bride_contact" autocomplete="tel" inputmode="tel" placeholder="e.g. 0917 123 4567" value="<?php echo e(service_form_value('bride_contact')); ?>" pattern="^[0-9+()\\-\\s]{7,20}$" data-pattern-message="Use digits and common phone symbols only.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 2</p>
                <h2>Preferred Wedding Schedule</h2>
                <p>Choose the wedding date and time you want the parish office to review.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="wedding_date">Date of wedding <span class="service-required">Required</span></label>
                    <input
                        id="wedding_date"
                        class="paper-line-input paper-date"
                        type="date"
                        name="wedding_date"
                        data-datetime-future="true"
                        data-datetime-pair="wedding-request-schedule"
                        data-datetime-role="date"
                        value="<?php echo e(service_form_value('wedding_date')); ?>"
                        required
                        data-required-message="Please choose the preferred wedding date.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="wedding_time">Preferred time <span class="service-required">Required</span></label>
                    <select
                        id="wedding_time"
                        class="paper-line-input"
                        name="wedding_time"
                        data-datetime-future="true"
                        data-datetime-pair="wedding-request-schedule"
                        data-datetime-role="time"
                        required
                        data-required-message="Please choose the preferred wedding time.">
                        <?php render_service_time_select('wedding_time', 'Choose a time'); ?>
                    </select>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 3</p>
                <h2>Requirements And Notes</h2>
                <p>Mark the documents already prepared. Anything missing can still be completed later with the parish office.</p>
            </div>
            <fieldset class="service-choice-group">
                <legend class="service-label">Requirements already available</legend>
                <div class="paper-checklist service-check-grid">
                    <label><input type="checkbox" name="requirements[]" value="Certificate of Live Birth" <?php echo service_form_checked('requirements', 'Certificate of Live Birth'); ?>> Certificate of Live Birth</label>
                    <label><input type="checkbox" name="requirements[]" value="Baptismal Certificate" <?php echo service_form_checked('requirements', 'Baptismal Certificate'); ?>> Baptismal Certificate</label>
                    <label><input type="checkbox" name="requirements[]" value="Confirmation Certificate" <?php echo service_form_checked('requirements', 'Confirmation Certificate'); ?>> Confirmation Certificate</label>
                    <label><input type="checkbox" name="requirements[]" value="CENOMAR" <?php echo service_form_checked('requirements', 'CENOMAR'); ?>> CENOMAR</label>
                    <label><input type="checkbox" name="requirements[]" value="Marriage Banns" <?php echo service_form_checked('requirements', 'Marriage Banns'); ?>> Marriage Banns</label>
                    <label><input type="checkbox" name="requirements[]" value="Marriage License or Affidavit of Cohabitation" <?php echo service_form_checked('requirements', 'Marriage License or Affidavit of Cohabitation'); ?>> Marriage License or affidavit</label>
                    <label><input type="checkbox" name="requirements[]" value="Canonical Interview" <?php echo service_form_checked('requirements', 'Canonical Interview'); ?>> Canonical interview</label>
                    <label><input type="checkbox" name="requirements[]" value="Pre-Cana Seminar" <?php echo service_form_checked('requirements', 'Pre-Cana Seminar'); ?>> Pre-Cana seminar</label>
                    <label><input type="checkbox" name="requirements[]" value="Marriage Counseling" <?php echo service_form_checked('requirements', 'Marriage Counseling'); ?>> Marriage counseling</label>
                    <label><input type="checkbox" name="requirements[]" value="Confession" <?php echo service_form_checked('requirements', 'Confession'); ?>> Confession</label>
                </div>
            </fieldset>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="other_requirements">Other requirements</label>
                    <input id="other_requirements" class="paper-line-input" type="text" name="other_requirements" placeholder="Anything else requested by the parish" value="<?php echo e(service_form_value('other_requirements')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="notes">Notes</label>
                    <textarea id="notes" class="paper-line-input" name="notes" rows="4" placeholder="Add clarifications or scheduling notes"><?php echo e(service_form_value('notes')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <details class="service-optional-panel">
            <summary>Reservation, payment, and parish office fields</summary>
            <p class="paper-subhead">These are preserved for existing workflows, but most couples can leave them blank until the office advises otherwise.</p>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="reservation_fee">Reservation fee</label>
                    <input id="reservation_fee" class="paper-line-input" type="number" step="0.01" min="0" name="reservation_fee" inputmode="decimal" placeholder="0.00" value="<?php echo e(service_form_value('reservation_fee')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="receipt_no">Official receipt number</label>
                    <input id="receipt_no" class="paper-line-input" type="text" name="receipt_no" value="<?php echo e(service_form_value('receipt_no')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="reservation_date">Date of reservation</label>
                    <input id="reservation_date" class="paper-line-input paper-date" type="date" name="reservation_date" value="<?php echo e(service_form_value('reservation_date')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="amount">Amount</label>
                    <input id="amount" class="paper-line-input" type="number" step="0.01" min="0" name="amount" inputmode="decimal" placeholder="0.00" value="<?php echo e(service_form_value('amount')); ?>">
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
                    <label class="service-label" for="minister_of_marriage">Minister of marriage</label>
                    <input id="minister_of_marriage" class="paper-line-input" type="text" name="minister_of_marriage" autocomplete="name" value="<?php echo e(service_form_value('minister_of_marriage')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="received_by">Received by</label>
                    <input id="received_by" class="paper-line-input" type="text" name="received_by" autocomplete="name" value="<?php echo e(service_form_value('received_by')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </details>

        <div class="paper-submit">
            <button class="btn btn-warning" type="submit">Submit wedding request</button>
        </div>
    </form>
</div>
<?php render_footer(); ?>
