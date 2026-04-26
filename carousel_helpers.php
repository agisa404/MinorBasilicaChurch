
<?php

function load_carousel_images(mysqli $conn): array
{
    $images = [];
    for ($slot = 1; $slot <= 6; $slot++) {
        $images[$slot] = null;
    }

    $res = $conn->query('SELECT slot, image_path FROM carousel_images ORDER BY slot ASC');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $slot = (int)($row['slot'] ?? 0);
            if ($slot >= 1 && $slot <= 6) {
                $images[$slot] = $row['image_path'] ?? null;
            }
        }
        $res->free();
    }

    return $images;
}

function update_carousel_images(mysqli $conn, array $currentImages): array
{
    $targetDir = __DIR__ . '/uploads/carousel';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    for ($slot = 1; $slot <= 6; $slot++) {
        $clear = !empty($_POST['clear_slot_' . $slot]);
        $fileKey = 'carousel_slot_' . $slot;
        $hasUpload = isset($_FILES[$fileKey]) && ($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        $oldPath = $currentImages[$slot] ?? null;
        $newPath = null;

        if ($clear) {
            $newPath = null;
        } elseif ($hasUpload) {
            $croppedFlagKey = 'carousel_slot_cropped_' . $slot;
            if (empty($_POST[$croppedFlagKey]) || $_POST[$croppedFlagKey] !== '1') {
                return [false, "Please crop the image for slot {$slot} before saving."];
            }
            if (($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                return [false, "Upload failed for slot {$slot}."];
            }

            $tmpPath = $_FILES[$fileKey]['tmp_name'] ?? '';
            $origName = $_FILES[$fileKey]['name'] ?? '';
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $mime = $tmpPath !== '' ? ($finfo->file($tmpPath) ?: '') : '';

            if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
                return [false, "Invalid image type for slot {$slot}."];
            }

            $fileName = 'carousel_' . $slot . '_' . time() . '.' . $ext;
            $dest = $targetDir . '/' . $fileName;
            if (!move_uploaded_file($tmpPath, $dest)) {
                return [false, "Unable to save image for slot {$slot}."];
            }
            $newPath = 'uploads/carousel/' . $fileName;
        } else {
            continue;
        }

        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $newPath && str_starts_with($oldPath, 'uploads/carousel/')) {
            $oldFs = __DIR__ . '/' . $oldPath;
            if (is_file($oldFs)) {
                @unlink($oldFs);
            }
        }

        if ($newPath === null) {
            $conn->query('UPDATE carousel_images SET image_path = NULL WHERE slot = ' . (int)$slot);
        } else {
            $stmt = $conn->prepare('UPDATE carousel_images SET image_path = ? WHERE slot = ?');
            $stmt->bind_param('si', $newPath, $slot);
            $stmt->execute();
            $stmt->close();
        }
    }

    return [true, 'Carousel images updated.'];
}

?>
