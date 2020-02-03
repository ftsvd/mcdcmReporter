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
[adminPortal.php]
	Create / Edit / Reset password for Users
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
//check for admin status
if ($_SESSION['level'] != "admin") { die('<br><br><br><br>You are not an admin. :P<br><a href=search.php>Return to Search Page</a>'); }

//do actions
if ($_POST['action'] != "") {
	$userFile = file_get_contents($userListFile);
	$users = explode("\n", $userFile);
	if ($_POST['action'] == "edit") {
		$user[$_POST['u']]  = explode("|", $users[$_POST['u']]);
		$user[$_POST['u']][2] = $_POST['fullname'];
		$users[$_POST['u']] = implode("|", $user[$_POST['u']]);
		echo "Name change ";
	}
	if ($_POST['action'] == "role") {
		$user[$_POST['u']]  = explode("|", $users[$_POST['u']]);
		$user[$_POST['u']][3] = $_POST['role'];
		$users[$_POST['u']] = implode("|", $user[$_POST['u']]);
		echo "Role change ";
	}
	if ($_POST['action'] == "reset") {
		$user[$_POST['u']]  = explode("|", $users[$_POST['u']]);
		$user[$_POST['u']][1] = password_hash($defaultPassword, PASSWORD_BCRYPT);
		$users[$_POST['u']] = implode("|", $user[$_POST['u']]);
		echo "Default password is [" . $defaultPassword . "].<br>";
		echo "Password reset ";
	}
	if ($_POST['action'] == "add") {
		$user[$_POST['u']][0] = $_POST['newusername'];
		$user[$_POST['u']][1] = password_hash($defaultPassword, PASSWORD_BCRYPT);
		$user[$_POST['u']][2] = $_POST['newfullname'];
		$user[$_POST['u']][3] = $_POST['newrole'] . "|";
		$users[$_POST['u']] = implode("|", $user[$_POST['u']]);
		echo "Default password is [" . $defaultPassword . "].<br>";
		echo "Add user ";
	}
	if ($_POST['action'] == "delete") {
		unset($users[$_POST['u']]);
		echo "Delete ";
	}
	file_put_contents('password.lst', implode("\n", $users));
	echo "successful.";
}
	
//reload user list
$userFile = file_get_contents($userListFile);
$users = explode("\n", $userFile);

//generate table
echo "<form action=adminPortal.php method=POST id=userEdit><input type=hidden id=action name=action><input type=hidden id=u name=u><input type=hidden id=fullname name=fullname><input type=hidden id=role name=role>";
echo "<table cellpadding=5><tr><td></td><td>Name</td><td>Username</td><td>Role</td><td>Actions</td></tr>\n";
for ($u = 0; $users[$u] != ""; $u++) {
	$user[$u] = explode("|", $users[$u]);
	if (isset($_POST['u']) && $u == $_POST['u']) { $uBgColor = "330033"; } else { $uBgColor = "000000"; }
	echo "<tr bgcolor=" . $uBgColor . "><td>" . ($u + 1) . "</td><td>" . $user[$u][2] . "</td><td>" . $user[$u][0] . "</td><td>" . $user[$u][3] . "</td>";
	echo "<td><input type=button value=\"Edit Name\" onClick=\"submitAction('" . $u . "','edit','" . $user[$u][2] . "')\"> <input type=button value=\"Edit Role\" onClick=\"popupRole('" . $u . "','role','" . $user[$u][2] . "','" . $user[$u][3] . "')\"> <input type=button value=\"Reset Password\" onClick=\"submitAction('" . $u . "','reset','" . $user[$u][2] . "','" . $user[$u][3] . "')\"> <input type=button value=\"Delete\" onClick=\"submitAction('" . $u . "','delete','" . $user[$u][2] . "')\"></td>"; 
	echo "</tr>\n";
}
echo "<tr><td>" . ($u+1) . "</td><td><input type=text name=newfullname id=newfullname size=20></td><td><input type=text id=newusername name=newusername size=8></td><td>";
echo "<select name=newrole><option value=radiologist>radiologist</option><option value=user>user</option><option value=admin>admin</option></select>";
echo "</td><td><input type=button value=\"Add New\" onClick=\"submitAction('" . $u . "','add')\"></td></tr>\n";
echo "</table>";
echo "</form>";
?>

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
	if (action == "role") { document.getElementById('role').value = document.getElementById('popUpRoleSelect').value; }
	if (action == "delete") { 
		if (!confirm("Delete " + fullname + "?")) { return; }
	}
	if (action == "reset") { 
		if (!confirm("Reset password for " + fullname + "?")) { return; }
	}
	document.getElementById("userEdit").submit();
}

function popupRole(u, action, fullname, role) {
	if (document.getElementById('popupRole').style.display == "block") { 
		document.getElementById('popupRole').style.display = "none";
		return;
	}
	document.getElementById('popupRole').style.display = "block";
	document.getElementById('popupRoleFullname').innerHTML = fullname;

	var adminSelected = "";
	var userSelected = "";
	var radiologistSelected = "";
	if (role == 'admin') { var adminSelected = "SELECTED"; }
	if (role == 'user') { var userSelected = "SELECTED"; }
	if (role == 'radiologist') { var radiologistSelected = "SELECTED"; }
	
	var htmlRoles = "<select id=popUpRoleSelect onChange=\"submitAction('" + u + "','" + action + "','')\">\n";
	htmlRoles += "<option value=admin " + adminSelected + ">Admin</option>\n";
	htmlRoles += "<option value=user " + userSelected + ">User</option>\n";
	htmlRoles += "<option value=radiologist " + radiologistSelected + ">Radiologist</option>\n";
	htmlRoles += "</select>\n";
	document.getElementById('popupRoleOptions').innerHTML = htmlRoles;
}	
</script>

<div id="popupRole" style="display:none; position:fixed; top:200; left:0; width:100%">
	<center>
		<table border=1 width=400 bgcolor=000000>
			<tr>
				<td align=center>
					<b>Edit role for: <span id="popupRoleFullname"></span><br><br>
					<span id="popupRoleOptions"></span><br><br>
					<a href="javascript:popupRole()">[ Cancel ]</a>
				</td>
			</tr>
		</table>
	</center>
</div>

</center>
</body>