# beatrun course server
<p>
Pretty advanced beatrun course server recreation.<br><br>
How to use:<br>
1) Hide steamauth and data from public. Don't let people access them and the files inside. <br>
2.1) Remove register.php to disallow public registration using steam accounts, then add authkeys manually to data/_keys.json. <br>
OR<BR>
2.2) Keep public registration and then do the following steps: Go to steamauth, configure the steamconfig.php file and remove the brackets from its name.<br>
3) Remove or keep admin.php to allow online administration. Be sure to put in SteamID64 id's into data/_admins.json beforehand.<br>
4) Put in a proper webhook into util.php if you need discord logging. Otherwise just leave it as an empty string.
_locked.json contains blocked identificators.<br>
_keys.json contains authkeys.<br>
_ratelimit.json is mostly internal, but it keeps track of ratelimiting... duh<br>
_record.json is internal too, keeps track of ips used to log into an account.<br>
_logs.log are obviously logs.
<br><br>
Credits go to this website for providing the headupdaisy font: http://www17.plala.or.jp/xxxxxxx/00ff/
</p>
