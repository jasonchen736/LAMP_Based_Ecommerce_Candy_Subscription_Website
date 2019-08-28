<?

	function exportTable ($dbName, $expTable, $nRows, $sRow, $wStr, $oStr) {

		require ('../v_library/v_site_config.php');
		require("../v_library/v_global.php");

		$tbl_name = mysql_real_escape_string(trim(strip_tags($expTable), $StripVars));

		if ($tbl_name != "") {

			$nRows = trim(strip_tags($nRows), $StripVars);
			$nRows = is_numeric($nRows) ? $nRows : 50;
			$sRow = trim(strip_tags($sRow), $StripVars);
			$sRow = (is_numeric($sRow) AND $sRow>0) ? $sRow-1 : 0;
			$wStr = strip_tags($wStr);
			$oStr = trim(strip_tags($oStr));

			$select = "SELECT * FROM $tbl_name $wStr $oStr LIMIT $sRow, $nRows";

			mysql_connect () or die ('Cannot connect to the database because: ' . mysql_error());
			mysql_select_db ($dbName) or die ('Unable to select database');

			$export = mysql_query($select);

			mysql_close();

			$fields = mysql_num_fields($export);

			$header = "";
			$data = "";

			for ($i = 0; $i < $fields; $i++) {
				$header .= mysql_field_name($export, $i) . "\t";
			}

			while ($row = mysql_fetch_row($export)) {
				$line = '';
				foreach ($row as $value) {
					if ((!isset($value)) OR ($value == "")) {
						$value = "\t";
					} else {
						$value = str_replace('"', '""', $value);
						$value = '"' . $value . '"' . "\t";
					}
					$line .= $value;
				}
				$data .= trim($line)."\n";
			}

			$data = str_replace("\r","",$data);

			if ($data == "") {
				$data = "\n(0) Records Found!\n";
			}

			header("Content-type: application/x-msdownload");
			header("Content-Disposition: attachment; filename=table_data.xls");
			header("Pragma: no-cache");
			header("Expires: 0");
			print "$header\n$data";

		} else {
			echo "Export Error: No Table Selected";
		}
	}

	exportTable ($_GET['d'], $_GET['t'], $_GET['nr'], $_GET['sr'], $_GET['c'], $_GET['o']);

?>