<?php
session_start();

/* -------------------------------------------------------------------------

    mcdcmReporter - Radiology Image Management and Reporting System
	Copyright (c) 2016-2020 Kim-Ann Git
  
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.

---------------------------------------------------------------------------*/	

/*
[index.php]
	Login page for mcdcmReporter
*/

// hide all errors
error_reporting(0);
ini_set('display_errors', 0);

// include configuration file
include 'config.php';
?>

<center><br><br><br><br><br><br><br><br><br><br><br><br>
<table width=100% style="background-color: rgba(20, 40, 50, 0.5);" cellpadding=0 cellspacing=0><tr><td align=center>
<br><a href=search.php><img src=img/logo2.jpg border=0></a><br><br>

<?php

//logout
if ($_GET['logout']) {
	session_start();
	session_destroy();
	echo "<font color=red><b>Successfully Logged Out</b></font><br><br>";
}

//handle authentication submission
if (!$_POST['submit']) {
	session_start();
	session_destroy();
} else {
	$IPisAllowed = true;
	if ($allowOnlyWhitelistedIPs) {
		$clientIP = $_SERVER['REMOTE_ADDR'];
		if (!in_array($clientIP, $allowedIPs)) {
			$IPisAllowed = false;
			echo "<font color=red><b>This Workstation Not Allowed</b></font><br><br>\n";
		}
	}
	
	if ($IPisAllowed) {
		$successfulLogin = false;
		$username = $_POST['username'];
		$password = $_POST['password'];

		//open the user CSV
		$file = fopen($userListFile, "r");
		if ($file) {
			while (($line = fgets($file)) !== false) {
				$elements = explode('|', $line); 
				if ($username == $elements[0] && password_verify($password, $elements[1])) {
					$successfulLogin = true;
					$_SESSION['loggedin'] = "1";
					$_SESSION['login'] = $username;
					$_SESSION['fullname'] = $elements[2];
					$_SESSION['level'] = $elements[3];
					$_SESSION['orientation'] = "landscape";
					$_SESSION['defaultviewer'] = "osimis";
					$_SESSION['view'] = "mixed";
					echo('<META http-equiv="REFRESH"  content="0; url=search.php">'); 
					break;
				}
			}
			fclose($file);
		} else {
			echo "Unable to open password file.";
		} 
		
		//wrong username/password - show error
		if ($successfulLogin == false) {
			echo "<font color=red><b>WRONG USERNAME / PASSWORD</b></font><br><br>\n";
		}
	}
}
?>

<form method=POST action=index.php>
Login to continue:<br><br>
<table><tr><td>Username:</td><td><input type=text name=username size=12></td></tr>
<tr><td>Password:</td><td><input type=password name=password size=12></td></tr></table><br>
<input type=submit name=submit value=Submit>
</form>