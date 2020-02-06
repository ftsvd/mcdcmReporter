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
[printStudy.php]
	Prints report
*/

// die if not logged in
if(isset($_SESSION['loggedin']) != 1){ die('<META http-equiv="REFRESH"  content="0; url=index.php">'); }

// hide all errors
error_reporting(0);
ini_set('display_errors', 0);

// include configuration file
include 'config.php';
?>

<body onload="window.print()">
<?php

$study = $_GET['study'];	

if (strpos($study,'OFFLINE') === false) { // Studies exist in Orthanc, so populate from Orthanc
	$studyJSON = file_get_contents($orthanc . "studies/" . $study . "/shared-tags?simplify");
	$studyMetadata = json_decode($studyJSON, true);
	$studyInstanceUID = $studyMetadata['StudyInstanceUID'];
	$offline = true;
} else { // Studies are OFFLINE (don't exist in Orthanc), so populate from PostgreSQL
	// Get from SQL into $study[][] array
	// Create connection
	$conn = pg_connect("host=" . $servername . " port=" . $serverport . " dbname=" . $dbname . " user=" . $username . " password=" . $password);
	$sql = "SELECT * FROM " . $table . " WHERE orthancstudyuid='" . $study . "';";
	$result = pg_query($conn, $sql);

	while($row = pg_fetch_array($result, NULL, PGSQL_NUM)) {
		if ($row[7] == $study) {
			$studyMetadata['PatientID'] = $row[0];
			$studyMetadata['PatientName'] = $row[2];
			$studyMetadata['StudyDate'] = $row[4];
			$studyMetadata['StudyInstanceUID'] = $row[8];
			$modality = $row[11];
			$offline = true;
		}
	}
	pg_close($conn);
}
	
//Load Report
//check and load existing report
if (file_exists("data/reports/" . $studyMetadata['StudyInstanceUID'] . ".txt")) {
	$existingReport = explode('<end>', file_get_contents("data/reports/" . $studyMetadata['StudyInstanceUID'] . ".txt"));
	for ($e = 0; $existingReport[$e] != ""; $e++) {
		$report = explode('|', $existingReport[$e]);
		if ($report[2] == "final") { $prevStatus = "final"; }
	}
} else {
	$report = array("","","new","");
}

if ($_GET['format'] == "bottom") { //half page
	echo "<div style=\"position: absolute; bottom: 15px; width:97%\">";
	echo $studyMetadata['PatientName'] . " (" . $studyMetadata['PatientID'] . ") on " . $studyMetadata['StudyDate'] . "<br><br>";
	echo "<table border=1 cellspacing=0 cellpadding=5 width=100%><tr><td><font size=-1>";
	echo str_replace("\n", "<br>", $report[0]);
	echo "</font></td></tr></table><br>";
	echo "<center><font size=-1>Verified by " . $report[1] . " on " . $report[3] . ". ";
	echo "This electronic document does not need signature for verification.</font></center>";
	echo "</div>";
} else { // full page
	//Report Page
	echo "<center><table width=100% border=0 cellpadding=5><td valign=top align=left width=100>";
	echo "<img src=" . $printLogo . " height=100></td>";
	echo "<td><b>" . $printDepartmentName . "</b><br>";
	echo $printHospitalName  . "<br>";
	echo $printHospitalAddress . "<br>";
	echo $printHospitalContact;
	echo "</td></table>";
	echo "<hr size=1>";
	//customize header
	if ($_GET['modality'] == "CT") { $reportHeader = "CT Report"; } else
	if ($_GET['modality'] == "MR") { $reportHeader = "MRI Report"; } else
	if ($_GET['modality'] == "US") { $reportHeader = "Ultrasound Report"; } else
	if ($_GET['modality'] == "CR") { $reportHeader = "General Radiograph Report"; } else
	if ($_GET['modality'] == "RF") { $reportHeader = "Fluoroscopy Report"; } else
	if ($_GET['modality'] == "XA") { $reportHeader = "Angiography Report"; } else
	if ($_GET['modality'] == "MG") { $reportHeader = "Mammography Report"; } else
	{ $reportHeader = "Radiology Report"; } //default
	
	echo "</center>";
	echo "<table cellspacing=10><tr><td valign=top width=50%>";
	echo "Patient Name: <b>" . $studyMetadata['PatientName'] . "</b><br>";
	echo "Patient ID: " . $studyMetadata['PatientID'] . "<br>";
	echo "Study Date: " . $studyMetadata['StudyDate'];
	echo "</td><td valign=top width=50%>";
	echo "<b>Indication</b>:<br>";
	echo "<table border=1 cellspacing=0 cellpadding=5 width=100%><tr><td> ";
	echo str_replace("\n", "<br>", $report[4]);
	echo "</td></tr></table>";
	echo "</td></tr></table>";
	echo "<b>" . $reportHeader . "</b>:<br>";
	echo "<table border=1 cellspacing=0 cellpadding=5 width=100%><tr><td>";
	echo str_replace("\n", "<br>", $report[0]);
	echo "</td></tr></table><br>";
	echo "Status: " . $report[2] . ". Updated by " . $report[1] . " on " . $report[3] . "<br>";
	echo "This electronic document does not need signature for verification.";
}

	
	
?>