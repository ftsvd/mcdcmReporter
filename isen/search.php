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
[search.php]
	Main search page of mcdcmReporter
*/

// die if not logged in
if(isset($_SESSION['loggedin']) != 1){ die('<META http-equiv="REFRESH"  content="0; url=index.php">'); }

// hide all errors
error_reporting(0);
ini_set('display_errors', 0);

// include configuration file
include 'config.php';
?>

<?php
// increase memory limit due to SELECT * FROM (legacy problem from when database was a CSV instead of an SQL)
// TO DO as of 20200203
ini_set('memory_limit','1024M');

// catch PostgreSQL Errors
// https://stackoverflow.com/questions/4253136/how-to-catch-pg-connect-function-error
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

try {
	$conn = pg_connect("host=" . $servername . " port=" . $serverport . " dbname=" . $dbname . " user=" . $username . " password=" . $password);
	pg_close($conn);
} catch (Exception $e) {
	echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><center>";
	echo "mcdcmReporter has problems connecting to the PostgreSQL database:<br>";
	echo "<pre>" . $e->getMessage() . "</pre>";
	echo "If database does not exist, have you run <a href=setup.php>setup.php</a> yet?";
	echo "</center>";
}
restore_error_handler();
?>

<script>
/* search.php receives parameters through GET */

// Appends sortby flag to current URL
function applySort(sortby, sortdir) {
	var URL = location.href;
	if (URL.indexOf('sortby') !== -1) { URL = URL.substr(0, URL.indexOf('sortby')); } //sort will always be at the end, after filter
	if (URL.indexOf('?') === -1) { URL += "?"; }
	if (URL.substr(-1,1) != "&") { URL += "&"; }
	URL += "sortby=" + sortby + "&sortdir=" + sortdir;
	location.href = URL;
}

// Appends filter flags to base search.php URL	
// Example format: search.php?filter[]=modality,CR,DX,&filter[]=studydate,20200122&
function applyFilter()	{
	var URL = "search.php?";
	//fill in patient filter
	if (document.getElementById('patientname').value) {
		URL += "filter[]=patientname," + document.getElementById('patientname').value + "&";
		}
	//fill in status filter
	var statusNew = document.getElementById('statusNew');
	var statusDraft = document.getElementById('statusDraft');
	var statusFinal = document.getElementById('statusFinal');
	var statusAmended = document.getElementById('statusAmended');
	if (statusNew.checked || statusDraft.checked || statusFinal.checked || statusAmended.checked ) {
		URL += "filter[]=status,";
		if (statusNew.checked) { URL += "new,"; }
		if (statusDraft.checked) { URL += "draft,"; }
		if (statusFinal.checked) { URL += "final,"; }
		if (statusAmended.checked) { URL += "amended,"; }
		URL += "&";
	}
	//fill in modality filter
	var modalityCR = document.getElementById('modalityCR');
	var modalityDX = document.getElementById('modalityDX');
	var modalityCT = document.getElementById('modalityCT');
	var modalityMR = document.getElementById('modalityMR');
	var modalityUS = document.getElementById('modalityUS');
	var modalityMG = document.getElementById('modalityMG');
	var modalityRF = document.getElementById('modalityRF');
	var modalityXA = document.getElementById('modalityXA');	
	if (modalityCR.checked || modalityDX.checked || modalityCT.checked || modalityMR.checked || modalityUS.checked || modalityMG.checked) {
		URL += "filter[]=modality,";
		if (modalityCR.checked) { URL += "CR,"; }
		if (modalityDX.checked) { URL += "DX,"; }
		if (modalityCT.checked) { URL += "CT,"; }
		if (modalityMR.checked) { URL += "MR,"; }
		if (modalityUS.checked) { URL += "US,"; }
		if (modalityMG.checked) { URL += "MG,"; }
		if (modalityRF.checked) { URL += "RF,"; }
		if (modalityXA.checked) { URL += "XA,"; }		
		URL += "&";
	}
	//fill in studydate filter
	if (document.getElementById('StudyDate').value != "") {
		URL += "filter[]=studydate," + document.getElementById('StudyDate').value + "&";
	}
	location.href = URL; 
}

function confirmDelete(studyUID) {
	if (confirm('Are you sure you want to delete this study?')) {
		location.href = "search.php?action=delete&study=" + studyUID;
	}
}

