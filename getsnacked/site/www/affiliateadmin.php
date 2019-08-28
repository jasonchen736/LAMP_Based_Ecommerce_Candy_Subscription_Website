<?

	session_start();
	header("Cache-control: private");

	$ID = isset($_SESSION["ID"]) ? $_SESSION["ID"] : "";
	
$header='<HTML>
<HEAD>
<TITLE>Veisi Deep Cleanse</TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
</HEAD>
<BODY BGCOLOR=#FFFFFF LEFTMARGIN=0 TOPMARGIN=0 MARGINWIDTH=0 MARGINHEIGHT=0>
<center>
<br>
Affiliate Admin
<br>
<form action="http://www.veisi.com/referreradmin.php" method="post">
	<input type="submit" name="refadmin" value="Create Referral Tag Name">
</form>
<br>
';

$tagform='
<form method="post" action="'.$_SERVER['PHP_SELF'].'">
	<table align="center">
		<tr>
			<td align="center" colspan="4">Enter ID and SubID to attribute tracking code to.<br>Leave Sub ID blank to create a Global Tag.</td>
		</tr>
		<tr>
			<td align="center" colspan="4">Global Tags are attributed to all Sub IDs without a tag of its own.</td>
		</tr>
		<tr>
			<td>ID:</td><td>'.$ID.'<input type="hidden" value="'.$ID.'" name="ID"></td>
			<td>Sub ID:</td><td align="left"><input type="text" value="" name="SID"></td>
		</tr>
		<tr>
			<td colspan="4" align="center">Tracking Code:</td>
		</tr>
		<tr>
			<td colspan="4" align="center"><textarea cols="50" rows="10" name="tcode"></textarea></td>
		</tr>
		<tr>
			<td colspan="4" align="center"><input type="submit" name="submittcode" value="Submit Code"></td>
		</tr>
	</table>
</form>
';

$footer='
</center>
</BODY>
</HTML>
';

	if ($ID=="") {
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}
		session_destroy();
		echo $header,'<br>Invalid ID<br><br>',$footer;
		exit;
	}
	
	if (isset($_POST['submittcode'])) {
		$ID = $_POST['ID'];
		$SID = $_POST['SID'];
		if ($SID == "") { $SID="0"; }
		$TCode = $_POST['tcode'];

		$tagexist='
<form method="post" action="'.$_SERVER['PHP_SELF'].'">
	<table align="center">
		<tr>
			<td align="center" colspan="4">You already have a tracking tag in place for this ID/SubID combination.</td>
		</tr>
		<tr>
			<td>ID:</td><td>'.$ID.'<input type="hidden" value="'.$ID.'" name="ID"></td>
			<td>Sub ID:</td><td align="left"><input type="text" value="'.$SID.'" name="SID"></td>
		</tr>
		<tr>
			<td colspan="4" align="center">Tracking Code:</td>
		</tr>
		<tr>
			<td colspan="4" align="center"><textarea cols="50" rows="10" name="tcode">'.$TCode.'</textarea></td>
		</tr>
		<tr>
			<td colspan="4" align="center">
				<table align="center">
					<tr>
						<td>Yes</td><td><input type="radio" name="TConfirmation" value="Yes" checked></td>
						<td>No</td><td><input type="radio" name="TConfirmation" value="No"></td>
						<td><input type="submit" name="submittcode" value="Continue"></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
';
		
		if ($TCode == "") {
			echo $header,'<font color="#FF0000"><b>Code Missing</b></font>',$tagform,$footer;
			exit;
		} else {
			if (!is_numeric($ID) OR $ID>999999999 OR !is_numeric($SID) OR $SID>999999999) {
				$message="";
				if (!is_numeric($ID) OR $ID>999999999) {$message=$message."<font color=\"#FF0000\">Invalid ID</font>";}
				if (!is_numeric($SID) OR $SID>999999999) {$message=$message."<font color=\"#FF0000\">Invalid SID</font>";}
				echo $header,'<font color="#FF0000"><b>Affiliate ID Error</b></font>',$message,$tagform,$footer;
				exit;
			} else {
				mysql_connect () or die ('Cannot connect to the database because: ' . mysql_error());
				mysql_select_db ("veisi_Tracking") or die ('Unable to select database');
								
				if (!isset($_POST["TConfirmation"])) {
					$result = mysql_query("SELECT * FROM ttags WHERE ID=$ID AND SubID='$SID'");
 					if (mysql_num_rows($result)>0) {
    					echo $header,$tagexist,$footer;
						mysql_close();
						exit;										
					}
				}
				
				if ($_POST["TConfirmation"]=="No") {
				
					$tagconfirm='
<br>
Tag Aborted
<br><br>
';

				} else {
				
					$query = "UPDATE ttags SET Tag='$TCode' WHERE ID=$ID AND SubID='$SID'";
	
					mysql_query($query);
					if (!mysql_affected_rows()) {
						mysql_query("INSERT INTO ttags VALUES ($ID, '$SID', '')");
						mysql_query($query);
					}
					$gettag="SELECT Tag FROM ttags WHERE ID=$ID AND SubID='$SID'";
					$result=mysql_query($gettag);
				
					mysql_close();

					$tagconfirm='
<br>
Tag Entered
<br><br>
<table>
	<tr><td align="center"><b>ID: </b>'.$ID.'</td><td align="center"><b>Sub ID: </b>'.$SID.'</td></tr>
	<tr><td colspan="2" align="left"><textarea cols="50" rows="10" name="tcode">'.mysql_result($result, 0, "Tag").'</textarea></td></tr>
</table>
';
				
				}
				
				echo $header,$tagconfirm,$footer;
				exit;
				
			}
			
		}
		
	}
	
	echo $header,$tagform,$footer;

?>