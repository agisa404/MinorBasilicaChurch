<?php
require_once __DIR__ . '/core.php';

function normalize_date(?string $value): ?string
{
    if (!$value) {
        return null;
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d', $ts) : null;
}

function normalize_time(?string $value): ?string
{
    if (!$value) {
        return null;
    }
    $ts = strtotime($value);
    return $ts ? date('H:i:s', $ts) : null;
}

function service_time_options(): array
{
    return [
        '06:00' => '6:00 AM',
        '07:00' => '7:00 AM',
        '08:00' => '8:00 AM',
        '09:00' => '9:00 AM',
        '10:00' => '10:00 AM',
        '11:00' => '11:00 AM',
        '12:00' => '12:00 PM',
        '13:00' => '1:00 PM',
        '14:00' => '2:00 PM',
        '15:00' => '3:00 PM',
        '16:00' => '4:00 PM',
        '17:00' => '5:00 PM',
        '18:00' => '6:00 PM',
    ];
}

function service_time_range_options(): array
{
    return [
        '08:00-10:00' => '8:00 AM - 10:00 AM',
        '10:00-12:00' => '10:00 AM - 12:00 PM',
        '12:00-14:00' => '12:00 PM - 2:00 PM',
        '14:00-16:00' => '2:00 PM - 4:00 PM',
        '16:00-18:00' => '4:00 PM - 6:00 PM',
        '18:00-20:00' => '6:00 PM - 8:00 PM',
        '20:00-22:00' => '8:00 PM - 10:00 PM',
    ];
}

function service_form_value(string $name, ?string $default = ''): string
{
    $value = $_POST[$name] ?? $default;
    if (is_array($value)) {
        return '';
    }

    return trim((string)$value);
}

function service_form_values(string $name): array
{
    $value = $_POST[$name] ?? [];
    if (!is_array($value)) {
        return [];
    }

    return array_values(array_filter(array_map(
        static fn($item) => trim((string)$item),
        $value
    ), static fn($item) => $item !== ''));
}

function service_form_checked(string $name, string $expected): string
{
    return in_array($expected, service_form_values($name), true) ? 'checked' : '';
}

function service_form_selected(string $name, string $expected, ?string $default = ''): string
{
    return service_form_value($name, $default) === $expected ? 'selected' : '';
}

function service_form_user_name(?array $user): string
{
    $name = trim((string)($user['full_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }

    return trim((string)($user['email'] ?? ''));
}

function service_today(): string
{
    return app_now()->format('Y-m-d');
}

function render_service_time_select(string $name, string $placeholder = 'Select a time'): void
{
    $selected = service_form_value($name);
    // Centralize time options so every service form stays consistent.
    ?>
    <option value=""><?php echo e($placeholder); ?></option>
    <?php foreach (service_time_options() as $value => $label): ?>
        <option value="<?php echo e($value); ?>" <?php echo $selected === $value ? 'selected' : ''; ?>>
            <?php echo e($label); ?>
        </option>
    <?php endforeach; ?>
    <?php
}

function render_service_time_range_select(string $name, string $placeholder = 'Select a time range'): void
{
    $selected = service_form_value($name);
    ?>
    <option value=""><?php echo e($placeholder); ?></option>
    <?php foreach (service_time_range_options() as $value => $label): ?>
        <option value="<?php echo e($value); ?>" <?php echo $selected === $value ? 'selected' : ''; ?>>
            <?php echo e($label); ?>
        </option>
    <?php endforeach; ?>
    <?php
}

function create_service_request(
    int $userId,
    string $formType,
    string $title,
    array $data,
    ?string $requestedDate = null,
    ?string $requestedTime = null
): int {
    global $conn;

    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $dateValue = $requestedDate !== null ? normalize_date($requestedDate) : null;
    $timeValue = $requestedTime !== null ? normalize_time($requestedTime) : null;

    $stmt = $conn->prepare(
        'INSERT INTO service_requests (user_id, form_type, title, details, requested_date, requested_time) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isssss', $userId, $formType, $title, $json, $dateValue, $timeValue);
    $stmt->execute();
    $id = (int)$stmt->insert_id;
    $stmt->close();

    return $id;
}
?>
