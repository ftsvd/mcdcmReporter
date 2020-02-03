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
[viewStudy.php]
	Viewer for each study
*/

// die if not logged in
if(isset($_SESSION['loggedin']) != 1){ die('<META http-equiv="REFRESH"  content="0; url=index.php">'); }

// hide all errors
error_reporting(0);
ini_set('display_errors', 0);

// include configuration file
include 'config.php';
?>

<!-- Following styles are used for reports -->
<style>
pre {
	white-space: pre-wrap;       /* css-3 */
	white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
	white-space: -pre-wrap;      /* Opera 4-6 */
	white-space: -o-pre-wrap;    /* Opera 7 */
	word-wrap: break-word;       /* Internet Explorer 5.5+ */
}
</style>

<script>
// Alert if leaving page after report is edited
var globalChange = false;
window.onbeforeunload = function() {
	if (globalChange) {
		return "Changes detected.";
	}
}
</script>

<center>
<table width=100% style="background-color: rgba(20, 40, 50, 0.5);" cellpadding=0 cellspacing=0>
	<tr>
		<td align=center>
			<?php
			// Allows user to go back to search results on clicking logo
			if ($_SESSION['lastURL']) {
				$searchLink = $_SESSION['lastURL'];
			} else {
				$searchLink = 'search.php';
			}
			echo "<a href=" . $searchLink . "><img src=img/logo2.jpg border=0></a><br>\n";

			// Toggle view format between images+report and report_only
			if ($_GET['view']) {
				if ($_SESSION['view'] == "mixed") {
					$_SESSION['view'] = "report"; 
				} else {
					$_SESSION['view'] = "mixed"; 
				}
			}

			echo "<font size=-1>Logged in as <b>" . $_SESSION['login'] . "</b> | ";
			?>

			<a href=userPreference.php>Preferences</a> | 
			<a href=javascript:switchOrientation()>Switch Orientation</a> |

			<?php
			if ($_SESSION['view'] == "mixed") {
				echo "<a href=javascript:switchView()>View Report Only</a> | ";
			} else {
				echo "<a href=javascript:switchView()>View Image & Report</a> | ";
			}
			?>

			<a href=index.php?logout=true>Log Out</a></font><br>
		</td>
	</tr>
</table>


<?php
if ($_GET['orientation']) {
	if ($_SESSION['orientation'] == "portrait") {
		$_SESSION['orientation'] = "landscape"; 
		} else {
		$_SESSION['orientation'] = "portrait"; 
		}
	}

$study = $_GET['study'];