// Creates preset dates according to DICOM standard format of YYYYMMDD
function changeStudyDate() {
	//today
	var d = new Date();
	var mm = "" + (d.getMonth() + 1);
	if (mm.length == 1) { mm = "" + "0" + mm; }
	var dd = "" + d.getDate();
	if (dd.length == 1) { dd = "" + "0" + dd; }
	var today = "" + d.getFullYear() + mm + dd;

	//yesterday
	var d1 = new Date();
	d1.setDate(d.getDate() - 1);
	var mm1 = "" + (d1.getMonth() + 1);
	if (mm1.length == 1) { mm1 = "" + "0" + mm1; }
	var dd1 = "" + d1.getDate();
	if (dd1.length == 1) { dd1 = "" + "0" + dd1; }
	var yesterday = "" + d1.getFullYear() + mm1 + dd1;

	//last week
	var d7 = new Date();
	d7.setDate(d.getDate() - 7);
	var mm7 = "" + (d7.getMonth() + 1);
	if (mm7.length == 1) { mm7 = "" + "0" + mm7; }
	var dd7 = "" + d7.getDate();
	if (dd7.length == 1) { dd7 = "" + "0" + dd7; }
	var lastweek = "" + d7.getFullYear() + mm7 + dd7;

	//last month
	var d30 = new Date();
	d30.setDate(d.getDate() - 30);
	var mm30 = "" + (d30.getMonth() + 1);
	if (mm30.length == 1) { mm30 = "" + "0" + mm30; }
	var dd30 = "" + d30.getDate();
	if (dd30.length == 1) { dd30 = "" + "0" + dd30; }
	var lastmonth = "" + d30.getFullYear() + mm30 + dd30;

	var studyDateOption = document.getElementById("StudyDateOptions").value;

	if (studyDateOption == "specific") {
		document.getElementById("StudyDate").disabled = false;
	} else {
		document.getElementById("StudyDate").disabled = true;
	}
	if (studyDateOption == "all") {
		document.getElementById("StudyDate").value = "";
	}
	if (studyDateOption == "today") {
		document.getElementById("StudyDate").value = today;
	}
	if (studyDateOption == "yesterday") {
		document.getElementById("StudyDate").value = yesterday;
	}
	if (studyDateOption == "last2days") {
		document.getElementById("StudyDate").value = yesterday + "-" + today;
	}
	if (studyDateOption == "last7days") {
		document.getElementById("StudyDate").value = lastweek + "-" + today;
	}
	if (studyDateOption == "last30days") {
		document.getElementById("StudyDate").value = lastmonth + "-" + today;
	}
}

// Select and deselect CR and DX at the same time
function singleCRDX(which) {
	if (which == 'CR') {
		document.getElementById('modalityDX').checked = document.getElementById('modalityCR').checked;
	} else {
		document.getElementById('modalityCR').checked = document.getElementById('modalityDX').checked;		
	}
}
</script>

<body>
<center>

