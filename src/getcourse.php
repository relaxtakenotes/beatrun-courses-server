<?php

require ('util.php');

getcourse_set_params();
if (!getcourse_headers_are_valid()) { _error("getcourse.php - Invalid headers."); }
if (is_ratelimited()) { _error("getcourse.php - Ratelimited."); }
if (!is_authkey_valid($authkey)) { _error("getcourse.php - Invalid key."); }
if (is_multiaccount(get_userid_from_authkey($authkey))) { 
    _error("getcourse.php - Your account is locked. Contact site administration."); 
}

$path = "courses/".$map."/".$code.".txt";
$body = file_get_contents($path);
$decoded_body = json_decode($body, true);
if (!$decoded_body) {_error("getcourse.php - Invalid course (not json)"); }
if (!body_is_valid($decoded_body)) { _error("getcourse.php - Invalid course (invalid signature)"); }

print($body);
_log("getcourse.php - Served a course under the name: ".sanitize($decoded_body[4], false, true));


?>