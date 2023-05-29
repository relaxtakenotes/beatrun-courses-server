<?php
require ('steamauth/steamauth.php');
require ('util.php');

if (!isset($_SESSION['steamid'])) { 
	echo '
	<a href="?login">
	    <button class="button" type="submit">Log-in</button>
	</a> 
';
	return;
}
$admins = json_decode(file_get_contents($admins_dir), true);
if (!isset($admins[$_SESSION['steamid']])) { _error("Not admin."); }

$response = "...";

// yo yanderedev u need to hire me fr

if (array_key_exists('_bcs_addkey', $_POST)) {
	if (strlen($_POST["_bcs_addkey_target"]) <= 0) { 
		$response = "No input."; 
	} else {
		$response = gen_key(str_replace(" ", "", $_POST["_bcs_addkey_target"])); 
		_log_browser("Admin ".$_SESSION['steamid']." generated a key for user ".$_POST["_bcs_addkey_target"].": ".$response);
	}
}

if (array_key_exists('_bcs_rmkey', $_POST)) {
	if (strlen($_POST["_bcs_rmkey_target"]) <= 0) { 
		$response = "No input."; 
	} else {

		$response = rm_key(str_replace(" ", "", $_POST["_bcs_rmkey_target"]));
		_log_browser("Admin ".$_SESSION['steamid']." removed a key from user ".$_POST["_bcs_rmkey_target"]);
	}
    
}

if (array_key_exists('_bcs_lock', $_POST)) {
	if (strlen($_POST["_bcs_lock_target"]) <= 0) { 
		$response = "No input."; 
	} else {
		$response = lock_account(str_replace(" ", "", $_POST["_bcs_lock_target"]));
		_log_browser("Admin ".$_SESSION['steamid']." locked a user with the ID ".$_POST["_bcs_lock_target"].": ".$response);
	}
    
}

if (array_key_exists('_bcs_unlock', $_POST)) {
	if (strlen($_POST["_bcs_unlock_target"]) <= 0) { 
		$response = "No input."; 
	} else {

		$response = unlock_account(str_replace(" ", "", $_POST["_bcs_unlock_target"]));
		_log_browser("Admin ".$_SESSION['steamid']." unlocked a user with the ID ".$_POST["_bcs_unlock_target"].": ".$response);
	}
    
}

if (array_key_exists('_bcs_logs', $_POST)) {
    $response = str_replace("\n", "<br>", file_get_contents($log_dir)); 
}

if (array_key_exists('_bcs_rm_course', $_POST)) {
	if (strlen($_POST["_bcs_rm_course_map"]) <= 0 || strlen($_POST["_bcs_rm_course_code"]) <= 0) { 
		$response = "No input."; 
	} else {
		$response = rm_course(str_replace(" ", "", $_POST["_bcs_rm_course_map"]), str_replace(" ", "", $_POST["_bcs_rm_course_code"]));
		_log_browser("Admin ".$_SESSION['steamid']." removed a course on the map ".$_POST["_bcs_rm_course_map"]." with the code ".$_POST["_bcs_rm_course_code"].": ".$response);
	}
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="css/main.css">
    </head>
    <body>
        <section>
            <div>  
				<form method="post">
					<input type="submit" name="_bcs_addkey" class="button" value="Add Key"/>
					<input type="text" class="button" name="_bcs_addkey_target">
					SteamID64 (this returns the existing authkey, if it exists)
					<br><br>
					<input type="submit" name="_bcs_rmkey" class="button" value="Remove Key"/>
					<input type="text" class="button" name="_bcs_rmkey_target">
					SteamID64
					<br><br>
					<input type="submit" name="_bcs_lock" class="button" value="Lock"/>
					<input type="text" class="button" name="_bcs_lock_target">
					Authkey, SteamID64, IP
					<br><br>
					<input type="submit" name="_bcs_unlock" class="button" value="Unlock"/>
					<input type="text" class="button" name="_bcs_unlock_target">
					Authkey, SteamID64, IP
					<br><br>
					<input type="submit" name="_bcs_rm_course" class="button" value="Remove Course"/>
					<input type="text" class="button" name="_bcs_rm_course_map">
					<input type="text" class="button" name="_bcs_rm_course_code">
					Course Map, Code
					<br><br>
					<input type="submit" name="_bcs_logs" class="button" value="Show logs"/>
				</form>
				<br>
				Server response:<br><br>
				<code><?php echo $response ?></code>
            </div>
        </section>
    </body>
    <script>
		if ( window.history.replaceState ) {
		  window.history.replaceState( null, null, window.location.href );
		}
	</script>
</html>