<!-- Add Offline Patient allows Reports to be written without Images -->
<div id="divAddOfflinePatient" style="display:none; position:fixed; top:200; left:0; width:100%; z-index:1000; padding: 10px;">
	<center>
	<table border=1 width=400 bgcolor=000000 cellpadding=10>
		<tr>
			<td>
				<b>Add Offline Patient:</b><br><br>
				<form name="formAddOfflinePatient" id="formAddOfflinePatient" method="POST" action="addOfflinePatient.php">
				Patient Name: <input type="text" name="addPatientName" id="addPatientName" size=30><br><br>
				Patient IC: <input type="text" name="addPatientIC" id="addPatientIC" size=15> (123456121234)<br><br>
				Patient Sex: <select name="addPatientSex"><option value="O">O</option>	<option value="M">M</option><option value="F">F</option></select><br><br>
				Modality: <select name="addModality">
					<option value="CR">CR</option>
					<option value="DX">DX</option>
					<option value="CT">CT</option>
					<option value="MR">MR</option>
					<option value="US">US</option>
					<option value="MG">MG</option>
					<option value="RF">RF</option>
					<option value="XA">XA</option>
				</select> 
				Description: <input type="text" name="addStudyDescription" id="addStudyDescription" size=20><br><br>
				Study Date: 
				<select name="studyDay" id="studyDay">
				<?php
				for ($d = 1; $d <= 31; $d++) {
					if (strlen($d) == 1) { $d = "0" . (string)$d; }
					echo "<option value=\"" . $d . "\">" . $d . "</option>\n";
				}
				?>
				</select>

				<select name="studyMonth" id="studyMonth">
				<?php
				for ($d = 1; $d <= 12; $d++) {
					if (strlen($d) == 1) { $d = "0" . (string)$d; }
					echo "<option value=\"" . $d . "\">" . $d . "</option>\n";
				}
				?>
				</select>

				<select name="studyYear" id="studyYear">
				<?php
				for ($d = 2016; $d <= date("Y"); $d++) {
					if (strlen($d) == 1) { $d = "0" . (string)$d; }
					echo "<option value=\"" . $d . "\">" . $d . "</option>\n";
				}
				?>
				</select> <input type="button" value="Set Today" onClick="addOfflinePatientChangeDate()"><br><br>

				<input type="hidden" name="addStudyTime" value="1200">
				<center>
				<input type="button" value="Confirm" onClick="doAddOfflinePatient()">
				<input type="button" value="Cancel" onClick="document.getElementById('divAddOfflinePatient').style.display='none'"><br><br>
				</center>
				</form>
			</td>
		</tr>
	</table>
	</center>
</div>

<script>
function doAddOfflinePatient() {
	if (document.getElementById('addPatientName').value == "") { alert('Patient Name cannot be Blank'); return; }
	if (document.getElementById('addPatientIC').value == "") { alert('Patient IC cannot be Blank'); return; }
	if (document.getElementById('addStudyDescription').value == "") { alert('Description cannot be Blank'); return; }
	document.getElementById('formAddOfflinePatient').submit();
}

function addOfflinePatientChangeDate() {
	//today
	var d = new Date();
	var mm = "" + (d.getMonth() + 1);
	if (mm.length == 1) { mm = "" + "0" + mm; }
	var dd = "" + d.getDate();
	if (dd.length == 1) { dd = "" + "0" + dd; }
	document.getElementById('studyDay').value = dd;
	document.getElementById('studyMonth').value = mm;
	document.getElementById('studyYear').value = d.getFullYear();
}
</script>

<!-- Header -->
<div id="header" style="position:fixed; top:10; left:0; z-index:200; width:100%">
	<table width=100% style="background-color: rgba(20, 40, 50, 0.5);" cellpadding=0 cellspacing=0><tr><td align=center>
	<a href=search.php><img src=img/logo2.jpg border=0></a><br>
	<?php
	$_SESSION['lastURL'] = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	echo "<font size=-1>Logged in as <b>" . $_SESSION['login'] . "</b>";
	if ($_SESSION['level'] == "admin") { echo " | <a href=adminPortal.php>Administration Portal</a>"; }
	echo " | <a href=userPreference.php>Preferences</a> | <a href=index.php?logout=true>Log Out</a></font><br>";
	?>
	</td></tr></table>
</div>

<?php
//do delete action
if ($_GET['action'] == "delete" && $_SESSION['level'] == "admin" && $_GET['study'] != "") {

	// Create connection
	$conn = pg_connect("host=" . $servername . " port=" . $serverport . " dbname=" . $dbname . " user=" . $username . " password=" . $password);

	// Build SQL Query
	$sql = "DELETE FROM " . $table . " WHERE orthancstudyuid='" . $_GET['study'] . "';";
	$result = pg_query($conn, $sql);
	echo $result . "<br>";
	
	pg_close($conn);

	//Delete study from Orthanc
	$curl = curl_init();
	curl_setopt ($curl, CURLOPT_URL, $orthanc . 'studies/' . $_GET['study']);
	curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
	$resp = curl_exec($curl);
	curl_close($curl);
	if($resp) { echo "Delete Successful"; };
	
}

