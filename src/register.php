<?php
require ('steamauth/steamauth.php');
require ('util.php');
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
            <!-- Login process start -->
            <?php if (!isset($_SESSION['steamid'])) { ?>
                <a href="?login">
                    <button class="button" type="submit">Log-in</button>
                </a>
            	<p>Log-in with steam to register an account and receive the authkey for the course system.<br>Make sure to set your profile and game details to public.<br>Remember that you NEED to own garry's mod and have a sufficiently old account.</p>
                <p>Terms of use:<br>1) Don't share apikeys.<br>2) Don't post courses with names that are deemed offensive.<br>3) Don't spam the servers with garbage courses.<br>Failure to obide by these terms will result in account termination.</p>
            <?php return; } ?>
            <!-- Login process end -->

            <!-- After login. Gives you your apikey here. -->
            <form action="" method="get">
                <button class="button" name="logout" type="submit">Logout</button>
            </form>
            <p>Your apikey is: <b>
            <?php 
                include ('steamauth/userInfo.php'); 
                echo register_steam_account($steamprofile['steamid'], $steamprofile['timecreated']);
            ?>
            </b>
            </div>
        </section>
    </body>
</html>
