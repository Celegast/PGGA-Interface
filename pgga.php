<?php
/*

	This file contains all 'Pokemon Go Gym Analysis' (PGGA) related classes and functions
	
	Necessary parameters:
		
	Return parameters:

*/

function countDescSort($item1,$item2)
{
	if ($item1['Count'] == $item2['Count']) return 0;
	return ($item1['Count'] < $item2['Count']) ? 1 : -1;
}

function sectorAscSort($item1,$item2)
{
	if ($item1['Sector'] == $item2['Sector']) return 0;
	return ($item1['Sector'] > $item2['Sector']) ? 1 : -1;
}

function unique_multidim_array($array, $key) {
    $temp_array = array();
    $i = 0;
    $key_array = array();
   
    foreach($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}

function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}

class PGGA
{
	public $gyms = array();
	
	function __construct()
	{
	}

	function Parse_Gyms_From_Html_File($file_name,$pokedex)
	{
		$dataFile = fopen($file_name, "r");
		if (!$dataFile)
		{
			throw new Exception('Could not open file: ' . $file_name);
		}

		$html_string = "";
		while (!feof($dataFile)) 
		{
			$buffer = fgets($dataFile, 4096);
			$html_string = $html_string . $buffer;
		}
		fclose($dataFile);
		//echo "<!-- " . $html_string . "-->";

		// Pre-Parse table into content(-array)
		$t=array("</TD>","<td>","</TR>","<tr>","</tr>","<tbody>","</tbody>","</table>","<table class=\"gympop\" cellspacing=\"0\" cellpadding=\"0\">","<table>","<td style=\"text-align:left\">",); 
		$r=array("</td>",""); 

		$table=str_replace($t,$r,$html_string); 
		
		$rows=explode("\r\n",$table); 
		if (sizeof($rows) == 1)
			$rows=explode("\n",$table); 

//print_r($rows[0]);

		$r = 0;
		foreach($rows as $row)
		{ 
			$c = 0;
			$cells=explode("</td>",$row); 

			foreach($cells as $cell) { 
				if ($cell == "") continue;
				
				$tmp = explode("\"",$cell);
				if ($c == 1)
					$cell = $tmp[1];
				else
					$cell = (sizeof($tmp) >= 3) ? ($tmp[1].", ".str_replace("> ","",$tmp[2])) : trim(end($tmp));
				
				$cell = str_replace(array("static/forts/","static/icons/",".png"),array(""),$cell); 

				$content[$r][$c++] = $cell;
			}
			$r++;
		}
		
//print_r($content[0]);

		// Extract data
		$gym_list = array();

		foreach($content as $row)
		{
			$gym = array();
			
			$gym['Name'] = array_shift($row);
			$gym['Team'] = array_shift($row);
			$gym['Prestige'] = str_replace("Pr: ","",array_shift($row));
			
			// Players
			while (sizeof($row) > 2)
			{
				$mon = explode(", ",array_shift($row));
				$trainer = explode(" (",str_replace(")","",array_shift($row)));
				
				//if (isset($trainer[0]) && isset($trainer[1]) && isset($pokedex[$mon[0]]) && isset($mon[1]))
					$gym['Trainers'][] = array( "Trainer" => $trainer[0], "Trainer_Level" => $trainer[1], "Pokemon" => $pokedex[$mon[0]], "CP" => $mon[1] );
					
				if (substr($row[0],0,15) === "Letztes Update:")
					break;
			}
			
			$gym['LastUpdate'] = str_replace("Letztes Update: ","",array_shift($row));
			
			// (optional) Sector number
			if (sizeof($row) > 0)
				$gym['Sector'] = array_shift($row);
			
			$gym_list[] = $gym;
		}
		
		// Erase duplicates
		$gym_list = unique_multidim_array($gym_list,"Name");

//print_r($gym_list);
		
		$this->gyms = $gym_list;
	}

	function Parse_Pokedex_From_CSV_File($file_name)
	{
		$dataFile = fopen($file_name, "r");
		if (!$dataFile)
		{
			throw new Exception('Could not open file: ' . $file_name);
		}

		$html_string = "";
		while (!feof($dataFile)) 
		{
			$buffer = fgets($dataFile, 4096);
			$html_string = $html_string . $buffer;
		}
		fclose($dataFile);

		// Pre-Parse table into content(-array)
		$t=array("</TD>","<td>","</TR>","<tr>","</tr>","<tbody>","</tbody>","</table>","<table class=\"gympop\" cellspacing=\"0\" cellpadding=\"0\">","<table>","<td style=\"text-align:left\">",); 
		$r=array("</td>",""); 

		$table=str_replace($t,$r,$html_string); 
		$rows=explode("\r\n",$table); 

//print_r($rows[0]);

		$r = 1;
		$content = array();
		
		foreach($rows as $row)
		{ 
			$c = 0;
			$cells=explode(";",$row); 

			$content[$r++] = $cells[1];
		}

		return $content;
	}