// filter
for ($f = 0; $_GET['filter'][$f] != ""; $f++) {
	$filterPair = explode(",", $_GET['filter'][$f]);
	// filter by patient name
	if ($filterPair[0] == "patientname") {
		$fillFilterPatientName = $filterPair[1];
	}
	// filter by date
	if ($filterPair[0] == "studydate") {
		$fillFilterStudyDate = $filterPair[1];
		$fillFilterCustom = "selected";
	}
	// filter by status
	if ($filterPair[0] == "status") {
		if (in_array('new',$filterPair)) { $fillFilterStatusNew = "checked"; } else { $fillFilterStatusNew = ""; }
		if (in_array('draft',$filterPair)) { $fillFilterStatusDraft = "checked"; } else { $fillFilterStatusDraft = ""; }
		if (in_array('final',$filterPair)) { $fillFilterStatusFinal = "checked"; } else { $fillFilterStatusFinal = ""; }
		if (in_array('amended',$filterPair)) { $fillFilterStatusAmended = "checked"; } else { $fillFilterStatusAmended = ""; } 
	}
	// filter by modality
	if ($filterPair[0] == "modality") {
		if (in_array('CR',$filterPair)) { $fillFilterModalityCR = "checked"; } else { $fillFilterModalityCR = ""; }
		if (in_array('DX',$filterPair)) { $fillFilterModalityDX = "checked"; } else { $fillFilterModalityDX = ""; }
		if (in_array('CT',$filterPair)) { $fillFilterModalityCT = "checked"; } else { $fillFilterModalityCT = ""; }
		if (in_array('MR',$filterPair)) { $fillFilterModalityMR = "checked"; } else { $fillFilterModalityMR = ""; }
		if (in_array('US',$filterPair)) { $fillFilterModalityUS = "checked"; } else { $fillFilterModalityUS = ""; }
		if (in_array('MG',$filterPair)) { $fillFilterModalityMG = "checked"; } else { $fillFilterModalityMG = ""; }
		if (in_array('RF',$filterPair)) { $fillFilterModalityRF = "checked"; } else { $fillFilterModalityRF = ""; }
		if (in_array('XA',$filterPair)) { $fillFilterModalityXA = "checked"; } else { $fillFilterModalityXA = ""; }
	}
}
?>

<!-- Search Box -->
<div id="search" style="position:absolute; top:50; left:0; z-index:150; width:100%">
	<form>
	<table cellpadding=5 width=700 border=0 bgcolor=000000>
		<tr>
			<td colspan=4 align=center bgcolor=204050>Search</td>
			<?php
			// Disable Offline Button for Users
			if ($_SESSION['level'] != 'user') { 
				echo "<td colspan=1 align=center bgcolor=204050>Offline</td>";
			}
			?>	
		</tr>
		<tr>
			<td valign=top>
			Status:
				<table><tr>
					<td>
						<input type="checkbox" name="status" id="statusNew" value="new" <?php echo $fillFilterStatusNew; ?>> New<br>
						<input type="checkbox" name="status" id="statusDraft" value="draft" <?php echo $fillFilterStatusDraft; ?>> Draft<br>
						<input type="checkbox" name="status" id="statusFinal" value="final" <?php echo $fillFilterStatusFinal; ?>> Final<br>
						<input type="checkbox" name="status" id="statusAmended" value="amended" <?php echo $fillFilterStatusAmended; ?>> Amended<br><br>
					</td>
				</tr></table>
					
			</td><td valign=top>
			Modality:
				<table><tr>
					<td>
						<input type="checkbox" name="modality" id="modalityCR" value="CR" <?php echo $fillFilterModalityCR; ?> onClick="singleCRDX('CR')"> CR<br>
						<input type="checkbox" name="modality" id="modalityDX" value="DX" <?php echo $fillFilterModalityDX; ?> onClick="singleCRDX('DX')"> DX<br>
						<input type="checkbox" name="modality" id="modalityCT" value="CT" <?php echo $fillFilterModalityCT; ?>> CT<br>
						<input type="checkbox" name="modality" id="modalityMR" value="MR" <?php echo $fillFilterModalityMR; ?>> MR<br>
					</td>
					<td>
						<input type="checkbox" name="modality" id="modalityUS" value="US" <?php echo $fillFilterModalityUS; ?>> US<br>
						<input type="checkbox" name="modality" id="modalityMG" value="MG" <?php echo $fillFilterModalityMG; ?>> MG<br>
						<input type="checkbox" name="modality" id="modalityRF" value="RF" <?php echo $fillFilterModalityRF; ?>> RF<br>
						<input type="checkbox" name="modality" id="modalityXA" value="XA" <?php echo $fillFilterModalityXA; ?>> XA<br>
					</td>
				</tr></table>
			</td><td valign=top>
			StudyDate:<br>
			<select ID="StudyDateOptions" onChange="changeStudyDate()">
			<option value="all" selected>All</option>
			<option value="today">Today</option>
			<option value="yesterday">Yesterday</option>
			<option value="last2days">Last 2 Days</option>
			<option value="last7days">Last 7 Days</option>
			<option value="last30days">Last 30 Days</option>
			<option value="specific" <?php echo $fillFilterCustom; ?>>Specific Date</option>
			</select><input type=text size=16 ID="StudyDate" value="<?php echo $fillFilterStudyDate; ?>"><br><br>
			Patient Name / ID / IC:<br>
			<input type="text" name="patientname" id="patientname" size=30 value="<?php echo $fillFilterPatientName; ?>">
			</td><td valign=center align=center>
			<img width=70 height=70 onClick="applyFilter()" alt="Search">
			</td>
			<?php
			// Show Add Offline Patient for Admin & Radiologist
			if ($_SESSION['level'] != 'user') { 
				echo "<td valign=center align=center>";
				echo "<img width=70 height=70 onClick=\"document.getElementById('divAddOfflinePatient').style.display = 'block';\" alt=\"Add Offline Report\">";
				echo "</td>";
			}
			?>
		</tr>
	</table>
	</form>
