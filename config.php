<?php
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
[config.php]
	Baseline configuration of mcdcmReporter
*/


// User List
$userListFile = "password.lst";

// Default Password
$defaultPassword = "password";

// Orthanc server address used locally by addPatient.php
// (Be sure to include last forward slash)
$orthancLocal = "http://localhost:8042/";

// Orthanc server address used remotely by clients to search / view
// Can point to a 2nd Orthanc service that uses the same PostgreSQL database
// Examples:
//   $orthanc = "http://192.168.1.1:8042";
//   $orthanc = "http://192.168.1.1/Orthanc"; (reverse proxied)
//   $orthanc = "http://" . $_SERVER['SERVER_NAME'] . ":8042/";
//   $orthanc = "http://" . $_SERVER['SERVER_NAME'] . "/Orthanc/"; (reverse proxied)
// use $orthanc = "http://" . $_SERVER['SERVER_NAME'] . "<suffix>/"; for variable addresses
// (Be sure to include last forward slash)
$orthanc = "http://" . $_SERVER['SERVER_NAME'] . ":8042/";

// Get rid of potentially damaging characters
if ($_POST) {
	$charsToReplace = array("\\", "|", "<end>", ";");
	$charsToReplaceWith = array("/", "/", "", "");
	foreach ($_POST as &$postEntry) {
		$postEntry = str_replace($charsToReplace, $charsToReplaceWith, $postEntry, $count);
		$finalCount += $count;
	}
}

// PostgreSQL Config
$servername = "localhost";
$serverport = "5432";
$username = "postgres";
$password = "pgpassword";
$dbname = "isen";
$table = "isendat";

// Allow only whitelisted IPs
$allowOnlyWhitelistedIPs = false;
include "allowedIPs.php";

// Deny password change for USER level
$allowUserLevelToChangePassword = false;
?>

<!-- Website title -->
<title>mcdcm reporter</title>

<!-- Browser icon -->
<link rel="icon" type="image/png" href="img/logo2_icon.png">

<!-- Background and Colors -->
<body bgcolor=111111 background=img/lights.jpg text=ffffff link=ffff00 vlink=cccc00>


<!-- CSS Styles -->
<style>
body {
    font-family: Calibri;
}
</style>

<!-- Footer -->
<div id="footer" style="position:fixed; bottom:0; left:0; width:100%">
<table width=100% style="background-color: rgba(20, 40, 50, 0.5);" cellpadding=0 cellspacing=0><tr><td align=center>
<font size=-1>mcdcmReporter &copy 2016-2020 Kim-Ann Git</font><br>
</td></tr></table>
</div>

<?php
// PRINT OPTIONS
$printLogo = "img/img_jatanegara.jpg";
$printDepartmentName = "Department of Radiology";
$printHospitalName = "Sample Hospital";
$printHospitalAddress = "123 XYZ Road, 99999 Town";
$printHospitalContact = "123-1234567 | hospital.com";
?>