	function Table_To_HTML($table,$title,$width,$sortable = false)
	{
		$s = "<p>";

		// Title
		if ($title != "")
			$s .= "<table width=" . $width . "><tr><td style=\"border:0px;\">" . $title . "</td></tr></table>\n";

		$s .= "<table width=" . $width;

		if ($sortable)
			$s .= " class=\"sortable\"";
		
		$s .= ">\n";

		foreach ($table as $key1 => $row)
		{
			$s .= " <tr>";
			
			foreach ($row as $key2 => $item)
			{
				if ($key1 == "Header")
				{
					if ($item == "")
						$s .= "<th style=\"border:0px;\">";
					else if ($item == "Team")
						$s .= "<th style=\"text-align:left;\">";
					else
						$s .= "<th>";
				}
				else
				{
					if ($item == "")
						$s .= "<td style=\"border:0px;\">";
					else if ($table['Header'][$key2] == "Team" || $table['Header'][$key2] == "Trainer" || $table['Header'][$key2] == "Gym")
						$s .= "<td style=\"text-align:left;\">";
					else if (strpos($item, '|') !== false)
					{
						$tmp = explode('|',$item);
						$s .= "<td id=\"" . $tmp[0] . "\">";
					}
					else
						$s .= "<td>";
				}

				if (strpos($item, '|') !== false)
				{
					$tmp = explode('|',$item);
					$s .= $tmp[1];
				}
				else
					$s .= $item;

				if ($key1 == "Header")
					$s .= "</th>";
				else
					$s .= "</td>";

			}

			$s .= " </tr>\n";
		}
		$s .= "</table></p>\n";
		
		return $s;
	}

	function Create_Overall_Gym_Statistics_Table()
	{
		$table = array();
		$title = '<b>Overall gym statistics</b>';
		
		// Build header row
		$header_row = "Team,#Gyms (%)";
		$table['Header'] = explode(",",$header_row);
		
		// Fill basic table content
		$index = 0;
		$teams = array( "Valor" => 0, "Mystic" => 0, "Instinct" => 0, "Uncontested" => 0, "Overall" => 0, "LVL10" => 0 );

		foreach ($this->gyms as $gym)
		{
			$teams[$gym['Team']]++;
			$teams['Overall']++;

			if (isset($gym['Trainers']) && sizeof($gym['Trainers']) == 10)
				$teams['LVL10']++;
		}
		
		$table['Valor'] = array( 'Valor', sprintf("%s (%.2f%%)",$teams['Valor'],$teams['Valor']*100/$teams['Overall']) );
		$table['Mystic'] = array( 'Mystic', sprintf("%s (%.2f%%)",$teams['Mystic'],$teams['Mystic']*100/$teams['Overall']) );
		$table['Instinct'] = array( 'Instinct', sprintf("%s (%.2f%%)",$teams['Instinct'],$teams['Instinct']*100/$teams['Overall']) );

		if ($teams['Uncontested'] > 0)
			$table['Uncontested'] = array( 'Uncontested', sprintf("%s (%.2f%%)",$teams['Uncontested'],$teams['Uncontested']*100/$teams['Overall']) );
		
		$table['Overall'] = array( '', $teams['Overall'] );
		$table['LVL10'] = array( 'Level 10', sprintf("%s (%.2f%%)",$teams['LVL10'],$teams['LVL10']*100/$teams['Overall']) );

		return $this->Table_To_HTML($table,$title,200);
	}

