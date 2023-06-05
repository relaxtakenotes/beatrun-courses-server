<?php

$log_dir = "data/_logs.log";
$authkeys_dir = "data/_keys.json";
$ratelimit_dir = "data/_ratelimit.json";
$account_record_dir = "data/_record.json";
$lock_dir = "data/_locked.json";
$admins_dir = "data/_admins.json";
$webhook_url = ""; // discord webhook logging url

$ratelimit_period = 5;
$ip_list_refresh = 10800; // how fast can a person change their ip on their account. this is 3 hours

$authkey = "";
$map = "";
$code = "";
$ip = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER['REMOTE_ADDR'];
$headers = getallheaders();

function sanitize($string, $force_lowercase = true, $anal = false) {
    $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "=", "+", "[", "{", "]",
                   "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                   "â€”", "â€“", ",", "<", ".", ">", "/", "?");
    $clean = trim(str_replace($strip, "", strip_tags($string)));
    $clean = preg_replace('/\s+/', "-", $clean);
    $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9_\-]/", "", $clean) : $clean ;
    return ($force_lowercase) ?
        (function_exists('mb_strtolower')) ?
            mb_strtolower($clean, 'UTF-8') :
            strtolower($clean) :
        $clean;
}

function getcourse_set_params() {
    global $headers, $authkey, $map, $code;
	$authkey = sanitize($_GET["key"], false, true);
	$map = sanitize($_GET["map"], false, true);
	$code = sanitize($_GET["sharecode"], false, true);
}

function upload_set_params() {
    global $headers, $authkey, $map;
	$authkey = sanitize($headers["Authorization"], false, true);
	$map = sanitize($headers["Game-Map"], false, true);
}

function upload_headers_are_valid() {
	global $headers;
    if ($_SERVER['REQUEST_METHOD'] != "POST" ||
        $headers["Content-Type"] != "text/plain" ||
        $headers["user-agent"] != "Valve/Steam HTTP Client 1.0 (4000)") { return false; } else { return true; }
}

function getcourse_headers_are_valid() {
	global $headers;
    if ($_SERVER['REQUEST_METHOD'] != "GET" ||
        $headers["user-agent"] != "Valve/Steam HTTP Client 1.0 (4000)") { return false; } else { return true; }
}

function is_ratelimited() {
	global $ip, $ratelimit_dir, $ratelimit_period;

    $ratelimit_array = json_decode(file_get_contents($ratelimit_dir), true);

    if (time() - $ratelimit_array[$ip] <= $ratelimit_period) { return true; }

    foreach ($ratelimit_array as $uid => $time) {
        if (time() - $ratelimit_array[$uid] > $ratelimit_period) {
            unset($ratelimit_array[$uid]);
        }
    }

    $ratelimit_array[$ip] = time();

    file_put_contents($ratelimit_dir, json_encode($ratelimit_array, JSON_PRETTY_PRINT));

    return false;
}

function is_authkey_valid($key) {
    global $authkeys_dir;
    $key_array = json_decode(file_get_contents($authkeys_dir), true);

    if (count($key_array) <= 0) { return true; }

    if ($key_array[$key]) { return true; }

    return false;
}

function body_is_valid($body) {
    if (count($body) != 6) { return false; }

    if (!is_array($body[0]) || 
        !is_array($body[1]) || 
        !is_string($body[2]) || 
        !is_float($body[3]) || 
        !is_string($body[4]) || 
        !is_array($body[5])) { return false; }

    return true;
}

function generate_code() {
    $code = "";
    for ($i = 0; $i < 3; $i++) {
        $code .= substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1,10))), 1, 4);
        if ($i == 0 || $i == 1) {$code .= "-";}
    } 
    return strtoupper($code);
}

function generateRandomString($length = 32) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

function debug_to_console($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}

function account_owns_gmod($userid) {
    require ('steamauth/SteamConfig.php'); // here cuz of scope bullshit

    $url = file_get_contents("http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key=".$steamauth['apikey']."&steamid=".$userid."&format=json");
    $content = json_decode($url, true);

    if (!$content["response"]) { return false; }
    if (!$content["response"]["games"]) { return false; }

    foreach ($content["response"]["games"] as $game) {
        if ($game["appid"] == 4000) { return true; }
    }

    return false;
}

