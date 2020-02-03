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
[addPatient.php]
	Called by Orthanc onStableStudy to add study into the mcdcmReporter database
*/

// hide all errors
error_reporting(0);
ini_set('display_errors', 0);

// include configuration file
include 'config.php';

?>

<?php
$study = $_GET['study'];

// Create connection
$conn = pg_connect("host=" . $servername . " port=" . $serverport . " dbname=" . $dbname . " user=" . $username . " password=" . $password); 

// Get metadata from study
$studyJSON = file_get_contents($orthancLocal . "studies/" . $study . "/shared-tags?simplify");
$metadata = json_decode($studyJSON, true);

//TAIPING from V1
if (!$metadata['StudyDescription']) { 
	//check patientcomments (Taiping)
	if ($metadata['PatientComments'] != "") {
		$metadata['StudyDescription'] = $metadata['PatientComments']; 
	} else {
		$metadata['StudyDescription'] = 'No Study Description'; 
	}
}

//ORIGINAL
//if (!$metadata['StudyDescription']) { $metadata['StudyDescription'] = 'No Study Description'; }

// get rid of potentially damaging characters
$charsToReplace = array("\\", "|", "<end>", ";");
$charsToReplaceWith = array("/", "/", "", "");
foreach ($metadata as &$postEntry) {
	$postEntry = str_replace($charsToReplace, $charsToReplaceWith, $postEntry, $count);
	}

// Create new record
$fields = ["patientid", "otherpatientid", "patientname", "patientsex", "studydate", "studytime", "studydescription", "orthancstudyuid", "studyinstanceuid", "reportstatus", "reportedby", "modality", "accessionnumber", "retired_studycomments", "institutionaldepartmentname", "imagecomments"];
$fieldValues = [$metadata['PatientID'], $metadata['OtherPatientID'], $metadata['PatientName'], $metadata['PatientSex'], $metadata['StudyDate'], $metadata['StudyTime'], $metadata['StudyDescription'], $study, $metadata['StudyInstanceUID'], "new", '', $metadata['Modality'], $metadata['AccessionNumber'], $metadata['RETIRED_StudyComments'], $metadata['InstitutionalDepartmentName'], $metadata['ImageComments']];

//Build SQL Query
$sqlFields = "";
$sqlFieldValues = "";
for ($f = 0; $f < count($fields); $f++) {
    $sqlFields .= $fields[$f] . ",";
    $sqlFieldValues .= "'" . $fieldValues[$f] . "',";
}
$sqlFields = rtrim($sqlFields, ",");
$sqlFieldValues = rtrim($sqlFieldValues, ",");

$result = pg_prepare($conn, "insertQuery", "INSERT INTO " . $table . " (" . $sqlFields . ") VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16)");
$result = pg_execute($conn, "insertQuery", $fieldValues);
echo $result;
/*
$sql = "INSERT INTO " . $table . " (" . $sqlFields . ") VALUES (" . $sqlFieldValues . ");";
// echo $sql;

if (pg_query($conn, $sql)) {
    echo "New record created successfully<br>";
} else {
    echo "Error<br>"; // . $sql . "<br>"; // . mysqli_error($conn);
}
*/


/*

// Check for previous study
$fp = fopen($dataFile, 'r');
$found = false;
while (($buffer = fgets($fp)) !== false) {
	if (strpos($buffer, $study) !== false) {
		$found = true;
		break;
		}
	}

if ($found == false) {

	// Get metadata from study
	$studyJSON = file_get_contents($orthanc . "studies/" . $study . "/shared-tags?simplify");
	$metadata = json_decode($studyJSON, true);

	if (!$metadata['StudyDescription']) { $metadata['StudyDescription'] = 'No Study Description'; }

	// get rid of potentially damaging characters
	$charsToReplace = array("\\", "|", "<end>", ";");
	$charsToReplaceWith = array("/", "/", "", "");
	foreach ($_metadata as &$postEntry) {
		$postEntry = str_replace($charsToReplace, $charsToReplaceWith, $postEntry, $count);
		$finalCount += $count;
		}
		
	// Write Metadata to $dataFile
	$dataToAppend = $metadata['PatientID'] . "|";
	$dataToAppend .= $metadata['OtherPatientID'] . "|";
	$dataToAppend .= $metadata['PatientName'] . "|";
	$dataToAppend .= $metadata['PatientSex'] . "|";
	$dataToAppend .= $metadata['StudyDate'] . "|";
	$dataToAppend .= $metadata['StudyTime'] . "|";
	$dataToAppend .= $metadata['StudyDescription'] . "|";
	$dataToAppend .= $study . "|";
	$dataToAppend .= $metadata['StudyInstanceUID'] . "|new||";
	$dataToAppend .= $metadata['Modality'] . "|";
	$dataToAppend .= $metadata['AccessionNumber'] . "|";
	$dataToAppend .= $metadata['RETIRED_StudyComments'] . "|";
	$dataToAppend .= $metadata['InstitutionalDepartmentName'] . "|";
	$dataToAppend .= $metadata['ImageComments'] . "||||||||\n";
	
	if (file_put_contents($dataFile, $dataToAppend, FILE_APPEND | LOCK_EX)) {
		echo "OK: Data added";
		} else {
		echo "FAIL: File write failed";
		}
	}	
*/
?>

