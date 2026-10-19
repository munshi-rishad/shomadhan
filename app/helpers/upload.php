<?php
// app/helpers/upload.php
// save_image_upload() checks an uploaded picture and saves it in public/uploads/<folder>/.
// Returns ['path' => 'uploads/<folder>/<random>.png', 'error' => ''] or ['path' => '', 'error' => 'text'].
// The file name is random and the extension comes from the real file type, so nobody can upload a script.

const UPLOAD_MAX_BYTES = 3 * 1024 * 1024;   // 3 MB

function save_image_upload($field, $folder, array $allowed, array $t, $required = true)
{
    $types = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    $f = $_FILES[$field] ?? null;

    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => '', 'error' => $required ? $t['adm_err_no_file'] : ''];
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return ['path' => '', 'error' => $t['adm_err_upload']];
    }
    if ($f['size'] > UPLOAD_MAX_BYTES) {
        return ['path' => '', 'error' => $t['adm_err_too_big']];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($types[$mime]) || !in_array($types[$mime], $allowed, true) || @getimagesize($f['tmp_name']) === false) {
        return ['path' => '', 'error' => sprintf($t['adm_err_type'], strtoupper(implode(', ', $allowed)))];
    }
    $name = bin2hex(random_bytes(12)) . '.' . $types[$mime];
    $rel  = 'uploads/' . $folder . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], ROOT_PATH . '/public/' . $rel)) {
        return ['path' => '', 'error' => $t['adm_err_upload']];
    }
    return ['path' => $rel, 'error' => ''];
}

// Deletes a file we uploaded earlier (only inside public/uploads/, never the built-in assets)
function delete_upload($rel)
{
    if (is_string($rel) && preg_match('#^uploads/[a-z-]+/[a-f0-9]{24}\.(png|jpg|webp)$#', $rel)) {
        @unlink(ROOT_PATH . '/public/' . $rel);
    }
}
