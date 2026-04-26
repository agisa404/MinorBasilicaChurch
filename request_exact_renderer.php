<?php
function rfv(array $details, string $key): string
{
    $value = $details[$key] ?? '';
    if (is_array($value)) {
        if (!$value) {
            return '-';
        }
        return implode(', ', array_map(static fn($v) => is_array($v) ? json_encode($v) : (string)$v, $value));
    }
    $text = trim((string)$value);
    return $text !== '' ? $text : '-';
}

function render_exact_request_form(array $request, array $details): void
{
    $formType = (string)($request['form_type'] ?? '');
    ?>
    <div class="request-paper-wrap">
        <div class="request-paper">
            <div class="paper-header">
                <div class="paper-brand">
                    <img class="paper-brand-logo" src="612401184_4348220988792023_5812589285034246497_n.jpg" alt="Basilica Logo">
                    <div>Basilica Menor de<br>San Pedro Bautista</div>
                </div>
                <h3 class="paper-title" style="margin:0;font-size:1.05rem;"><?php echo e($request['title'] ?: $formType); ?></h3>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-md-6"><strong>Reference ID:</strong> #<?php echo (int)$request['id']; ?></div>
                <div class="col-md-6"><strong>Status:</strong> <?php echo e($request['status']); ?></div>
                <div class="col-md-6"><strong>Requested Date:</strong> <?php echo e($request['requested_date'] ?: 'N/A'); ?></div>
                <div class="col-md-6"><strong>Requested Time:</strong> <?php echo e($request['requested_time'] ? date('h:i A', strtotime($request['requested_time'])) : 'N/A'); ?></div>
            </div>
            <hr>

            <?php if ($formType === 'Mass Intentions'): ?>
                <p class="paper-section">Mass Intention Type:</p>
                <p><?php echo e(rfv($details, 'intentions')); ?></p>
                <p class="paper-section">Donor / Requestor:</p><p><?php echo e(rfv($details, 'donor')); ?></p>
                <p class="paper-section">Contact No.:</p><p><?php echo e(rfv($details, 'contact_no')); ?></p>
                <p class="paper-section">Amount of Donation:</p><p><?php echo e(rfv($details, 'donation')); ?></p>
                <p class="paper-section">Official Receipt No.:</p><p><?php echo e(rfv($details, 'official_receipt_no')); ?></p>
                <p class="paper-section">Names to include:</p><p><?php echo e(rfv($details, 'names')); ?></p>
                <p class="paper-section">Notes:</p><p><?php echo e(rfv($details, 'notes')); ?></p>
            <?php elseif ($formType === 'Baptism Registration'): ?>
                <p><strong>Name of Child:</strong> <?php echo e(rfv($details, 'name_of_child')); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo e(rfv($details, 'date_of_birth')); ?> | <strong>Place of Birth:</strong> <?php echo e(rfv($details, 'place_of_birth')); ?></p>
                <p><strong>Gender:</strong> <?php echo e(rfv($details, 'gender')); ?> | <strong>Current Age:</strong> <?php echo e(rfv($details, 'current_age')); ?></p>
                <p><strong>Father's Name:</strong> <?php echo e(rfv($details, 'father_name')); ?></p>
                <p><strong>Mother's Maiden Name:</strong> <?php echo e(rfv($details, 'mother_maiden_name')); ?></p>
                <p><strong>Address:</strong> <?php echo e(rfv($details, 'address')); ?></p>
                <p><strong>Contact Nos.:</strong> <?php echo e(rfv($details, 'contact_nos')); ?></p>
                <p><strong>Full Name of Sponsors:</strong> <?php echo e(rfv($details, 'full_name_of_sponsors')); ?></p>
                <p><strong>Additional Sponsors:</strong> <?php echo e(rfv($details, 'additional_sponsors')); ?></p>
                <p><strong>Requirements:</strong> <?php echo e(rfv($details, 'requirements')); ?></p>
                <p><strong>Notes:</strong> <?php echo e(rfv($details, 'notes')); ?></p>
            <?php elseif ($formType === 'Baptismal Request' || $formType === 'Outside Basilica Request'): ?>
                <p><strong>Certificate Types:</strong> <?php echo e(rfv($details, 'certificate_types')); ?></p>
                <p><strong>Name:</strong> <?php echo e(rfv($details, 'name')); ?> | <strong>Date of Birth:</strong> <?php echo e(rfv($details, 'date_of_birth')); ?></p>
                <p><strong>Date of Baptism:</strong> <?php echo e(rfv($details, 'date_of_baptism')); ?></p>
                <p><strong>Date of Confirmation:</strong> <?php echo e(rfv($details, 'date_of_confirmation')); ?></p>
                <p><strong>Date of Marriage:</strong> <?php echo e(rfv($details, 'date_of_marriage')); ?></p>
                <p><strong>Father's Name:</strong> <?php echo e(rfv($details, 'father_name')); ?></p>
                <p><strong>Mother's Name:</strong> <?php echo e(rfv($details, 'mother_name')); ?></p>
                <p><strong>Purpose:</strong> <?php echo e(rfv($details, 'purpose')); ?></p>
                <p><strong>Requested By:</strong> <?php echo e(rfv($details, 'requested_by')); ?></p>
                <p><strong>Marriage Purpose:</strong> <?php echo e(rfv($details, 'purpose_for_marriage')); ?></p>
                <p><strong>Name of Bride/Groom:</strong> <?php echo e(rfv($details, 'name_of_bride_or_groom')); ?></p>
                <p><strong>Date of Wedding:</strong> <?php echo e(rfv($details, 'date_of_wedding')); ?></p>
                <p><strong>Church of Wedding:</strong> <?php echo e(rfv($details, 'church_of_wedding')); ?></p>
            <?php elseif ($formType === 'Confirmation Registration'): ?>
                <p><strong>Name of Candidate:</strong> <?php echo e(rfv($details, 'name_of_candidate')); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo e(rfv($details, 'date_of_birth')); ?> | <strong>Place of Birth:</strong> <?php echo e(rfv($details, 'place_of_birth')); ?></p>
                <p><strong>Date of Baptism:</strong> <?php echo e(rfv($details, 'date_of_baptism')); ?></p>
                <p><strong>Church/Parish of Baptism:</strong> <?php echo e(rfv($details, 'church_parish_of_baptism')); ?></p>
                <p><strong>Father's Name:</strong> <?php echo e(rfv($details, 'father_name')); ?></p>
                <p><strong>Mother's Maiden Name:</strong> <?php echo e(rfv($details, 'mother_maiden_name')); ?></p>
                <p><strong>Sponsor:</strong> <?php echo e(rfv($details, 'name_of_sponsor')); ?></p>
                <p><strong>Requirements:</strong> <?php echo e(rfv($details, 'requirements')); ?></p>
            <?php elseif ($formType === 'Holy Communion Registration'): ?>
                <p><strong>Name of Child:</strong> <?php echo e(rfv($details, 'name_of_child')); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo e(rfv($details, 'date_of_birth')); ?> | <strong>Place of Birth:</strong> <?php echo e(rfv($details, 'place_of_birth')); ?></p>
                <p><strong>Date of Baptism:</strong> <?php echo e(rfv($details, 'date_of_baptism')); ?></p>
                <p><strong>Church/Parish of Baptism:</strong> <?php echo e(rfv($details, 'church_parish_of_baptism')); ?></p>
                <p><strong>Father's Name:</strong> <?php echo e(rfv($details, 'father_name')); ?></p>
                <p><strong>Mother's Maiden Name:</strong> <?php echo e(rfv($details, 'mother_maiden_name')); ?></p>
                <p><strong>Parish:</strong> <?php echo e(rfv($details, 'parish')); ?> | <strong>Contact:</strong> <?php echo e(rfv($details, 'contact_nos')); ?></p>
                <p><strong>Requirements:</strong> <?php echo e(rfv($details, 'requirements')); ?></p>
            <?php elseif ($formType === 'Wedding Registration'): ?>
                <p><strong>Name of Groom:</strong> <?php echo e(rfv($details, 'groom_name')); ?></p>
                <p><strong>Name of Bride:</strong> <?php echo e(rfv($details, 'bride_name')); ?></p>
                <p><strong>Groom Address:</strong> <?php echo e(rfv($details, 'groom_address')); ?></p>
                <p><strong>Bride Address:</strong> <?php echo e(rfv($details, 'bride_address')); ?></p>
                <p><strong>Groom Contact:</strong> <?php echo e(rfv($details, 'groom_contact')); ?> | <strong>Bride Contact:</strong> <?php echo e(rfv($details, 'bride_contact')); ?></p>
                <p><strong>Reservation Fee:</strong> <?php echo e(rfv($details, 'reservation_fee')); ?> | <strong>Official Receipt No.:</strong> <?php echo e(rfv($details, 'receipt_no')); ?></p>
                <p><strong>Requirements:</strong> <?php echo e(rfv($details, 'requirements')); ?></p>
                <p><strong>Notes:</strong> <?php echo e(rfv($details, 'notes')); ?></p>
            <?php elseif ($formType === 'Facilities Reservation'): ?>
                <p><strong>Requesting Group:</strong> <?php echo e(rfv($details, 'requesting_group')); ?></p>
                <p><strong>Name of Head:</strong> <?php echo e(rfv($details, 'name_of_head')); ?></p>
                <p><strong>Authorized Representative:</strong> <?php echo e(rfv($details, 'authorized_representative')); ?></p>
                <p><strong>Purpose/Activity:</strong> <?php echo e(rfv($details, 'purpose_activity')); ?></p>
                <p><strong>No. of Participants:</strong> <?php echo e(rfv($details, 'participants')); ?></p>
                <p><strong>Facilities:</strong> <?php echo e(rfv($details, 'facilities')); ?></p>
                <p><strong>Charges:</strong> <?php echo e(rfv($details, 'charges')); ?> | <strong>Total:</strong> <?php echo e(rfv($details, 'total_charges')); ?></p>
                <p><strong>Notes:</strong> <?php echo e(rfv($details, 'notes')); ?></p>
            <?php else: ?>
                <?php foreach ($details as $key => $value): ?>
                    <p><strong><?php echo e(ucwords(str_replace('_', ' ', (string)$key))); ?>:</strong> <?php echo e(rfv($details, (string)$key)); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