	function Create_Trainer_Statistics_Table()
	{
		$ret = "";
		
	error_reporting(E_ALL & ~E_NOTICE);
		//$lvl40_trainers = array();
		$trainers = array();
		
		// Create teams
		$trainers['Valor'] = array();
		$trainers['Mystic'] = array();
		$trainers['Instinct'] = array();
		
		foreach ($this->gyms as $gym)
		{
			if (!isset($gym['Trainers']))
				continue;
				
			foreach ($gym['Trainers'] as $key => $t)
			{
			/*
				if ($t['Trainer_Level'] == 40)
				{
					$lvl40_trainers{$t['Trainer']}['Team'] = $gym['Team'];

					$gym_info = array ( "Gym" => $gym['Name'], "Gym_Position" => ($key+1), "Pokemon" => $t['Pokemon'], "CP" => $t['CP'] );
					$lvl40_trainers{$t['Trainer']}[] = $gym_info;
				}
			*/
			
				//$trainer_key = sprintf("%s (%s)", $t['Trainer'], $t['Trainer_Level']);
				$trainer_key = $t['Trainer'];
				
				if (!isset($trainers[$gym['Team']][$trainer_key]))
					$trainers{$gym['Team']}{$trainer_key} = array( 'Count' => 0, 'Trainer_Level' => 0, 'MaxCP' => 0, 'AvgCP' => 0, 'MinCP' => 50000, );
			
				// #Gym appearances
				$trainers[$gym['Team']][$trainer_key]['Count']++;
				
				// Trainer Level
				$trainers[$gym['Team']][$trainer_key]['Trainer_Level'] = $t['Trainer_Level'];
				
				// MaxCP
				if ($t['CP'] > $trainers[$gym['Team']][$trainer_key]['MaxCP'])
					$trainers[$gym['Team']][$trainer_key]['MaxCP'] = $t['CP'];

				// AvgCP
				$trainers[$gym['Team']][$trainer_key]['AvgCP'] += $t['CP'];
				
				// MinCP
				if ($t['CP'] < $trainers[$gym['Team']][$trainer_key]['MinCP'])
					$trainers[$gym['Team']][$trainer_key]['MinCP'] = $t['CP'];

			}
		}

		// Sort Trainers
		arsort($trainers['Valor']);
		arsort($trainers['Mystic']);
		arsort($trainers['Instinct']);

		// Create tables (one for each team)
		$ret .= "<p><table><tr><th colspan=3>All Trainers</th></tr><tr>";
		foreach ($trainers as $team => $team_trainers)
		{
			//if ($team == "Uncontested") continue;
			
			$table = array();
			$title = "<b>" . $team . "</b>";
			
			// Build header row
			$header_row = "Trainer,Lvl,#Gyms,MaxCP,AvgCP,MinCP";
			$table['Header'] = explode(",",$header_row);
			
			// Fill basic table content
			$index = 0;

			foreach ($team_trainers as $trainer => $trainer_info)
			{
				$table[++$index] = array( 
					str_replace(" ","&nbsp;",$trainer), 
					$trainer_info['Trainer_Level'],
					$trainer_info['Count'],
					$trainer_info['MaxCP'], 
					sprintf("%.1f",$trainer_info['AvgCP']/$trainer_info['Count']), 
					$trainer_info['MinCP'],
				);
			}

			$ret .= "<td valign=\"top\" style=\"border:0px;\">";
			$ret .= $this->Table_To_HTML($table,$title,320,true);
			$ret .= "</td>";
		}

		$ret .= "</tr></table></p>";

		/*
		$trainers['Valor']['Overall'] = sizeof($trainers['Valor']);
		$trainers['Mystic']['Overall'] = sizeof($trainers['Mystic']);
		$trainers['Instinct']['Overall'] = sizeof($trainers['Instinct']);
		*/
		return $ret;
	}

