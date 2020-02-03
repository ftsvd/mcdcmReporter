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
[setup.php]
	Creates PostgreSQL database and table for mcdcmReporter
*/

// display all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// include configuration file
include 'config.php';

?>
<b>Output:</b><br><pre>
--begin output--

<?php

//Creates database
$conn = pg_connect("host=" . $servername . " port=" . $serverport . " user=" . $username . " password=" . $password);
$query = "CREATE DATABASE " . $dbname;
$result = pg_query($conn, $query);
pg_close($conn);

//Create table
$conn = pg_connect("host=" . $servername . " port=" . $serverport . " dbname=" . $dbname . " user=" . $username . " password=" . $password);
$query = "CREATE TABLE isendat (
			patientid CHARACTER VARYING,
			otherpatientid CHARACTER VARYING,
			patientname CHARACTER VARYING,
			patientsex CHARACTER VARYING,
			studydate CHARACTER VARYING,
			studytime CHARACTER VARYING,
			studydescription CHARACTER VARYING,
			orthancstudyuid CHARACTER VARYING UNIQUE,
			studyinstanceuid CHARACTER VARYING,
			reportstatus CHARACTER VARYING,
			reportedby CHARACTER VARYING,
			modality CHARACTER VARYING,
			accessionnumber CHARACTER VARYING,
			retired_studycomments CHARACTER VARYING,
			institutionaldepartmentname CHARACTER VARYING,
			imagecomments CHARACTER VARYING
			);";
$result = pg_query($conn, $query);
pg_close($conn);

?>

--end output--
</pre>
<br>
<a href=index.php?logout=true>Login Again</a>