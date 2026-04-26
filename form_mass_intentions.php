<?php
require_once __DIR__ . '/request_helpers.php';
require_once __DIR__ . '/layout.php';
$user = require_login();
$logoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';
$requestorName = service_form_user_name($user);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect_if_invalid_future_datetime_rules([
        ['date' => trim($_POST['mass_date'] ?? ''), 'time' => trim($_POST['mass_time'] ?? ''), 'allow_blank' => false],
    ], 'form_mass_intentions.php');

    $namesText = trim($_POST['names_text'] ?? '');
    $names = $namesText === '' ? [] : preg_split('/\r\n|\r|\n/', $namesText);
    $names = array_values(array_filter(array_map('trim', $names), static fn($item) => $item !== ''));

    $data = [
        'intentions' => $_POST['intention'] ?? [],
        'names' => $names,
        'donor' => trim($_POST['donor'] ?? ''),
        'donation' => trim($_POST['donation'] ?? ''),
        'contact_no' => trim($_POST['contact_no'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        'official_receipt_no' => trim($_POST['official_receipt_no'] ?? ''),
        'received_by' => trim($_POST['received_by'] ?? ''),
    ];

    $requestId = create_service_request(
        (int)$user['id'],
        'Mass Intentions',
        'Mass Intentions Form',
        $data,
        $_POST['mass_date'] ?? null,
        $_POST['mass_time'] ?? null
    );

    notify_user((int)$user['id'], 'Mass intentions request submitted. Reference #' . $requestId . '.');
    set_flash('success', 'Mass intentions request submitted.');
    header('Location: request_success.php?id=' . $requestId);
    exit();
}

render_header('Mass Intentions Form', 'services_mass_intentions');
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
                <h1 class="paper-title">Mass Intentions</h1>
                <p class="paper-subhead">This form now keeps the request fast: choose the schedule, select the intention type, then add the name or names to include in the prayer intention.</p>
            </div>
        </div>

        <div class="service-form-banner">
            <strong>Best for quick completion:</strong> schedule first, intention type second, names last.
        </div>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 1</p>
                <h2>Preferred Mass Schedule</h2>
                <p>Pick your preferred date and time so the office can check availability.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="mass_date">Date of Mass <span class="service-required">Required</span></label>
                    <input
                        id="mass_date"
                        class="paper-line-input paper-date"
                        type="date"
                        name="mass_date"
                        data-datetime-future="true"
                        data-datetime-pair="mass-intention-schedule"
                        data-datetime-role="date"
                        value="<?php echo e(service_form_value('mass_date')); ?>"
                        required
                        data-required-message="Please choose the preferred Mass date.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="mass_time">Time of Mass <span class="service-required">Required</span></label>
                    <select
                        id="mass_time"
                        class="paper-line-input"
                        name="mass_time"
                        data-datetime-future="true"
                        data-datetime-pair="mass-intention-schedule"
                        data-datetime-role="time"
                        required
                        data-required-message="Please choose the preferred Mass time.">
                        <?php render_service_time_select('mass_time', 'Choose a time'); ?>
                    </select>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 2</p>
                <h2>Intention Details</h2>
                <p>Select at least one intention type, then list the name or names to include.</p>
            </div>
            <fieldset class="service-choice-group" data-require-one data-group-message="Please select at least one intention type.">
                <legend class="service-label">Mass intention type <span class="service-required">Required</span></legend>
                <div class="paper-checklist service-check-grid">
                    <label><input type="checkbox" name="intention[]" value="Thanksgiving" <?php echo service_form_checked('intention', 'Thanksgiving'); ?>> Thanksgiving</label>
                    <label><input type="checkbox" name="intention[]" value="Birthday" <?php echo service_form_checked('intention', 'Birthday'); ?>> Birthday</label>
                    <label><input type="checkbox" name="intention[]" value="Special Intentions" <?php echo service_form_checked('intention', 'Special Intentions'); ?>> Special intentions</label>
                    <label><input type="checkbox" name="intention[]" value="Healing" <?php echo service_form_checked('intention', 'Healing'); ?>> Healing</label>
                    <label><input type="checkbox" name="intention[]" value="Souls" <?php echo service_form_checked('intention', 'Souls'); ?>> Souls</label>
                    <label><input type="checkbox" name="intention[]" value="Death Anniversary" <?php echo service_form_checked('intention', 'Death Anniversary'); ?>> Death anniversary</label>
                </div>
                <p class="form-group-error" aria-live="polite"></p>
            </fieldset>

            <div class="service-grid">
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="names_text">Name(s) to include</label>
                    <textarea id="names_text" class="paper-line-input" name="names_text" rows="5" placeholder="One name per line&#10;Example: Juan Dela Cruz&#10;Maria Dela Cruz"><?php echo e(service_form_value('names_text')); ?></textarea>
                    <p class="form-helper">One name per line keeps the final printable form easy to read.</p>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="notes">Notes</label>
                    <textarea id="notes" class="paper-line-input" name="notes" rows="3" placeholder="Optional note for the parish office"><?php echo e(service_form_value('notes')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 3</p>
                <h2>Requestor Information</h2>
                <p>Your name is prefilled when available, and contact details stay optional to reduce friction.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="donor">Donor / requestor <span class="service-required">Required</span></label>
                    <input
                        id="donor"
                        class="paper-line-input"
                        type="text"
                        name="donor"
                        autocomplete="name"
                        value="<?php echo e(service_form_value('donor', $requestorName)); ?>"
                        required
                        data-required-message="Please enter the requestor's name.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="contact_no">Contact number</label>
                    <input
                        id="contact_no"
                        class="paper-line-input"
                        type="tel"
                        name="contact_no"
                        autocomplete="tel"
                        inputmode="tel"
                        placeholder="e.g. 0917 123 4567"
                        value="<?php echo e(service_form_value('contact_no')); ?>"
                        pattern="^[0-9+()\\-\\s]{7,20}$"
                        data-pattern-message="Use digits and common phone symbols only.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="donation">Donation amount</label>
                    <input id="donation" class="paper-line-input" type="number" step="0.01" min="0" name="donation" inputmode="decimal" placeholder="0.00" value="<?php echo e(service_form_value('donation')); ?>">
                    <p class="form-helper">Optional. The parish office can also confirm the amount later.</p>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <details class="service-optional-panel">
            <summary>Parish office payment fields</summary>
            <p class="paper-subhead">Leave these blank unless the office already gave you the official receipt details.</p>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="official_receipt_no">Official receipt number</label>
                    <input id="official_receipt_no" class="paper-line-input" type="text" name="official_receipt_no" value="<?php echo e(service_form_value('official_receipt_no')); ?>">
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
            <button class="btn btn-warning" type="submit">Submit Mass intention request</button>
        </div>
    </form>
</div>
<?php render_footer(); ?>