	function Create_Pokemon_Statistics_Table($lvl10_only = true)
	{
		$ret = "";
	
		if ($lvl10_only == true)
			$ret .= "<h1><i>Level 10 gyms only:</i></h1>";
			

	error_reporting(E_ALL & ~E_NOTICE);

		// Pokemon statistics
		$highest_mons = array();
		$highest_cp = 0;
		$lowest_mons = array();
		$lowest_cp = 50000;
		$mons = array();

		foreach ($this->gyms as $gym)
		{
			if (!isset($gym['Trainers'])) continue;
			
			if ($lvl10_only && sizeof($gym['Trainers']) < 10) continue;
		
			foreach ($gym['Trainers'] as $key => $t)
			{
/*
				// Highest CP
				if ($t['CP'] > $highest_cp)
				{
					$highest_cp = $t['CP'];
					$highest_mons = array();
					$highest_mons[] = array ( "Trainer" => $t['Trainer'], "Trainer_Level" => $t['Trainer_Level'], "Gym" => $gym['Name'], "Gym_Position" => ($key+1), "Pokemon" => $t['Pokemon'], "CP" => $t['CP'] );
				}
				else if ($t['CP'] == $highest_cp)
					$highest_mons[] = array ( "Trainer" => $t['Trainer'], "Trainer_Level" => $t['Trainer_Level'], "Gym" => $gym['Name'], "Gym_Position" => ($key+1), "Pokemon" => $t['Pokemon'], "CP" => $t['CP'] );

				// Lowest CP
				if ($t['CP'] < $lowest_cp)
				{
					$lowest_cp = $t['CP'];
					$lowest_mons = array();
					$lowest_mons[] = array ( "Trainer" => $t['Trainer'], "Trainer_Level" => $t['Trainer_Level'], "Gym" => $gym['Name'], "Gym_Position" => ($key+1), "Pokemon" => $t['Pokemon'], "CP" => $t['CP'] );
				}
				else if ($t['CP'] == $lowest_cp)
					$lowest_mons[] = array ( "Trainer" => $t['Trainer'], "Trainer_Level" => $t['Trainer_Level'], "Gym" => $gym['Name'], "Gym_Position" => ($key+1), "Pokemon" => $t['Pokemon'], "CP" => $t['CP'] );
*/
				// Pokemon statistics
				//$mons['Overall']++;
				$mons{$t['Pokemon']}['Pokemon'] = $t['Pokemon'];
				$mons{$t['Pokemon']}['Count']++;
				$mons{$t['Pokemon']}['AvgCP'] = (($mons[$t['Pokemon']]['Count']-1) * $mons[$t['Pokemon']]['AvgCP'] + $t['CP']) / $mons[$t['Pokemon']]['Count'];

				// Pokemons per gym spot
				if (!isset($mons[$t['Pokemon']][$key]))
					$mons{$t['Pokemon']}{$key} = array();
					
				$mons{$t['Pokemon']}{$key}['Count']++;
				$mons{$t['Pokemon']}{$key}['AvgCP'] = (($mons[$t['Pokemon']][$key]['Count']-1) * $mons[$t['Pokemon']][$key]['AvgCP'] + $t['CP']) / $mons[$t['Pokemon']][$key]['Count'];
			}
		}
		
		// Create overall statistics table
		$table = array();
		$title = "<b>Pokemon - Overall statistics</b>";
			
		// Sort Pokemons
		usort($mons, 'countDescSort');

//print_r($mons);

		// Build header row
		$header_row = "Pokemon,#Gyms,AvgCP,,1,2,3,4,5,6,7,8,9,10";
		$table['Header'] = explode(",",$header_row);

		// Fill basic table content
		$index = 0;
		foreach ($mons as $mon)
		{
			$tmp = array();
			for ($i = 0; $i < 10; $i++)
				$tmp[$i] = ($mon[$i]['Count'] == 0) ? "" : sprintf("%d<br>%.1f",$mon[$i]['Count'],$mon[$i]['AvgCP']);
			
			$table[++$index] = array( 
				$mon['Pokemon'], 
				$mon['Count'],
				sprintf("%.1f",$mon['AvgCP']), 
				"",
				$tmp[0],$tmp[1],$tmp[2],$tmp[3],$tmp[4],$tmp[5],$tmp[6],$tmp[7],$tmp[8],$tmp[9], // Gym levels 1-10
			);
		}

		$ret .= $this->Table_To_HTML($table,$title,320);
		return $ret;
	}

	function Get_Gym_Names()
	{
		$gym_names = array();

		foreach ($this->gyms as $gym)
			$gym_names[] = $gym['Name'];
		
		sort($gym_names);
		
		return $gym_names;
	}

	function Get_Gym_Names_By_Sector()
	{
		$gym_names = array();

		foreach ($this->gyms as $gym)
		{
			if (isset($gym['Sector']))
				$gym_names{$gym['Sector']}[] = $gym['Name'];
			else
				$gym_names[] = $gym['Name'];
		}
		
		//sort($gym_names);
		
		return $gym_names;
	}

	function Get_Gym_List()
	{
		$gym_list = array();

		foreach ($this->gyms as $gym)
		{
			$gym_list{$gym['Name']}['Team'] = $gym['Team'];
			
			if (isset($gym['Trainers']))
				$gym_list{$gym['Name']}['Trainers'] = $gym['Trainers'];

			if (isset($gym['Sector']))
				$gym_list{$gym['Name']}['Sector'] = $gym['Sector'];
		}
		
		ksort($gym_list);
		
		return $gym_list;
	}