function get_userid_from_authkey($authkey) {
    global $authkeys_dir;
    $key_array = json_decode(file_get_contents($authkeys_dir), true);

    if (count($key_array) <= 0) { return ""; }

    if (!isset($key_array[$authkey])) { return ""; }

    return $key_array[$authkey];
}

function get_authkey_from_userid($userid) {
    global $authkeys_dir;

    $key_array = json_decode(file_get_contents($authkeys_dir), true);
    foreach ($key_array as $authkey => $authsteamid) {
        if ($authsteamid === $userid) {
            return $authkey;
        }
    }

    return "";
}

function get_relevant_info($id) {
    $_steamid = "";
    $_authkey = "";
    $type = "";

    if (preg_match('/[^\/][0-9]{8,}/', $id)) {
        $type = "steamid";
    } else {
        $type = "authkey";
    }

    if ($type == "steamid") {
        $_steamid = $id;
        $_authkey = get_authkey_from_userid($_steamid);
    }

    if ($type == "authkey") {
        $_steamid = get_userid_from_authkey($id);
        $_authkey = $id;        
    }

    return [$_steamid, $_authkey];
}

function _lock_account($id) {
    global $lock_dir;
    $locks = json_decode(file_get_contents($lock_dir), true);
    $locks[$id] = true;
    file_put_contents($lock_dir, json_encode($locks, JSON_PRETTY_PRINT));    
}

function lock_account($id) {
    [$_steamid, $_authkey] = get_relevant_info($id);

    _lock_account($_steamid);
    _lock_account($_authkey);

    return "Locked: $_steamid, $_authkey";
}

function _unlock_account($id) {
    global $lock_dir;
    $locks = json_decode(file_get_contents($lock_dir), true);
    unset($locks[$id]);
    file_put_contents($lock_dir, json_encode($locks, JSON_PRETTY_PRINT));
}

function unlock_account($id) {
    [$_steamid, $_authkey] = get_relevant_info($id);

    _unlock_account($_steamid);
    _unlock_account($_authkey);

    return "Unlocked. $_steamid, $_authkey";
}

function is_locked($id) {
    global $lock_dir;

    [$_steamid, $_authkey] = get_relevant_info($id);

    $locks = json_decode(file_get_contents($lock_dir), true);

    if (isset($locks[$_authkey]) || isset($locks[$_steamid])) {
        return true; 
    }

    return false;
}

function gen_key($userid) {
    global $authkeys_dir;
    $keys = json_decode(file_get_contents($authkeys_dir), true);

    foreach ($keys as $authkey => $authsteamid) {
        if ($authsteamid === $userid) {
            return $authkey;
        }
    }

    $key = generateRandomString(32);
    while (isset($keys[$key])) {
        $key = generateRandomString(32);
    }
    $keys[$key] = $userid;

    file_put_contents($authkeys_dir, json_encode($keys, JSON_PRETTY_PRINT));
    return $key;
}

function rm_key($userid) {
    global $authkeys_dir;
    $key_array = json_decode(file_get_contents($authkeys_dir), true);

    foreach ($key_array as $authkey => $authsteamid) {
        if ($authsteamid === $userid) {
            unset($key_array[$authkey]);
        }
    }

    file_put_contents($authkeys_dir, json_encode($key_array, JSON_PRETTY_PRINT));
    return "Removed.";
}

function rm_course($map, $code) {
    $path = "courses/".$map."/".$code.".txt";
    $body = file_get_contents($path);
    $decoded_body = json_decode($body, true);
    if (!$decoded_body) { echo "Invalid course (not json)"; }
    if (!body_is_valid($decoded_body)) { echo "getcourse.php - Invalid course (invalid signature)"; }

    if (!unlink($path)) {
        return "Failed to delete. Check validity of the input.";
    } else {
        return "Deleted.";
    }
}

