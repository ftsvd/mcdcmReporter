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
[userPreference.php]
	Setup user preferences
*/

// die if not logged in
if(isset($_SESSION['loggedin']) != 1){ die('<META http-equiv="REFRESH"  content="0; url=index.php">'); }

// hide all errors
error_reporting(0);
ini_set('display_errors', 0);

// include configuration file
include 'config.php';
?>

<body>
<center>
<table width=100% style="background-color: rgba(20, 40, 50, 0.5);" cellpadding=0 cellspacing=0>
	<tr>
		<td align=center>
			<a href=search.php><img src=img/logo2.jpg border=0></a><br>
			<?php echo "<font size=-1>Logged in as <b>" . $_SESSION['login'] . "</b> | "; ?>
			<a href=userPreference.php>Preferences</a> | <a href=index.php?logout=true>Log Out</a></font><br>
		</td>
	</tr>
</table>
<br>

<?php
//check for session login
if (!$_SESSION['level']) { die('<br><br><br><br>Incomplete login info.<br><a href=index.php>Log in again</a>'); }

//do user actions
if ($_POST['action'] != "") {
	if ($_SESSION['level'] == 'user' && $allowUserLevelToChangeDetails == true) {
		$userFile = file_get_contents($userListFile);
		$users = explode("\n", $userFile);
		if ($_POST['action'] == "edit") {
			$user[$_POST['u']]  = explode("|", $users[$_POST['u']]);
			$user[$_POST['u']][2] = $_POST['fullname'];
			$users[$_POST['u']] = implode("|", $user[$_POST['u']]);
			echo "Name change ";
		}
		if ($_POST['action'] == "changePassword") {
			$user[$_POST['u']]  = explode("|", $users[$_POST['u']]);
			$user[$_POST['u']][1] = password_hash($_POST['newPassword'], PASSWORD_BCRYPT);
			$users[$_POST['u']] = implode("|", $user[$_POST['u']]);
			echo "Change password ";
		}
		file_put_contents('password.lst', implode("\n", $users));
		echo "successful";
	} else {
		echo "Name & password change not allowed for this user level";
	}
}

//do template actions
if ($_POST['templateAction'] != "") {
	$templates = explode("<end>", file_get_contents("data/templates/" . $_SESSION['login'] . ".txt")); 
		
	if ($_POST['templateAction'] == "editTemplate") {
		$template[$_POST['t']] = explode("|", $templates[$_POST['t']]);
		$template[$_POST['t']][0] = $_POST['templateName'];
		$template[$_POST['t']][1] = $_POST['viewTemplate'];
		$templates[$_POST['t']] = implode("|", $template[$_POST['t']]);
		echo "Edit template ";
	}	
	if ($_POST['templateAction'] == "deleteTemplate") {
		unset($templates[$_POST['t']]);
		echo "Delete template ";
	}
		
	file_put_contents("data/templates/" . $_SESSION['login'] . ".txt", implode("<end>", $templates));
	echo "successful";
}
		
//reload user list
$userFile = file_get_contents($userListFile);
$users = explode("\n", $userFile);

//generate user info editing
echo "<form action=userPreference.php method=POST id=userEdit><input type=hidden id=action name=action><input type=hidden id=u name=u><input type=hidden id=fullname name=fullname><input type=hidden id=role name=role><input type=hidden name=newPassword id=newPassword>";
echo "<table width=600 border=0 cellpadding=5><tr><td colspan=4 bgcolor=204050>User Information</td></tr>\n";
echo "<tr><td>Name</td><td>Username</td><td>Role</td><td>Actions</td></tr>\n";
for ($u = 0; $users[$u] != ""; $u++) {
	$user[$u] = explode("|", $users[$u]);
	if ($user[$u][0] != $_SESSION['login']) { continue; }
	if (isset($_POST['u']) && $u == $_POST['u']) { $uBgColor = "330033"; } else { $uBgColor = "000000"; }
	echo "<tr bgcolor=" . $uBgColor . "><td>" . $user[$u][2] . "</td><td>" . $user[$u][0] . "</td><td>" . $user[$u][3] . "</td>";
	echo "<td><input type=button value=\"Edit Name\" onClick=\"submitAction('" . $u . "','edit','" . $user[$u][2] . "')\"> <input type=button value=\"Change Password\" onClick=\"popupPassword('" . $u . "','changePassword')\"></td>"; 
	echo "</tr>\n";
}
echo "</table>";
echo "</form>";