	function Calculate_Volatility_Index(&$gym_list_1, &$gym_list_2, $timestamp) // Note: Parameters passed by reference
	{
		foreach ($gym_list_2 as $gym_name => $gym)
		{
			if (isset($gym_list_1[$gym_name])) // Known gym
			{
				// Calculate volatility index
				$vix = 0;
				
				if ($gym_list_1[$gym_name]['Team'] == $gym['Team'] && isset($gym['Trainers']))
				{
					foreach ($gym['Trainers'] as $key => $t)
					{
						if (in_array_r($t['Trainer'], $gym_list_1[$gym_name]['Trainers']))
							$vix++;
					}
					
					$vix = round(10 * $vix / sizeof($gym['Trainers']), 1);
				}
				
				$vix = 10 - $vix; // 0 ... static, 10 ... highly volatile
			
				$gym_list_1{$gym_name}['VIX'][$timestamp] = array ('Team' => $gym_list_2[$gym_name]['Team'], 'VIX_Value' => $vix);
			}

			$gym_list_1{$gym_name}['Team'] = $gym['Team'];
			
			if (isset($gym['Sector']))
				$gym_list_1{$gym_name}['Sector'] = $gym['Sector'];

			if (isset($gym['Trainers']))
				$gym_list_1{$gym_name}['Trainers'] = $gym['Trainers'];
		}
		
		ksort($gym_list_1);
		
		//return $gym_list;
	}

	function Create_Volatility_Index_Table($gym_list,$timestamps)
	{
		$ret = "";
		$color = array ('Valor' => "red", 'Mystic' => "blue", 'Instinct' => "yellow", 'Uncontested' => "grey");
		
		// Skip first timestamp
		array_shift($timestamps);
	
//	error_reporting(E_ALL & ~E_NOTICE);

		// Create volatility index table
		$table = array();
		$title = "<b>Volatility Index (VIX)</b>";
			
		// Build header row
		$header_row = "Gym,Sector";
		foreach ($timestamps as $key => $timestamp)
		{
			$header_row .= "," . ($key+1+1);//str_replace(' ','<BR>',$timestamp);
		}
		$header_row .= ",Avg";
		$table['Header'] = explode(",",$header_row);

		// Fill basic table content
		$index = 1;
		foreach ($gym_list as $gym_name => $gym)
		{
			$table[$index][] = $gym_name;
			
			if (isset($gym['Sector']))
				$table[$index][] = $gym['Sector'];
			else
				$table[$index][] = "";

			$nr = $avg = 0;
			$j = 2;
			foreach ($timestamps as $timestamp)
			{
				$table[$index][$j] = ""; // Create every element
				
				if (isset($gym['VIX'][$timestamp]) && isset($gym['VIX'][$timestamp]['Team']) && isset($gym['VIX'][$timestamp]['VIX_Value']))
				{
//echo "'" . $gym['VIX'][$timestamp]['Team'] . "' ";
					$table[$index][$j] = $gym['VIX'][$timestamp]['Team'] . "|" . $gym['VIX'][$timestamp]['VIX_Value'];
					
					$avg += $gym['VIX'][$timestamp]['VIX_Value'];
					$nr++;
				}
				
				$j++;
			}
			
			if ($nr > 0)
				$table[$index][] = sprintf("%.1f",$avg/$nr);
			
			$index++;
		}
			
//print_r($table);


		$ret .= $this->Table_To_HTML($table,$title,800,true);
		return $ret;
	}


	function Calculate_Gym_History(&$gym_list_1, &$gym_list_2, $timestamp) // Note: Parameters passed by reference
	{
		foreach ($gym_list_2 as $gym_name => $gym)
		{
			if (isset($gym_list_1[$gym_name])) // Known gym
			{
				$lvl = (isset($gym_list_2[$gym_name]['Trainers'])) ? sizeof($gym_list_2[$gym_name]['Trainers']) : 0;
				$gym_list_1{$gym_name}['History'][$timestamp] = array ('Team' => $gym_list_2[$gym_name]['Team'], 'Level' => $lvl);
			}

			$gym_list_1{$gym_name}['Team'] = $gym['Team'];
			
			if (isset($gym['Sector']))
				$gym_list_1{$gym_name}['Sector'] = $gym['Sector'];

			if (isset($gym['Trainers']))
				$gym_list_1{$gym_name}['Trainers'] = $gym['Trainers'];
		}
		
		ksort($gym_list_1);
		
		//return $gym_list;
	}

