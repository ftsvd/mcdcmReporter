<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
include 'config.php';

session_start();
?>

<title>mcdcm reporter</title>
<body bgcolor=111111 background=lights.jpg text=ffffff link=ffff00 vlink=cccc00>
	<br><br><br>
	
<?php
// get rid of potentially damaging characters
$charsToReplace = array("\\", "|", "<end>", ";");
$charsToReplaceWith = array("/", "/", "", "");
foreach ($_POST as &$postEntry) {
	$postEntry = str_replace($charsToReplace, $charsToReplaceWith, $postEntry, $count);
	$finalCount += $count;
	}

$gmDate = gmdate("U");
$random = mt_rand(10000,99999);
$gmDate = (string)$gmDate . $random;

/* ORIGINAL
// Write Metadata to $dataFile
$dataToAppend = $_POST['addPatientIC'] . "|"; //$metadata['PatientID'] . "|";
$dataToAppend .= "|"; //"$metadata['OtherPatientID'] . "|";
$dataToAppend .= $_POST['addPatientName'] . "|"; //$metadata['PatientName'] . "|";
$dataToAppend .= $_POST['addPatientSex'] . "|"; //$metadata['PatientSex'] . "|";
$dataToAppend .= $_POST['studyYear'] . $_POST['studyMonth'] . $_POST['studyDay'] . "|"; //$metadata['StudyDate'] . "|";
$dataToAppend .= $_POST['addStudyTime'] . "|"; //$metadata['StudyTime'] . "|";
$dataToAppend .= $_POST['addStudyDescription'] . "|"; //$metadata['StudyDescription'] . "|";
$dataToAppend .= "OFFLINE-" . $gmDate . "|"; //$study . "|";
$dataToAppend .= "OFFLINE-UID" . $gmDate . "|new||"; //$metadata['StudyInstanceUID'] . "|new||";
$dataToAppend .= $_POST['addModality'] . "|"; //$modality . "|";
$dataToAppend .= "OFFLINE-ACC" . $gmDate . "|"; //$metadata['AccessionNumber'] . "|";
$dataToAppend .= "|"; //$metadata['RETIRED_StudyComments'] . "|";
$dataToAppend .= "|"; //$metadata['InstitutionalDepartmentName'] . "|";
$dataToAppend .= "||||||||\n"; //$metadata['ImageComments'] . "||||||||\n";

if (file_put_contents($dataFile, $dataToAppend, FILE_APPEND | LOCK_EX)) {
	echo "OK: Data added";
	} else {
	echo "FAIL: File write failed";
	}
END ORIGINAL */

//VERSION 2.0
// Create connection
//$conn = new mysqli($servername, $username, $password, $dbname);
$conn = pg_connect("host=" . $servername . " port=" . $serverport . " dbname=" . $dbname . " user=" . $username . " password=" . $password); 

// Get metadata from study
$studyJSON = file_get_contents($orthanc . "studies/" . $study . "/shared-tags?simplify");
$metadata = json_decode($studyJSON, true);

if (!$metadata['StudyDescription']) { $metadata['StudyDescription'] = 'No Study Description'; }

// get rid of potentially damaging characters
$charsToReplace = array("\\", "|", "<end>", ";");
$charsToReplaceWith = array("/", "/", "", "");
foreach ($metadata as &$postEntry) {
	$postEntry = str_replace($charsToReplace, $charsToReplaceWith, $postEntry, $count);
	}

// Create new record
$fields = ["patientid", "otherpatientid", "patientname", "patientsex", "studydate", "studytime", "studydescription", "orthancstudyuid", "studyinstanceuid", "reportstatus", "reportedby", "modality", "accessionnumber", "retired_studycomments", "institutionaldepartmentname", "imagecomments"];
$fieldValues = [
	$_POST['addPatientIC'],
	"",
	$_POST['addPatientName'],
	$_POST['addPatientSex'],
	$_POST['studyYear'] . $_POST['studyMonth'] . $_POST['studyDay'],
	$_POST['addStudyTime'],
	$_POST['addStudyDescription'],
	"OFFLINE-" . $gmDate,
	"OFFLINE-UID" . $gmDate,
	"new",
	"",
	$_POST['addModality'],
	"OFFLINE-ACC" . $gmDate,
	"",
	"",
	""
];

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

/*
$sql = "INSERT INTO " . $table . " (" . $sqlFields . ") VALUES (" . $sqlFieldValues . ");";

if (pg_query($conn, $sql)) {
    echo "New record created successfully<br>";
} else {
    echo "Error<br>"; // . $sql . "<br>"; // . mysqli_error($conn);
}
*/
//END VERSION 2.0

?>
	<br><br>
	[ <a href="<?php echo $_SESSION['lastURL']; ?>">Back to Study List</a> ] 
</body>	


