<?php

require ('util.php');

upload_set_params();
if (!upload_headers_are_valid()) { _error("upload.php - Invalid headers."); }
if (is_ratelimited()) { _error("upload.php - Ratelimited."); }
if (!is_authkey_valid($authkey)) { _error("upload.php - Invalid key."); }
if (is_multiaccount(get_userid_from_authkey($authkey))) { 
    _error("upload.php - Your account is locked. Contact site administration."); 
}

$body = file_get_contents('php://input');
$decoded_body = json_decode($body, true);
if (!$decoded_body) { _error("upload.php - Invalid course (not json)"); }
if (!body_is_valid($decoded_body)) { _error("upload.php - Invalid course (invalid signature)"); }

$path = "courses/".$map."/";

$course_id = generate_code();
$file = $path.$course_id.".txt";

$iter_limit = 500;
$iter = 0;
while (file_exists($file)) {
    if ($iter > $iter_limit) { _error("Hit the iter_limit while looking for a free slot. Try again."); }
    $course_id = generate_code();
    $file = $path.$course_id.".txt";
    $iter++;
}

if (!is_dir($path)) { mkdir($path, 0755, true); }
file_put_contents($file, $body);

_log("Uploaded a course: ".$course_id." (name: ".sanitize($decoded_body[4], false, true).")");
print("Uploaded under the ID: ".$course_id."\n");


?>