	function Create_Gym_History_Table($gym_list,$timestamps)
	{
		$ret = "";
		$color = array ('Valor' => "red", 'Mystic' => "blue", 'Instinct' => "yellow", 'Uncontested' => "grey");
		
//	error_reporting(E_ALL & ~E_NOTICE);

		// Create gym history table
		$table = array();
		$title = "<b>Gym History</b>";
			
		// Build header row
		$header_row = "Gym,Sector";
		foreach ($timestamps as $key => $timestamp)
		{
			$header_row .= "," . ($key+1);//str_replace(' ','<BR>',$timestamp);
		}
		$table['Header'] = explode(",",$header_row);

		// Fill basic table content
		$index = 1;
		foreach ($gym_list as $gym_name => $gym)
		{
			$table[$index][] = $gym_name;
			
			if (isset($gym['Sector']))
				$table[$index][] = $gym['Sector'];
			else
				$table[$index][] = "";

			$j = 2;
			foreach ($timestamps as $timestamp)
			{
				$table[$index][$j] = ""; // Create every element
				
				if (isset($gym['History'][$timestamp]) && isset($gym['History'][$timestamp]['Team']) && isset($gym['History'][$timestamp]['Level']))
				{
//echo "'" . $gym['History'][$timestamp]['Team'] . "' ";
					$table[$index][$j] = $gym['History'][$timestamp]['Team'] . "|" . $gym['History'][$timestamp]['Level'];
				}
				
				$j++;
			}
			
			$index++;
		}
			
//print_r($table);

		$ret .= $this->Table_To_HTML($table,$title,800,true);
		return $ret;
	}

}

/*
function Array_To_DAT_String($name, $ar, $nokey1 = true, $nokey2 = true, $nokey3 = true, $nokey4 = true)
{
	$s = "<?php\n\n$" . $name . " = array (\n";
	foreach ($ar as $key1 => $value1)
	{
		if (is_array($value1))
		{
			if (!$nokey1)
				$s .= " \"" . $key1 . "\" =>";
			
			$s .= " array (";
			foreach ($value1 as $key2 => $value2)
			{
				if (is_array($value2))
				{
					if (!$nokey2)
						$s .= " \"" . $key2 . "\" =>";
					
					$s .= " array (";
					foreach ($value2 as $key3 => $value3)
					{
						if (is_array($value3))
						{
							if (!$nokey3)
								$s .= " \"" . $key3 . "\" =>";
							
							$s .= " array(";
							foreach ($value3 as $key4 => $value4)
							{
								if ($nokey4)
									$s .= "\"" . $value4 . "\",";
								else
									$s .= "\"" . $key4 . "\" => \"" . $value4 . "\",";
							}
							$s .= "),";
						}
						else if ($nokey3)
							$s .= "\"" . $value3 . "\",";
						else
							$s .= "\"" . $key3 . "\" => \"" . $value3 . "\",";
					}
					$s .= " ),";
				}
				else if ($nokey2)
					$s .= " \"" . $value2 . "\",";
				else
					$s .= " \"" . $key2 . "\" => \"" . $value2 . "\",";
			}
			$s .= " ),\n";
		}
		else if ($nokey1)
			$s .= " \"" . $value1 . "\",";
		else
			$s .= " \"" . $key1 . "\" => \"" . $value1 . "\",";
	}
	$s .= ");\n\n?>\n";
	
	return $s;
}

function Array_To_HTML_Table($ar)
{
	$s = "<table>\n";
	foreach ($ar as $key1 => $value1)
	{
		$s .= " <tr>";
		
		foreach ($value1 as $key2 => $value2)
		{
			if ($key1 == "Header")
				$s .= " <th>";
			else
				$s .= " <td>";

			$s .= $value2;

			if ($key1 == "Header")
				$s .= "</th>";
			else
				$s .= "</td>";

		}

		$s .= " </tr>\n";
	}
	$s .= "</table>\n";
	
	return $s;
}

function Invert_Array($ar1)
{
	$table = array();
	
	foreach ($ar1 as $key1 => $value1)
	{
		if (is_array($value1))
		{
			foreach ($value1 as $key2 => $value2)
				$table[$value2] = $key1;
		}
		else
			$table[$value1] = $key1;
	}
	
	return $table;
}

function Remove_Array_Keys($ar)
{
	$new_ar = array();

	foreach($ar as $value) { $new_ar[] = $value; }
	
	return $new_ar;
}
*/

?>