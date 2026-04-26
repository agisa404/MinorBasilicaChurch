<?php
require_once __DIR__ . '/request_helpers.php';
require_once __DIR__ . '/layout.php';
$user = require_login();
$logoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';
$requestorName = service_form_user_name($user);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'certificate_types' => $_POST['certificate_types'] ?? [],
        'others' => trim($_POST['others'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
        'date_of_baptism' => trim($_POST['date_of_baptism'] ?? ''),
        'date_of_confirmation' => trim($_POST['date_of_confirmation'] ?? ''),
        'date_of_marriage' => trim($_POST['date_of_marriage'] ?? ''),
        'father_name' => trim($_POST['father_name'] ?? ''),
        'mother_name' => trim($_POST['mother_name'] ?? ''),
        'purpose' => trim($_POST['purpose'] ?? ''),
        'requested_by' => trim($_POST['requested_by'] ?? ''),
        'purpose_for_marriage' => trim($_POST['purpose_for_marriage'] ?? ''),
        'name_of_bride_or_groom' => trim($_POST['name_of_bride_or_groom'] ?? ''),
        'date_of_wedding' => trim($_POST['date_of_wedding'] ?? ''),
        'church_of_wedding' => trim($_POST['church_of_wedding'] ?? '')
    ];

    $requestId = create_service_request(
        (int)$user['id'],
        'Outside Basilica Request',
        'Request Form for Baptismal, Confirmation, Marriage and Other Certification',
        $data
    );

    notify_user((int)$user['id'], 'Outside-basilica request submitted. Reference #' . $requestId . '.');
    set_flash('success', 'Outside-basilica request submitted.');
    header('Location: request_success.php?id=' . $requestId);
    exit();
}

render_header('Outside Basilica Request Form', 'services_outside_basilica_request');
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
                <h1 class="paper-title">Outside Basilica Request</h1>
                <p class="paper-subhead">Request the needed certification or permission for sacramental services outside the basilica using a simpler, mobile-friendly flow.</p>
            </div>
        </div>

        <div class="service-form-banner">
            <strong>Before you submit:</strong> choose the document or permission needed, then add the record details that will help the office verify it.
        </div>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 1</p>
                <h2>Requested Service Or Document</h2>
                <p>Select the related service or document needed for the outside-basilica request.</p>
            </div>
            <fieldset class="service-choice-group" data-require-one data-group-message="Please select at least one requested service or document.">
                <legend class="service-label">Requested item <span class="service-required">Required</span></legend>
                <div class="paper-checklist service-check-grid">
                    <label><input type="checkbox" name="certificate_types[]" value="Certificate of Baptism (Binyag)" <?php echo service_form_checked('certificate_types', 'Certificate of Baptism (Binyag)'); ?>> Certificate of Baptism</label>
                    <label><input type="checkbox" name="certificate_types[]" value="Certificate of Confirmation (Kumpil)" <?php echo service_form_checked('certificate_types', 'Certificate of Confirmation (Kumpil)'); ?>> Certificate of Confirmation</label>
                    <label><input type="checkbox" name="certificate_types[]" value="Certificate of Marriage (Kasal)" <?php echo service_form_checked('certificate_types', 'Certificate of Marriage (Kasal)'); ?>> Certificate of Marriage</label>
                    <label><input type="checkbox" name="certificate_types[]" value="Certification of No Record" <?php echo service_form_checked('certificate_types', 'Certification of No Record'); ?>> Certification of No Record</label>
                    <label><input type="checkbox" name="certificate_types[]" value="Permission for Baptism" <?php echo service_form_checked('certificate_types', 'Permission for Baptism'); ?>> Permission for Baptism</label>
                </div>
                <p class="form-group-error" aria-live="polite"></p>
            </fieldset>
            <div class="service-field" data-field>
                <label class="service-label" for="others">Other request</label>
                <input id="others" class="paper-line-input" type="text" name="others" placeholder="Specify another related request if needed" value="<?php echo e(service_form_value('others')); ?>">
                <p class="form-error" aria-live="polite"></p>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 2</p>
                <h2>Record Details</h2>
                <p>Fill in only the information you already know. Partial details are still useful for follow-up.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="name">Record holder's full name <span class="service-required">Required</span></label>
                    <input
                        id="name"
                        class="paper-line-input"
                        type="text"
                        name="name"
                        autocomplete="name"
                        placeholder="Full name on the church record"
                        value="<?php echo e(service_form_value('name')); ?>"
                        required
                        minlength="3"
                        data-required-message="Please enter the record holder's full name."
                        data-length-message="Please enter the complete recorded name.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_birth">Date of birth</label>
                    <input id="date_of_birth" class="paper-line-input paper-date" type="date" name="date_of_birth" value="<?php echo e(service_form_value('date_of_birth')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_baptism">Date of baptism</label>
                    <input id="date_of_baptism" class="paper-line-input paper-date" type="date" name="date_of_baptism" value="<?php echo e(service_form_value('date_of_baptism')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_confirmation">Date of confirmation</label>
                    <input id="date_of_confirmation" class="paper-line-input paper-date" type="date" name="date_of_confirmation" value="<?php echo e(service_form_value('date_of_confirmation')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_marriage">Date of marriage</label>
                    <input id="date_of_marriage" class="paper-line-input paper-date" type="date" name="date_of_marriage" value="<?php echo e(service_form_value('date_of_marriage')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="father_name">Father's name</label>
                    <input id="father_name" class="paper-line-input" type="text" name="father_name" autocomplete="name" value="<?php echo e(service_form_value('father_name')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="mother_name">Mother's name</label>
                    <input id="mother_name" class="paper-line-input" type="text" name="mother_name" autocomplete="name" value="<?php echo e(service_form_value('mother_name')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 3</p>
                <h2>Purpose And Claimant</h2>
                <p>Use this section to explain why the request is needed and who is making it.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="purpose">Purpose</label>
                    <input id="purpose" class="paper-line-input" type="text" name="purpose" placeholder="e.g. transfer, outside parish requirement" value="<?php echo e(service_form_value('purpose')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="requested_by">Requested by</label>
                    <input id="requested_by" class="paper-line-input" type="text" name="requested_by" autocomplete="name" value="<?php echo e(service_form_value('requested_by', $requestorName)); ?>">
                    <p class="form-helper">Prefilled from your account when available.</p>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <details class="service-optional-panel">
            <summary>Marriage-related details</summary>
            <p class="paper-subhead">Complete this only when the request is connected to a wedding requirement.</p>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="purpose_for_marriage">Purpose for marriage</label>
                    <input id="purpose_for_marriage" class="paper-line-input" type="text" name="purpose_for_marriage" placeholder="e.g. church wedding requirement" value="<?php echo e(service_form_value('purpose_for_marriage')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="name_of_bride_or_groom">Name of bride or groom</label>
                    <input id="name_of_bride_or_groom" class="paper-line-input" type="text" name="name_of_bride_or_groom" autocomplete="name" value="<?php echo e(service_form_value('name_of_bride_or_groom')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_wedding">Date of wedding</label>
                    <input id="date_of_wedding" class="paper-line-input paper-date" type="date" name="date_of_wedding" value="<?php echo e(service_form_value('date_of_wedding')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="church_of_wedding">Church of wedding</label>
                    <input id="church_of_wedding" class="paper-line-input" type="text" name="church_of_wedding" placeholder="Church name" value="<?php echo e(service_form_value('church_of_wedding')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </details>

        <div class="paper-submit">
            <button class="btn btn-warning" type="submit">Submit outside-basilica request</button>
        </div>
    </form>
</div>
<?php render_footer(); ?>