if ($_SESSION['level'] != "user") {
	//load templates
	//read templates
	if (file_exists("data/templates/" . $_SESSION['login'] . ".txt")) { 
		$templates = explode("<end>", file_get_contents("data/templates/" . $_SESSION['login'] . ".txt")); 
	} 

	echo "<table width=500 border=0 cellpadding=5><tr><td colspan=2 bgcolor=204050>User Templates</td></tr>\n<tr>\n";
	echo "<form action=userPreference.php method=POST id=templateEdit><input type=hidden id=templateAction name=templateAction><input type=hidden id=t name=t>\n";

	$templateOptions = "<option value=\"" . count($templates) . "\">(New)</option>\n";
	$templateJSValues = "";
	for ($t = 0; $templates[$t] != ""; $t++) {
		$template = explode("|", $templates[$t]);
		$templateOptions .= "<option value=\"" . $t . "\">" . $template[0] . "</option>\n";
		$template[1] = str_replace("\n", "<br>", $template[1]);
		$template[1] = str_replace("\r", "", $template[1]);
		$templateJSValues .= "templateJSValues[" . $t . "] = \"" . $template[1] . "\";\n";
		$templateNameJSValues .= "templateNameJSValues[" . $t . "] = \"" . $template[0] . "\";\n";
	}
	//adds blank for (new)
	$templateJSValues .= "templateJSValues[" . $t . "] = \"\";\n";
	$templateNameJSValues .= "templateNameJSValues[" . $t . "] = \"\";\n";

	echo "<td>Template name:<br><input type=text size=20 name=templateName id=templateName></td>";
	echo "<td align=right>Templates:<br><select onChange=\"loadTemplate()\" id=selectedTemplate>" . $templateOptions . "</select></td></tr>";
	echo "<tr><td colspan=2><textarea name=viewTemplate id=viewTemplate cols=80 rows=20></textarea></td></tr>\n"; 
	echo "<tr><td colspan=2 align=center><input type=button value=\"Save This Template\" onClick=\"submitTemplateAction('editTemplate')\"> <input type=button value=\"Delete This Template\" onClick=\"submitTemplateAction('deleteTemplate')\">";
	echo "</form>";
	echo "</td></tr></table>\n";
}
?>

<script>
function loadTemplate() {
	var templateJSValues = [];
	var templateNameJSValues = [];
	<?php echo $templateJSValues; ?>
	<?php echo $templateNameJSValues; ?>
	
	var t = document.getElementById('selectedTemplate').value;
	if (t) {
		document.getElementById('templateName').value = templateNameJSValues[t];
		document.getElementById('viewTemplate').value = templateJSValues[t].replace(/<br>/g, "\n");
	} else {
		document.getElementById('templateName').value = "";
		document.getElementById('viewTemplate').value = "";	
	}
}

function submitTemplateAction(action) {
	if (document.getElementById('templateName').value == "" || document.getElementById('viewTemplate').value == "") {
		alert('Template name and Template text cannot be empty!');
		return;
	}
	document.getElementById('t').value = document.getElementById('selectedTemplate').value;
	document.getElementById('templateAction').value = action;
	if (action == "deleteTemplate") { 
		if (!confirm('Confirm delete of this template?\n\nThe other templates will not be affected.')) { return; }
	}
	document.getElementById('templateEdit').submit();
}

</script>


<script>
function submitAction(u, action, fullname) {
	document.getElementById('u').value = u;
	document.getElementById('action').value = action;

	if (action == "edit") { 
		var newName = prompt('Edit Name', fullname);
		if (newName != null && newName != "") {
			document.getElementById('fullname').value = newName; 
		} else {
			return;
		}
	}
	if (action == "changePassword") { 
		if (document.getElementById('newPassword1').value != document.getElementById('newPassword2').value) { 
			alert('Passwords do not match!');
			return;
		} else {
			document.getElementById('newPassword').value = 	document.getElementById('newPassword1').value;
		}
	}
	document.getElementById("userEdit").submit();
}

function popupPassword(u, action, fullname, role) {
	if (document.getElementById('popupPassword').style.display == "block") { 
		document.getElementById('popupPassword').style.display = "none";
		return;
	}
	document.getElementById('popupPassword').style.display = "block";
	document.getElementById('popupPasswordSubmit').innerHTML = "<input type=button value=\"Change Password\" onclick=submitAction('" + u + "','" + action + "','')>";
}	
</script>

<div id="popupPassword" style="display:none; position:fixed; top:200; left:0; width:100%">
	<center>
		<table border=1 width=400 bgcolor=000000>
			<tr>
				<td align=center><br>
					<b>Enter new password: <input type="password" name="newPassword1" id="newPassword1"><br><br>
					Confirm new password: <input type="password" name="newPassword2" id="newPassword2"><br><br>
					<span id="popupPasswordSubmit"></span> <a href="javascript:popupPassword()">[ Cancel ]</a><br><br>
				</td>
			</tr>
		</table>
	</center>
</div>

</center>
</body>