function is_multiaccount($userid) {
    global $account_record_dir, $ip_list_refresh, $ip, $authkeys_dir;

    if (is_locked($userid)) { return true; }

    $record = json_decode(file_get_contents($account_record_dir), true);

    // sanity... that drains my sanity.
    if (!isset($record[$userid])) { $record[$userid] = []; }
    if (!isset($record[$userid]["ips"])) { $record[$userid]["ips"] = []; }
    if (!isset($record[$userid]["lastchanged"])) { $record[$userid]["lastchanged"] = 0; }

    if (time() - $record[$userid]["lastchanged"] > $ip_list_refresh) {
        $record[$userid]["ips"] = [];
        $record[$userid]["lastchanged"] = time();
    }

    if (!isset($record[$userid]["ips"][$ip])) {
        $record[$userid]["ips"][$ip] = true;
        $record[$userid]["lastchanged"] = time();
    }

    if (count($record[$userid]["ips"]) > 2) {
        lock_account($userid);
        return true;
    }

    file_put_contents($account_record_dir, json_encode($record, JSON_PRETTY_PRINT));

    return false;
}

function _log_webhook($text) {
    global $webhook_url;

    if (strlen($webhook_url) <= 0) { return; }

    $json_data = json_encode(["content" => $text], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    print(curl_exec($ch));
    curl_close($ch);
}

function _log_browser($content) {
    global $log_dir, $ip;

    $date = date("D M j G:i:s T Y");
    $text = "$date - $content (IP: $ip)";

    _log_webhook($text);
    file_put_contents($log_dir, $text."\n", FILE_APPEND);
}

function _log($content) {
    global $log_dir, $authkey, $map, $ip;

    $date = date("D M j G:i:s T Y");
    $steamid = get_userid_from_authkey($authkey);
    $text = "$date - $content (Authkey: $authkey, Map: $map, IP: $ip, SteamID: $steamid)";
    
    _log_webhook($text);
    file_put_contents($log_dir, $text."\n", FILE_APPEND);
}

function _error($reason) {
    print($reason);
    http_response_code(400);
    _log($reason);
    exit;
}

function get_records() {
    global $account_record_dir;
    $record = json_decode(file_get_contents($account_record_dir), true);

    $output = "";

    foreach ($record as $steamid => $data) {
        $ips = [];
        foreach ($data["ips"] as $ip => $boolleaaann) { array_push($ips, $ip); }
        $s_ips = implode(', ', $ips);
        $lastchanged = $data["lastchanged"];
        $output .= "$steamid - IP's: $s_ips; LastChanged: $lastchanged\n";
    }

    return $output;
}

function register_steam_account($userid, $timecreated) {
    global $authkeys_dir;

    $ragh = "(SteamID: $userid, TimeCreated: $timecreated)";

    if (is_multiaccount($userid)) { _log_browser("util.php - Account locked. ".$ragh); return "Your account is locked. Contact site administration."; }

    $keys = json_decode(file_get_contents($authkeys_dir), true);
    foreach ($keys as $akey => $value) {
        if ($value === $userid) {
            $ragh = "(";
            $ragh .= "UserID: ".$userid.", ";
            $ragh .= "timecreated: ".$timecreated.", ";
            $ragh .= "key: ".$akey.")";
            _log_browser("util.php - Existing user logged back in ".$ragh);
            return $akey;
        }
    }

    if (time() - $timecreated < 7890000) { _log_browser("util.php - Too young of an account ".$ragh); return "Account too young. Needs to be at least 3 months old."; }
    if (!account_owns_gmod($userid)) { _log_browser("util.php - GMOD not found ".$ragh); return "Account doesn't have Garry's mod. Make sure your game details are public if you think this is wrong."; }

    $key = generateRandomString(32);
    while (isset($keys[$key])) {
        $key = generateRandomString(32);
    }
    $keys[$key] = $userid;

    file_put_contents($authkeys_dir, json_encode($keys, JSON_PRETTY_PRINT));

    _log_browser("util.php - New user: ".$userid." ".$timecreated." ".$key);

    return $key;
}


?>