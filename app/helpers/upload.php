<?php
// app/helpers/upload.php
// save_image_upload() checks an uploaded picture and saves it in public/uploads/<folder>/.
// Returns ['path' => 'uploads/<folder>/<random>.png', 'error' => ''] or ['path' => '', 'error' => 'text'].
// The file name is random and the extension comes from the real file type, so nobody can upload a script.
// $size = [width, height] = the SHAPE and the SMALLEST size: the picture must have the same shape (width : height)
// and be at least that big. Bigger is better (sharp when the admin clicks it to zoom); the page scales it down.

const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;   // 5 MB (big, sharp pictures look clear when zoomed)
// Smallest picture size for each upload place = the size of the place where it is shown
// (measured on a 1920 x 1080 screen). Same shape but bigger (e.g. 2x) is allowed and looks sharper.
const IMG_SIZES = [
    'banners'      => [1600, 200],   // user home slider (8 : 1)
    'welcome'      => [600, 225],    // coupon picture on the welcome page (8 : 3)
    'popups'       => [500, 500],    // offer popup (square)
    'services'     => [128, 128],    // service logo, PNG (square)
    'sub-services' => [480, 270],    // service photo on the cards (16 : 9)
    'offers'       => [200, 200],    // offer / notification picture (square)
];
// Profile pictures (admins, employees, users, service providers): any square size from PHOTO_MIN px, at most 2 MB.
const PHOTO_MIN       = 150;
const PHOTO_MAX_BYTES = 2 * 1024 * 1024;
const PHOTO = ['square'];          // pass as $size to save_image_upload()

// Hint under an upload box: "must be exactly W x H ..." + $extra (file types etc.)
function img_hint($folder, array $t, $extra = '')
{
    return sprintf($t['adm_size_hint'], IMG_SIZES[$folder][0], IMG_SIZES[$folder][1]) . $extra;
}

function save_image_upload($field, $folder, array $allowed, array $t, $required = true, array $size = [])
{
    $types = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    $f = $_FILES[$field] ?? null;

    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => '', 'error' => $required ? $t['adm_err_no_file'] : ''];
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return ['path' => '', 'error' => $t['adm_err_upload']];
    }
    $photo = $size === PHOTO;
    if ($f['size'] > ($photo ? PHOTO_MAX_BYTES : UPLOAD_MAX_BYTES)) {
        return ['path' => '', 'error' => $t[$photo ? 'ph_err_too_big' : 'adm_err_too_big']];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $dim = @getimagesize($f['tmp_name']);
    if (!isset($types[$mime]) || !in_array($types[$mime], $allowed, true) || $dim === false) {
        return ['path' => '', 'error' => sprintf($t['adm_err_type'], strtoupper(implode(', ', $allowed)))];
    }
    if ($photo && ($dim[0] !== $dim[1] || $dim[0] < PHOTO_MIN)) {
        return ['path' => '', 'error' => sprintf($t['ph_err_square'], PHOTO_MIN, PHOTO_MIN, $dim[0], $dim[1])];
    }
    if ($size && !$photo) {
        $want    = $size[0] / $size[1];                                   // shape, e.g. 1600 x 200 = 8 : 1
        $shapeOk = abs($dim[0] / max(1, $dim[1]) - $want) <= $want * 0.01;  // 1% difference allowed
    }
    if ($size && !$photo && (!$shapeOk || $dim[0] < $size[0] || $dim[1] < $size[1])) {
        return ['path' => '', 'error' => sprintf($t['adm_err_size'], $size[0], $size[1], $dim[0], $dim[1])];
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
