<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>

<?php 

include "pgga.php";
include "pokedex.php"; // $pokedex

$GLOBALS['DATA_APPENDIX'] = "";
if (isset($_GET['data']))
{
	$_data_folder = htmlspecialchars(strip_tags($_GET['data']));
	$GLOBALS['DATA_APPENDIX'] = "&data=" . urlencode($_data_folder);
}
else
	$_data_folder = "data";

// Get list of data files (folder $_data_folder)
$_data_files = array();
foreach (glob($_data_folder."/*.txt") as $filename) {
    $_data_files[] = $filename;
}
//print_r($_data_files);

if (isset($_GET['mid']))
	$_mid = htmlspecialchars(strip_tags($_GET['mid']));
else
	$_mid = end($_data_files);

if (isset($_GET['gym']))
{
	$_gym = htmlspecialchars(strip_tags($_GET['gym']));
	$_mid = "Gym"; // Alter Menu-ID
}

if (isset($_GET['trainer']))
{
	$_trainer = htmlspecialchars(strip_tags($_GET['trainer']));
	$_mid = "Trainer"; // Alter Menu-ID
}


// Vars
$menu_str = array("data" => "Graz now (since July 8, 2017)", "data/graz2" => "Graz pre/post raid transition (June 19, 2017 &amp; July 9, 2017)", "data/graz1" => "Graz pre raids (May 5 - June 19, 2017)",);
$mainmenu_appdx = "";
$MESSAGE = "";
$SHOW_MESSAGE = false;

?>

<title>Pokemon Go - Gym Analyis<?php echo $mainmenu_appdx; ?></title>

<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="content-language" content="en">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="description" content="Pokemon Go - Gym Analyis">
<meta name="author" content="Celegast">
<meta name="copyright" content="Celegast">
<meta name="keywords" content="">
<meta name="date" content="<?php echo date('Y-m-d'); ?>">

<link rel="icon" type="image/png" href="pgga.ico" />

<link rel="stylesheet" type="text/css" href="css/pgga.css">

<script src="sorttable.js"></script>

<style>
table.sortable th
{ 
	cursor: pointer;
	cursor: hand;
	/* content: " \25B4\25BE" */
}
</style>

</head>

<body>
<div id="site">

<div class="box">

	<div id="top">
	<p>&nbsp;</p>
	<font size=45><b>Pokemon Go - Gym Analysis</b></font>

	<ul id="menu">
	<?php
	$i = -1;

	foreach ($menu_str as $data_folder => $menu_item)
	{
		$i++;
		
		echo "		<li class=\"";
		if ($_data_folder == $data_folder) echo "current";
			else echo "m" . $i;
		echo "\">";
		
		$tmp = ($i == 0) ? "" : ("?data=".urlencode($data_folder));
		echo "<a href=\"index.php" . $tmp . "\">" . $menu_item . "</a>";

		echo "</li>\n";
	}
	?>
	</ul>

	</div>
	
	<div id="middle">

<?php // Additional ($_mid dependent) <div>s

	// Left menu
	echo "<div id=\"leftmenu\">\n<ul>";

	$_data_timestamps = array();
	$nr = 1;
	foreach ($_data_files as $data_file)
	{
		echo "<li><a href=\"index.php?mid=" . $data_file . $GLOBALS['DATA_APPENDIX'] . "\"";
		if ($_mid == $data_file)
			echo " id=\"selected\"";
		
		$_data_timestamps[] = str_replace(array('.txt','data/'),'',$data_file);//date('Y-m-d H:i:s', filemtime($data_file));
		echo ">" . end($_data_timestamps) . "</a> " . $nr++ . "</li>\n";
	}

	// Gym History
	echo "<li><a href=\"index.php?mid=History" . $GLOBALS['DATA_APPENDIX'] . "\"";
	if ($_mid == "History") echo " id=\"selected\"";
	echo ">Gym History</a></li>\n";

	// Volatility Index
	echo "<li><a href=\"index.php?mid=VIX" . $GLOBALS['DATA_APPENDIX'] . "\"";
	if ($_mid == "VIX") echo " id=\"selected\"";
	echo ">Volatility Index (VIX)</a></li>\n";

	echo "</ul></div>\n"; // End: Left menu

