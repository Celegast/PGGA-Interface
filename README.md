# Pokemon Go - Gym Analysis Interface (PHP)
_Pokemon Go - Gym Analysis (PGGA)_ is a tool to collect and analyze data of gyms and trainers in _Pokemon Go_.
The PGGA-Interface transforms the raw data files (input) into a report (HTML output).

## User manual
### index.php
Responsible for menu handling and output display.
#### GET parameters
* **mid**: Attached to the selection of the left menu bar. Either specifies a gym data file (e.g. _data/g_20170713_155143.txt_), _History_ for the Gym History or _VIX_ for the Volatility Index. Default value: Latest gym data file.
* **gym**: Gym name. History of the specified gym.
* **trainer**: Trainer name. History of the specified trainer.
* **data**: Attached to the selection of the top horizontal menu bar (defined by $menu_str). Can be used to change between data sets (= sub-folders in folder _data_). Default value: data.
#### Gym data file analysis
- **Overall gym statistics**
- **All Trainers**: List of trainers for each team, sorted by number of gym appearances (#Gyms).
- **Level X gyms only**: Overview of all Pokemon in full gyms, with X being either 6 (new) or 10 (old). You can lift this restriction by changing the $max_lvl_only parameter to false (see _$pgga->Create_Pokemon_Statistics_Table(true);_).
#### History
#### Volatility Index (VIX)
An indicative value about the behavior of a gym. It ranges from 0 (static) to 10 (highly volatile).
Formula: $vix = 10 - 10 * (# of matching trainers in previous sample) / (# current trainers)

### pgga.php
Contains all PGGA related classes and functions.
#### Parser
Transforms raw gym data into a PHP array structure:
...
Array
(
    [0] => Array
        (
            [Name] => Gym_Name
            [Team] => Valor/Mystic/Instinct
            [FreeSpots] => (optional)
            [Prestige] => (optional)
            [Trainers] => Array
                (
                    [0] => Array
                        (
                            [Trainer] => Trainer_Name
                            [Trainer_Level] => Trainer_Level
                            [Pokemon] => Pokemon_Name
                            [CP] => Pokemon_CP
                            [AssignedSince] => (optional)
                        )
					..
                )

            [LastUpdate] =>  LastUpdate_String
            [Sector] => Sector_Number (optional)
            [Raid] => Raid_Information (optional)
        )
	..
}
...
The array gets stored in the public class variable _$gyms_.
#### 'Create table' functions
These functions perform calculations on the gym data, prepare report tables and return HTML code.
PHP array structure ($table) for _Table_To_HTML($table,$title,$width,$sortable = false)_:
...
Array
(
    [Header] => Array
        (
            [0] => Column_1_Title
            [1] => Column_2_Title
			..
        )

    [1] => Array
        (
            [0] => Cell_1_1
            [1] => Cell_2_1
			..
        )
}
...
Note: By putting a string in the format _(CSS_id)|_ in front of a cell content string you can set the cells' id selector. See _pgga.php_ for examples.

## Installation guide
* Upload the files to a server that supports PHP or use a local server (e.g. [XAMPP](https://www.apachefriends.org/index.html)).
* Place (new) gym data files in folder _/data_.

### XAMPP settings
In order to run PGGA on your local machine you need to add a few lines to the Apache configuration file.
- Run XAMPP and open **Apache httpd.conf**.
- In Section _<IfModule alias_module>_ add the following:
...
Alias /PGGA "E:\PGGA"
<Directory "E:\PGGA">
    DirectoryIndex index.php
    Allow from all
    Require all granted
</Directory>
...
Note: Replace _E:\PGGA_ with your PGGA directory.
- Save _httpd.conf_ and start the Apache module.
- Open a browser and enter "localhost/PGGA/index.php" as URL.