</div>

<div id="results" style="position:absolute; top:250; left:0; z-index:100; width:100%">
	<?php

	//die if no filter
	if (!$_GET['filter']) { die('Please Enter Search Parameters'); }

	// Get from SQL into $study[][] array
	// Create connection
	$conn = pg_connect("host=" . $servername . " port=" . $serverport . " dbname=" . $dbname . " user=" . $username . " password=" . $password);

	// TO DO: THIS LINE NEEDS TO BE FIXED - 20200203
	$sql = "SELECT * FROM " . $table;
	$result = pg_query($conn, $sql);

	$study = array();
	$i = 0;
	// output data of each row
	while($row = pg_fetch_array($result, NULL, PGSQL_NUM)) {
		$study[$i] = $row;
		$i++;
	}
	$totalStudies = $i;

	pg_close($conn);
		
	// Output the $study list
	if ($_GET['sortby']) {
		if ($_GET['sortby'] == "patientname") { $sortby = 2; }
		if ($_GET['sortby'] == "studydate") { $sortby = 4; }
		if ($_GET['sortby'] == "studydescription") { $sortby = 5; }
		if ($_GET['sortby'] == "status") { $sortby = 9; }

		foreach ($study as $key => $row) {
			$sortArray[$key] = strtolower($row[$sortby]); //default is Caps before non-caps... Obviously PHP doesn't like ironman.
			$timeArray[$key] = $row[5];
		}
		if ($_GET['sortdir'] == "dsc") { 
			array_multisort($sortArray, SORT_DESC, $timeArray, SORT_DESC, $study); 
		} else { 
			array_multisort($sortArray, SORT_ASC, $timeArray, SORT_ASC, $study);
		}
	}
	?>

	<table border=1 cellspacing=0 cellpadding=4>
		<tr bgcolor=204050>
			<td></td>
			<td>MRN</td>
			<td>PatientName <a href="javascript:applySort('patientname','asc')">&#x2227</a> <a href="javascript:applySort('patientname','dsc')">&#x2228</a> </td>
			<td>Sex</td>
			<td>Date <a href="javascript:applySort('studydate','asc')">&#x2227</a> <a href="javascript:applySort('studydate','dsc')">&#x2228</a></td>
			<td>Time</td>
			<td>Mod</td>
			<td>Procedure</td>
			<td>Status</td>
			<td>Reported by</td>
			<?php if ($_SESSION['level'] == "admin") { echo "<td>Action</td>"; } ?>
		</tr>
		<?php
		// Worklist Session Variable allows quick switching between studies found in search.php
		$_SESSION['worklist'] = []; // blanks worklist
		$j = 1; //count cases

		for ($i = 0; $i < $totalStudies; $i++) {
			//apply filter
			$filtered = "pass";
			for ($f = 0; $_GET['filter'][$f] != ""; $f++) {
				if ($filtered == "block") { break; }
				$filterPair = explode(",", $_GET['filter'][$f]);
				// filter by name
				if ($filterPair[0] == "patientname") {
					if (strpos(strtolower($study[$i][0]), strtolower($filterPair[1])) === false && strpos(strtolower($study[$i][1]), strtolower($filterPair[1])) === false && strpos(strtolower($study[$i][2]), strtolower($filterPair[1])) === false) {
						$filtered = "block";
					}
				}
				// filter by date
				if ($filterPair[0] == "studydate") {
					if (strpos($filterPair[1],"-") !== false) { 
						//date range
						$filterDates = explode("-", $filterPair[1]);
						//generate ranges for "froms" and "tos"
						if ($filterDates[0] == "") { $filterDates[0] = 20000000; } 
						if ($filterDates[1] == "") { $filterDates[1] = date(Ymd); }
						//compensate for short dates
						if (strlen($filterDates[0]) == 4) { $filterDates[0] .= "0101"; } // 2014-
						if (strlen($filterDates[0]) == 6) { $filterDates[0] .= "01"; } // 201401-
						if (strlen($filterDates[1]) == 4) { $filterDates[1] .= "1231"; } // -2015 
						if (strlen($filterDates[1]) == 6) { $filterDates[1] .= "31"; } // -201501
				
						if ($study[$i][4] < $filterDates[0] || $study[$i][4] > $filterDates[1]) {
							$filtered = "block";
						}
					} else { 
						//single date
						if (strpos($study[$i][4], $filterPair[1]) === false) {
							$filtered = "block";
						}
					}
				}
				// filter by status
				if ($filterPair[0] == "status") {
					$statuses = array ('new', 'draft', 'final', 'amended');
					$filteredStatuses = array_intersect($statuses, $filterPair);
					if (in_array($study[$i][9], $filteredStatuses) == false) {
						$filtered = "block";
					}
				}
				// filter by modality
				if ($filterPair[0] == "modality") {
					$statuses = array ('CR', 'DX', 'CT', 'MR', 'US', 'MG', 'RF', 'XA');
					$filteredStatuses = array_intersect($statuses, $filterPair);
					if (in_array($study[$i][11], $filteredStatuses) == false) {
						$filtered = "block";
					}
				}
			}

			if ($filtered == "pass" && $study[$i][9] != "deleted") { 
				//load current list into worklist
				$_SESSION['worklistPatientName'][$j] = $study[$i][2];
				$_SESSION['worklist'][$j] = $study[$i][7];

				//draw table
				echo "<tr>";
				echo "<td>" . $j . "</td>";
				echo "<td>" . $study[$i][0]. "</td>";
				echo "<td>" . $study[$i][2]. "</td>";
				echo "<td>" . $study[$i][3]. "</td>";
				echo "<td>" . substr($study[$i][4], 6, 2) . "/" . substr($study[$i][4], 4, 2) . "/" . substr($study[$i][4], 0, 4) . "</td>";
				$studyTime = round($study[$i][5]);
				if ($studyTime < 100000) { $studyTime = "" . "0" . $studyTime; }
				echo "<td>" . substr($studyTime, 0, 2) . ":" . substr($studyTime, 2, 2) . "</td>";
				echo "<td>" . $study[$i][11]. "</td>";
				echo "<td><a href=viewStudy.php?study=" . $study[$i][7] . ">" . $study[$i][6]. "</a></td>";
				echo "<td>" . $study[$i][9]. "</td>";
				echo "<td>" . $study[$i][10]. "</td>";
				// disallow Actions for user
				if ($_SESSION['level'] != 'user') {
					echo "<td>";
					if ($_SESSION['level'] == "admin") {
						echo "<a href=\"javascript:confirmDelete('" . $study[$i][7] . "')\">X</a> | ";
					}
					echo "<a href=printStudy.php?study=" . $study[$i][7] . " target=\"_blank\">PF</a>|";
					echo "<a href=printStudy.php?study=" . $study[$i][7] . "&format=bottom target=\"_blank\">PH</a>";
					echo "</td>";
				}
				$j++;
			}
		}
		echo "</table>";	
		?>
	<!-- adds space for footer-->
	<br><br>

</div>

<div id="count" style="position:absolute; top:230; left:0; z-index:100; width:100%">
	<?php echo "Total Cases: " . ($j-1); ?>
</div>

</center>
</body>