switch ($_mid)
{
	case 0: // Home
		{
		}
		break;
}
?>

		<div id="content">

<?php // Content

if ($SHOW_MESSAGE)
	echo "<p id=\"message\">" . $MESSAGE . "</p>";

switch ($_mid)
{
	case "History":
		{
			if (sizeof($_data_files) <= 1)
				continue;
				
			$pgga = new PGGA();
			$pgga2 = new PGGA();
			
			$i = 0;
			reset($_data_timestamps);
			
			foreach ($_data_files as $data_file)
			{
				if ($i == 0) // First data file
				{
					$pgga->Parse_Gyms_From_Html_File($data_file,$pokedex);
					$gym_list_1 = $pgga->Get_Gym_List();
				}

				$pgga2->Parse_Gyms_From_Html_File($data_file,$pokedex);
				$pgga->Calculate_Gym_History($gym_list_1, $pgga2->Get_Gym_List(), current($_data_timestamps));
				next($_data_timestamps);
				
				$i++;
			}
			
			// Sort by sector (ASC)
			uasort($gym_list_1, 'sectorAscSort');

			echo $pgga->Create_Gym_History_Table($gym_list_1,$_data_timestamps);
			//print_r($gym_list_1);
			
			break;
		}
	case "VIX":
		{
			if (sizeof($_data_files) <= 1)
				continue;
				
			$pgga = new PGGA();
			$pgga2 = new PGGA();
			
			$i = 0;
			reset($_data_timestamps);
			
			foreach ($_data_files as $data_file)
			{
				if ($i == 0) // First data file
				{
					$pgga->Parse_Gyms_From_Html_File($data_file,$pokedex);
					$gym_list_1 = $pgga->Get_Gym_List();
				}
				else // Calculate volatility index
				{
					$pgga2->Parse_Gyms_From_Html_File($data_file,$pokedex);
					$pgga->Calculate_Volatility_Index($gym_list_1, $pgga2->Get_Gym_List(), next($_data_timestamps));
				}
				
				$i++;
			}
			
			// Sort by sector (ASC)
			uasort($gym_list_1, 'sectorAscSort');

			echo $pgga->Create_Volatility_Index_Table($gym_list_1,$_data_timestamps);
			//print_r($gym_list_1);
			
			break;
		}
	case "Trainer":
		{
			if (sizeof($_data_files) <= 1)
				continue;
			
			// Reverse arrays
			$_data_timestamps = array_reverse($_data_timestamps);
			$_data_files = array_reverse($_data_files);
				
			echo "<h1>" . $_trainer . "</h1>\r\n";
				
			$pgga = new PGGA();
			$trainer_team = "";
			
			$i = 0;
			reset($_data_timestamps);
			
			foreach ($_data_files as $data_file)
			{
				$pgga->Parse_Gyms_From_Html_File($data_file,$pokedex);
				$tmp = $pgga->Create_Trainer_Table($_trainer, current($_data_timestamps), $trainer_team);
				
				if ($i == 0) // Header
				{
					echo "<h2>" . $trainer_team . "</h2>\r\n";
					echo "<p><font size=\"-2\"><br>Note: Use column headers to sort tables</font></p>\r\n";

					// Pack all following tables into one big table to keep all centered
					echo "<p><table><tr><td style=\"border:0px;\">";
				}
				
				echo $tmp;
				next($_data_timestamps);
				
				$i++;
			}

			echo "</td></tr></table></p>";
			
			break;
		}
	case "Gym":
		{
			if (sizeof($_data_files) <= 1)
				continue;
			
			// Reverse arrays
			$_data_timestamps = array_reverse($_data_timestamps);
			$_data_files = array_reverse($_data_files);
				
			echo "<h1>" . $_gym . "</h1>\r\n";
				
			$pgga = new PGGA();
			$sector = "";
			
			$i = 0;
			reset($_data_timestamps);
			
			foreach ($_data_files as $data_file)
			{
				$pgga->Parse_Gyms_From_Html_File($data_file,$pokedex);
				$tmp = $pgga->Create_Individual_Gym_Table($_gym, current($_data_timestamps), $sector);
				
				if ($i == 0) // Header
				{
					if ($sector != "")
						echo "<h2>Sector " . $sector . "</h2>\r\n";
					
					echo "<p><font size=\"-2\"><br>Note: Use column headers to sort tables</font></p>\r\n";

					// Pack all following tables into one big table to keep all centered
					echo "<p><table><tr><td style=\"border:0px;\">";
				}
				
				echo $tmp;
				next($_data_timestamps);
				
				$i++;
			}

			echo "</td></tr></table></p>";
			
			break;
		}
	default:
		{
error_reporting(E_ALL);

			if ($_mid == "" || !file_exists($_mid))
			{
				echo "<br>No files in folder '\\" . $_data_folder . "'.";
				continue;
			}

			$filename = $_mid;

			$pgga = new PGGA();
			$pgga->Parse_Gyms_From_Html_File($filename,$pokedex);

			echo $pgga->Create_Overall_Gym_Statistics_Table();
			echo "<p>&nbsp;</p>";
			echo $pgga->Create_Trainer_Statistics_Table();
			echo "<p>&nbsp;</p>";
			echo $pgga->Create_Pokemon_Statistics_Table(true); // $lvl10_only = true

error_reporting(E_ALL);

			//print_r($pgga->Get_Gym_Names_By_Sector());

/*
			$filename = "gyms_graz_04052017.txt";

			$pgga2 = new PGGA();
			$pgga2->Parse_Gyms_From_Html_File($filename,$pokedex);

			$gym_names1 = $pgga->Get_Gym_Names();
			$gym_names2 = $pgga2->Get_Gym_Names();
			$missing_gyms1 = array();
			$missing_gyms2 = array();

			foreach ($gym_names1 as $gym_name)
			{
				if (!in_array($gym_name,$gym_names2))
					$missing_gyms1[] = $gym_name;
			}
			foreach ($gym_names2 as $gym_name)
			{
				if (!in_array($gym_name,$gym_names1))
					$missing_gyms2[] = $gym_name;
			}
			
			print_r ($missing_gyms1);
			print_r ($missing_gyms2);
			*/
/*
			// Pokemon Go gym parsing script test
			$filename = "http://148.251.192.149/?lat=47.09&lng=15.42";
			
			echo $filename . "<br><br>\n\n";
			$dataFile = fopen( $filename, "r" ) ;
			$html_string = "";
			
			while (!feof($dataFile)) 
			{
				$buffer = fgets($dataFile, 4096);
				$html_string = $html_string . $buffer;
			}
			fclose($dataFile);
			echo htmlspecialchars($html_string);
*/

		}
		break;
		
}

?>
			<p>&nbsp;</p>
		</div>

	</div>

	<div style="clear:both;"></div>
	
	<div id="bottom">
		<div id="footer">
			<div class="tree">
<?php
if (isset($menu_footer_str[$_mid]))
	echo "<a href=\"index.php?mid=" . $_mid . $GLOBALS['DATA_APPENDIX'] . "\">" . $menu_footer_str[$_mid] . "</a>\n";
?>
			</div>
			<div class="toplink"><a href="#">back to top &uarr;</a></div>
			<p>Celegast &copy; 20<?php echo date("y"); ?></p>
		</div>
	</div>

</div>

</div>


</body>
</html>