if (strpos($study,'OFFLINE') === false) { // Studies exist in Orthanc, so populate from Orthanc
	$studyJSON = file_get_contents($orthanc . "studies/" . $study . "/shared-tags?simplify");
	$studyMetadata = json_decode($studyJSON, true);

	$seriesJSON = file_get_contents($orthanc . "studies/" . $study . "/series");
	$seriesMetadata = json_Decode($seriesJSON, true);

	//check for modality info for mixed modalities
	$modality = "";
	if (!$studyMetadata['Modality']) { 
		$i = 0;
		while ($seriesMetadata[$i]['ID'] != "" && $modality == "") {
			if ($seriesMetadata[$i]['MainDicomTags']['Modality'] != "SR") {
				$modality = $seriesMetadata[$i]['MainDicomTags']['Modality'];
			}
			$i++;
		}
		if ($modality == "") { //failed to find modality
			$modality = "XX";
		}
	} else {
		$modality = $studyMetadata['Modality'];
	}
	$offline = false;
	$studyInstanceUID = $studyMetadata['StudyInstanceUID'];
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

//AUDIT TRAIL - Log IP
date_default_timezone_set("Asia/Kuala_Lumpur");
$file = 'auditTrail.csv';
$line = date("Ymd") . "," . date("Hi") . "," . $studyMetadata['PatientName'] . "," . $studyMetadata['PatientID'] . "," . $modality . "," . $studyMetadata['StudyDate'] . "," . $study . "," . $_SERVER['REMOTE_ADDR'] . "," . $_SESSION['login'] . "\n";
// Write the contents to the file, 
// using the FILE_APPEND flag to append the content to the end of the file
// and the LOCK_EX flag to prevent anyone else writing to the file at the same time
file_put_contents($file, $line, FILE_APPEND | LOCK_EX);

//SAVE - append report to file
if (isset($_POST['editReportDo'])) {
	if ($_POST['editReportDo'] == "Save") { $status = "draft"; }
	if ($_POST['editReportDo'] == "Finalize") { $status = "final"; }
	if ($_POST['editReportDo'] == "Amend") { $status = "amended"; }
	$reportFile = "data/reports/" . $studyMetadata['StudyInstanceUID'] . ".txt";
	$dataToAppend = $_POST['report'] . "|" . $_SESSION['login'] . "|" . $status . "|" . date("Ymd") . "|" . $_POST['history'] . "<end>";
	//save into file
	if(file_put_contents($reportFile, $dataToAppend, FILE_APPEND | LOCK_EX)) { echo $_POST['editReportDo'] . " successful!"; } else { echo $_POST['editReportDo'] . "failed!"; }
	//load next item in worklist
	echo "<br><a href=printStudy.php?study=" . $_GET['study'] . " target=\"_blank\">Print Full</a> | ";
	echo "<a href=printStudy.php?study=" . $_GET['study'] . "&format=bottom target=\"_blank\">Print Half</a> | ";
	for ($s = 1; $_SESSION['worklist'][$s] != ""; $s++) {
		if ($_SESSION['worklist'][$s] == $_GET['study']) {
			if ($_SESSION['worklist'][$s+1] != "") {
				echo "<a href=viewstudy.php?study=" . $_SESSION['worklist'][$s+1] . ">Load Next Study?</a>";
			} else {
				echo "<a href=" . $searchLink . ">Back to Search</a>";	
			}
		}
	}

	// Create connection
	$conn = pg_connect("host=" . $servername . " port=" . $serverport . " dbname=" . $dbname . " user=" . $username . " password=" . $password);

	$sql = "UPDATE " . $table . " SET reportstatus='" . $status . "' WHERE orthancstudyuid='" . $_GET['study'] . "';";
	$sql .= "UPDATE " . $table . " SET reportedby='" . $_SESSION['login'] . "' WHERE orthancstudyuid='" . $_GET['study'] . "';";
	
	$result = pg_query($conn, $sql);
	pg_close($conn);
}
	
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

//alert if reported by somebody else before
if ($report[2] == "final" || $report[2] == "amended") { $alertColor = "ff0000"; } else { $alertColor = "ffff00"; }
if ($report[2] != "new" && $report[1] !=  $_SESSION['login']) { echo "<font color=" . $alertColor . "><b>previous " . $report[2] . " report by " . $report[1] . "</b></font>"; }	

//read templates. If no user template, load the default templates
if (file_exists("data/templates/" . $_SESSION['login'] . ".txt")) { 
	$templates = explode("<end>", file_get_contents("data/templates/" . $_SESSION['login'] . ".txt")); 
} else { 
	$templates = explode("<end>", file_get_contents("data/templates/default.txt")); 
}

//get orientation
if ($_SESSION['orientation'] == "portrait") { $orientation ="portrait"; } else { $orientation = "landscape"; }

//generate viewing code
$htmlViewing = "Series: <select onChange=\"changeSeries()\" id=changeSeries name=changeSeries>\n";
for ($se=0; $se < count($seriesMetadata); $se++) {
	if (!$seriesMetadata[$se]['MainDicomTags']['SeriesDescription']) { $seriesMetadata[$se]['MainDicomTags']['SeriesDescription'] = "No Series Description"; }
	$htmlViewing .= "<option value=\"" . $orthanc . "web-viewer/app/viewer.html?series=" . $seriesMetadata[$se]['ID'] . "\">" . $seriesMetadata[$se]['MainDicomTags']['SeriesDescription'] . "</option>\n";
}
$htmlViewing .= "</select>\n";
$htmlViewing .= "<input type=button value=\"Open Current Study in Osimis\" onClick=\"window.open('" . $orthanc . "osimis-viewer/app/index.html?study=" . $study . "', '_blank')\">\n";
$htmlViewing .= "<br>";

//don't auto-load webviewer if submitted (i.e in between reports)
if (isset($_POST['editReportDo'])) {
	if ($_SESSION['defaultviewer'] == "osimis") {
		$htmlViewing .= "<div id=\"divViewerIframe\" style=\"text-align:center; position:relative; height:90%\"><iframe width=100% height=90% id=\"viewerIframe\" name=\"viewerIframe\" srcdoc=\"<a href=" . $orthanc . "osimis-viewer/app/index.html?study=" . $study . "><font color=ffff00>[ Load series ]</a>\"></iframe>";
		$htmlViewing .= "<input type=button value=\"Maximize\" id=\"buttonMaximize\" onClick=\"maximizeDivViewerIframe()\"></div>";
	} else {
		$htmlViewing .= "<div id=\"divViewerIframe\" style=\"text-align:center; position:relative; height:90%\"><iframe width=100% height=90% id=\"viewerIframe\" name=\"viewerIframe\" srcdoc=\"<a href=" . $orthanc . "web-viewer/app/viewer.html?series=" . $seriesMetadata[0]['ID'] . "><font color=ffff00>[ Load series ]</a>\"></iframe>";
		$htmlViewing .= "<input type=button value=\"Maximize\" id=\"buttonMaximize\" onClick=\"maximizeDivViewerIframe()\"></div>";
	}
} else {
	if ($_SESSION['defaultviewer'] == "osimis") {
		$htmlViewing .= "<div id=\"divViewerIframe\" style=\"text-align:center; position:relative; height:90%\"><iframe width=100% height=90% id=\"viewerIframe\" name=\"viewerIframe\" src=" . $orthanc . "osimis-viewer/app/index.html?study=" . $study . "></iframe>";
		$htmlViewing .= "<input type=button value=\"Maximize\" id=\"buttonMaximize\" onClick=\"maximizeDivViewerIframe()\"></div>";
	} else {
		$htmlViewing .= "<div id=\"divViewerIframe\" style=\"text-align:center; position:relative; height:90%\"><iframe width=100% height=90% id=\"viewerIframe\" name=\"viewerIframe\" src=" . $orthanc . "web-viewer/app/viewer.html?series=" . $seriesMetadata[0]['ID'] . "></iframe>";
		$htmlViewing .= "<input type=button value=\"Maximize\" id=\"buttonMaximize\" onClick=\"maximizeDivViewerIframe()\"></div>";
	}
}
	
//generate reporting code
$htmlReportingHeader = "<form id=editReport method=\"POST\" action=\"viewStudy.php?study=" . $_GET['study'] . "\">";
$htmlReportingHeader .= "<input type=hidden name=editReportDo id=editReportDo>";

$htmlReportingPatient = "<b>Patient Details</b><hr size=1>Patient Name: <font color=cbaf16>" . $studyMetadata['PatientName'] . "</font><br>";
$htmlReportingPatient .= "Patient ID: <font color=cbaf16>" . $studyMetadata['PatientID'] . " " . $studyMetadata['OtherPatientIDs'] . "</font><br>";
$htmlReportingPatient .= "Study Date: <font color=cbaf16>" . $studyMetadata['StudyDate'] . "</font><br><br>";

$htmlReportingClinical = "<b>Clinical Information</b><hr size=1>";

//users are only allowed to view reports. radiologist+admin allowed to edit reports.
if ($_SESSION['level'] == "user") {
	$htmlReportingClinical .= "<iframe width=100% height=60 srcdoc=\"<font color=ffffff><pre style='white-space:pre-wrap;'>" . $report[4] . "</pre></font>\"></iframe><br><br>";
} else {
	$htmlReportingClinical .= "<textarea id=\"history\" name=\"history\" cols=80 rows=4>" . $report[4] . "</textarea><br><br>";
}

$htmlReportingReport = "<table width=100% cellspacing=0 callpadding=0 border=0><tr><td align=left valign=bottom><b>Report:</b></td><td align=right>";

if ($_SESSION['level'] != "user") {
	$htmlReportingReport .= "Templates <select id=\"templates\" onChange=\"loadTemplate()\">";
	$htmlReportingReport .= "<option></option>\n";
	for ($t = 0; $templates[$t] != ""; $t++) {
		$template = explode("|", $templates[$t]);
		$htmlReportingReport .= "<option value=\"" . $template[1] . "\">" . $template[0] . "</option>\n";
	}
	$htmlReportingReport .= "</select>";
}
$htmlReportingReport .= "</td></tr></table><hr size=1>";

if ($_SESSION['level'] == "user") {
	if ($report[2] == "new") 	{
		$htmlReportingReport .= "<iframe width=100% height=270 srcdoc=\"<font color=ffffff><pre style='white-space:pre-wrap;'>No Report Available</pre></font>\"></iframe><br><br>";
		$htmlReportingReport .= "<table width=100%><tr><td><!--Keywords:--></td><td align=right>";
	}
	if ($report[2] == "draft" || $report[2] == "final" || $report[2] == "amended") {
		$htmlReportingReport .= "<iframe id=\"userReport\" class=\"userReport\" width=100% height=270 srcdoc=\"<font color=ffffff><pre style='white-space:pre-wrap;'>" . $report[0] . "</pre></font>\"></iframe><br><br>";
		$htmlReportingReport .= "<table width=100%><tr><td><!--Keywords: " . $keyword[$studyInstanceUID] . "--></td><td align=right>";
	}
} else {
	$htmlReportingReport .= "<textarea id=\"report\" name=\"report\" cols=80 rows=13>" . $report[0] . "</textarea><br><br>";
	$htmlReportingReport .= "<table width=100%><tr><td><!--Keywords: <input type=text id=\"keywords\" name=\"keywords\" size=25 value=\"" . $keyword[$studyInstanceUID] . "\">--></td><td align=right>";
}
	
$htmlReportingReport .= "Current status: <font color=ffff00>" . $report[2] . " [". $report[1] . "] </font>";

if ($_SESSION['level'] != "user") {
	if ($report[2] == "new" || $report[2] == "draft") { $htmlReportingReport .= "<input type=button value=\"Save\" onClick=\"confirmFlag('Save')\"> <input type=button value=\"Finalize\" onClick=\"confirmFlag('Finalize')\">"; }
	if ($report[2] == "final" || $report[2] == "amended") { $htmlReportingReport .= "<input type=button value=\"Amend\" onClick=\"confirmFlag('Amend')\">"; }
}
	
$htmlReportingReport .= "</td></tr></table>";

$htmlReportingFooter = "</form>";

if ($orientation == "portrait") { $htmlReporting .= "</td></tr></table>\n"; }

//draw table based on orientation & modality
echo "<table width=100% height=90% border=0 cellpadding=10>";
if ($offline == true || $_SESSION['view'] == "report") {
	echo "<tr>";
	echo "<td></td>";
	echo "<td width=550 valign=top>" . $htmlReportingHeader . $htmlReportingPatient . $htmlReportingClinical . $htmlReportingReport . $htmlReportingFooter . "</td>";
	echo "<td></td>";
	echo "</tr>";
} else {
	if ($orientation == "landscape") {
		echo "<tr>";
		echo "<td valign=top>" . $htmlViewing . "</td>\n";
		echo "<td width=550 valign=top>" . $htmlReportingHeader . $htmlReportingPatient . $htmlReportingClinical . $htmlReportingReport . $htmlReportingFooter . "</td>";
		echo "</tr>";
	} else { //portrait
		echo "<tr height=80%><td>" . $htmlViewing . "</td></tr>";
		echo "<tr height=20%><td>";
		echo "<table width=100%><tr height=30%><td width=30% valign=top>\n";
		echo $htmlReportingHeader . $htmlReportingPatient;
		echo "</td><td width=70% valign=top rowspan=2>\n";
		echo $htmlReportingReport;
		echo "</td></tr><tr height=70%><td width=30% valign=top>\n";
		echo $htmlReportingClinical;
		echo "</td></tr></table>\n";
	}
}
echo "</table>\n";	

?>

</center>
</body>

<div id="worklistHeader" style="position:fixed; z-index:102; top:5; right:0; width:300" onMouseOver="javascript:document.getElementById('worklist').style.display = 'block';">
	<center>
	<b>Current Worklist</b>
	</center>
</div>

<div id="worklist" style="position:fixed; z-index:101; top:25; right:0; width:300; height:200; display:none" onMouseOver="javascript:document.getElementById('worklist').style.display = 'block';" onMouseOut="javascript:document.getElementById('worklist').style.display = 'none';">
	<table width=100% style="background-color: rgba(20, 40, 50, 0.8);" cellpadding=0 cellspacing=0>
		<tr>
			<td align=center>
				<font size=-1>
				<?php
				for ($w = 1; $_SESSION['worklist'][$w] != ""; $w++) {
					if ($_SESSION['worklist'][$w] == $_GET['study']) {
						echo "&gt " . $_SESSION['worklistPatientName'][$w] . " &lt<br>";
					} else {
						echo "<a href=viewStudy.php?study=" . $_SESSION['worklist'][$w] . ">" . $_SESSION['worklistPatientName'][$w] . "</a><br>";
					}
				}
				?>
				</font>
			</td>
		</tr>
	</table>
</div>

<script>
/*
var keywords = ['mass', 'nodule', 'ptb', 'tuberculosis', 'pneumonia', 'infection', 'variant', 'tb', 'cardiomegaly'];
var reportText = "";
	
function generateKeywords() {
	globalChange = true;
	document.getElementById('keywords').value = "";
	reportText = document.getElementById('report').value.toLowerCase();
	for (var k = 0; k < keywords.length; k++) {
		var indices = multiIndex(keywords[k]);
		for (var i = 0; i < indices.length; i++) {
			
			var freshLine = reportText.substring(reportText.lastIndexOf('\n',indices[i]), indices[i]);
			freshLine = freshLine.toLowerCase();
			if (freshLine.indexOf('no ') === -1 && freshLine.indexOf('not ') === -1) {
				document.getElementById('keywords').value += keywords[k] + ",";
				break;
			}
		}
	}
}

function multiIndex(keyword) {
	var ip = 0;
	var cp = 0;
	var indices = [];
	while (reportText.indexOf(keyword,cp) !== -1) {
		indices[ip] = reportText.indexOf(keyword,cp);
		ip++;
		cp = reportText.indexOf(keyword,cp) + 1;
	}
	return indices;
}
*/

/*	
function putFilter() {
	document.getElementById('report').value += "\n\nReported by\n";
	document.getElementById('report').value += "<?php echo $_SESSION['fullname']; ?>";
}
*/

function loadTemplate() {
	var selected = document.getElementById('templates').options.selectedIndex;
	if (document.getElementById('report').value == "") { 
		document.getElementById('report').value = document.getElementById('templates').options[selected].value;
	} else {
		if (confirm('Load Template?')) { document.getElementById('report').value = document.getElementById('templates').options[selected].value; }
	}
}
	
function confirmFlag(action) {
	document.getElementById('editReportDo').value = action;
	globalChange = false;
	document.getElementById('editReport').submit();
}
	
/*
function doConfirmFlag() {
	globalChange = false;
	document.getElementById('keywords').value = document.getElementById('confirmFlagTextBox').value;
	if (document.getElementById('confirmFlagCheckBox').checked) {
		if (document.getElementById('keywords').value.charAt(document.getElementById('keywords').value.length-1) != ",") {
			document.getElementById('keywords').value += ",";
		}
		document.getElementById('keywords').value += "flag,";
	}
	document.getElementById('editReport').submit();
}
*/

function changeSeries() {
	document.getElementById('viewerIframe').src = document.getElementById('changeSeries').value;
}

function switchOrientation() {
	location.href += '&orientation=switch';
}
	
function switchView() {
	var currentLocation = location.href.split("&");
	location.href = currentLocation[0] + '&view=switch';
}

var maximized = false;
function maximizeDivViewerIframe() {
	if (maximized == false) {
		document.getElementById('divViewerIframe').style.position = 'fixed';
		document.getElementById('divViewerIframe').style.width = window.innerWidth - 10;
		document.getElementById('divViewerIframe').style.height = window.innerHeight - 50;
		document.getElementById('divViewerIframe').style.top= '50px';
		document.getElementById('divViewerIframe').style.left= '5px';
		document.getElementById('buttonMaximize').value = 'Restore';
		maximized = true;
	} else {
		document.getElementById('divViewerIframe').style.position = 'relative';
		document.getElementById('divViewerIframe').style.width = '';
		document.getElementById('divViewerIframe').style.height = '90%';
		document.getElementById('divViewerIframe').style.top= '';
		document.getElementById('divViewerIframe').style.left= '';
		document.getElementById('buttonMaximize').value = 'Maximize';
		maximized = false;
	}
}

//Stop selection of report for user
var userReportIFrame = document.getElementById('userReport').contentWindow;

userReportIFrame.addEventListener('select', function() {
	this.selectionStart = this.selectionEnd;
}, false);

userReportIFrame.addEventListener('mousedown', userReportClick);

function userReportClick(e) {
	e.preventDefault();
}

</script>

<style>
.userReport {
	-webkit-user-select: none; /* Safari 3.1+ */
	-moz-user-select: none; /* Firefox 2+ */
	-ms-user-select: none; /* IE 10+ */
	user-select: none; /* Standard syntax */
}
</style>