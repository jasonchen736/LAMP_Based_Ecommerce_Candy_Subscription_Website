<?

	require("sajax/Sajax.php");

	mysql_connect() or die("Connect error: " . mysql_error());

	$showDB = mysql_query("SHOW DATABASES");
	$dbSelector = '<select name="dbSelect" id="dbSelect"" onchange="displayTables(this.value);">
						<option value="">Select Database</option>';

	while($dbRow = mysql_fetch_array($showDB)) {
		$dbSelector .= '
						<option value="'.$dbRow[0].'">'.$dbRow[0].'</option>';
	}

	$dbSelector .= '
					</select>';

	$tableSelector = '<select name="tSelect" id="tSelect">
						<option value="">Select Table</option>
					</select>';

	$importFile = "";
	$newT = "";

	if ($_SERVER['REQUEST_METHOD']=="POST") {
		if (isset($_POST['import'])) {
			require ('../v_library/v_site_config.php');
			require("../v_library/v_global.php");

			$db = trim(strip_tags($_POST['dbSelect']), $StripVars);
			$tbl_name = trim(strip_tags($_POST['tSelect']), $StripVars);
			$newT = (isset($_POST["eraseTable"]) AND isset($_POST["confirmErase"])) ? 1 : 0;

			if ($db != "" AND $tbl_name != "") {
				mysql_select_db($db) or die("Select error: " . mysql_error());
				$importFile = basename($_FILES['ImportF']['name']);
				$target_path = "imports/";
				$target_path = $target_path.basename($_FILES['ImportF']['name']);
				// Uploading files
				if (move_uploaded_file($_FILES['ImportF']['tmp_name'], $target_path)) {

					$msg = "";

					// expects the file to be in the same dir as this script
					$fcontents = file ($target_path);

					if ($newT == 1) {
						$sql = "TRUNCATE TABLE $tbl_name;";
						mysql_query($sql);
					}

					for ($i=1; $i<sizeof($fcontents); $i++) {
						$line = addslashes(trim($fcontents[$i]));
						$arr = explode("\t", $line);
						// if your data is comma separated
						// instead of tab separated,
						// change the '\t' above to ','
						$sql = "INSERT INTO $tbl_name VALUES ('".implode("','", $arr)."')";
						mysql_query($sql) or die("<center><b>Data input error</b><br>".mysql_error()."<br><a href=\"".$_SERVER['PHP_SELF']."\">Back</a></center>");
					}

					// unlink deletes file
//					unlink($target_path);
					$msg = $msg == "" ? "$importFile successfully imported into $tbl_name" : $msg;
				} else {
					$msg = "No data file/Error handling data file";
				}
			} else {
				$msg = "Database/Table not selected";
			}
		}
	} elseif ($_SERVER['REQUEST_METHOD']=="GET") {

		require ('../v_library/v_site_config.php');
		require("../v_library/v_global.php");

		$db = trim(strip_tags($_GET['d']), $StripVars);
		$tbl_name = trim(strip_tags($_GET['t']), $StripVars);

		if ($db != "" AND $tbl_name != "") {

			$dbTables = mysql_query("SHOW TABLES FROM $db");
			$tableSelector = '<select name="tSelect" id="tSelect"">
						<option value="">Select Table</option>';

			while($tRow = mysql_fetch_array($dbTables)) {
				$tableSelector .= '
						<option value="'.$tRow[0].'">'.$tRow[0].'</option>';
			}

			$tableSelector .= '
					</select>';

			$init = 'document.getElementById("dbSelect").value = "'.$db.'";
		document.getElementById("tSelect").value = "'.$tbl_name.'";';

		}

		$msg = "Import data file";

	} else {
		$msg = "Import data file";
	}

	require ('v_functions.php');

	mysql_close();

	$sajax_request_type = "POST";
	sajax_init();
	sajax_export("tDisplay");
	sajax_handle_client_request();

$header='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>

	<title>Import File</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

	<script>
	<!--

'.sajax_get_javascript().'

	function initVars () {
		'.$init.'
	}

	function tDisplay_cb (tSel) {
		document.getElementById("tSelector").innerHTML = tSel;
	}

	function displayTables (thisDB) {
		if (thisDB == "") {
			document.getElementById("tSelector").innerHTML = "<select></select>";
		} else {
			x_tDisplay (thisDB, tDisplay_cb);
		}
	}

	function displayRecords (tValue) {

	}

	function executeConfirm () {
		var clearT;
		if (document.getElementById("eraseTable").checked && document.getElementById("confirmErase").checked) clearT = "Yes"; else clearT = "No";
		var executeConfirm = confirm("Confirm action\nDatabase: " + document.getElementById("dbSelect").value + "\nTable: " + document.getElementById("tSelect").value + "\nOverwrite table: " + clearT + "\nDatafile: " + document.getElementById("ImportF").value);
		return executeConfirm;
	}

	//-->
	</script>

	<Style>
	<!--
		.highlight { background-color: #33CCFF }
		.alt1 { background-color: #CCCCCC }
		.alt2 { background-color: #FFFFFF }
		.alt3 { background-color: #F7EDF0 }
		span:visited{ text-decoration:none; color:#293d6b; }
		span:hover{ text-decoration:underline; color:#293d6b; }
		span {color:#293d6b; cursor: pointer}
	//-->
	</style>

</head>
';

$body='
<body onload="initVars();">

	<table width="50%" align="center">
		<tr width="100%">
			<td align="center">
				<b>File Import</b>
				<br>
				<form action="'.$_SERVER['PHP_SELF'].'" method="post" enctype="multipart/form-data" onSubmit="return executeConfirm();">
					'.$dbSelector.'
					<span id="tSelector">
					'.$tableSelector.'
					</span>
					<hr><br>
					<div>txt / tab separated / non deliminated format only (for now)</div>
					<div><input type="hidden" name="MAX_FILE_SIZE" value="1048576000"><input type="file" name="ImportF" id="ImportF" size="60"></div>
					<div><input type="submit" name="import" value="Import"></div>
					<br><hr>
					<div>
						<table>
							<tr><td>Overwrite table</td><td><input type="checkbox" value="" name="eraseTable"></td><td>Confirm overwrite</td><td><input type="checkbox" value="" name="confirmErase"></td></tr>
							<tr><td colspan="4"><font size="-1">Table will be truncated prior to data import attempt</font></td></tr>
						</table>
					</div>
					<hr>
				</form>
			</td>
		<tr>
		<tr><td align="center" class="alt3">'.$msg.'</td></tr>
	</table>

</body>
';

$footer = '
</html>
';

	echo $header,$body,$footer;

?>