<?php

/*
// Alkaline
// Copyright (c) 2010-2012 by Budin Ltd. Some rights reserved.
// http://www.alkalineapp.com/
*/

require_once('./../config.php');
require_once(PATH . CLASSES . 'alkaline.php');

$alkaline = new Alkaline;
$user = new User;

$user->perm(true, 'themes');

// Load current themes
$themes = $alkaline->getTable('themes');

$theme_ids = array();
$theme_uids = array();
$theme_builds = array();
$theme_folders = array();

foreach($themes as $theme){
	$theme_ids[] = $theme['theme_id'];
	$theme_uids[] = $theme['theme_uid'];
	$theme_builds[] = $theme['theme_build'];
	$theme_folders[] = $theme['theme_folder'];
}

// Seek all themes
$seek_themes = $alkaline->seekDirectory(PATH . THEMES, '');

$theme_deleted = array();

// Determine which themes have been removed, delete rows from table
foreach($themes as $theme){
	$theme_folder = PATH . THEMES . $theme['theme_folder'];
	if(!in_array($theme_folder, $seek_themes)){
		$theme_deleted[] = $theme['theme_id'];
	}
}

$alkaline->deleteRow('themes', $theme_deleted);

// Determine which themes are new, install them
$themes_installed = array();
$themes_updated = array();

foreach($seek_themes as &$theme_folder){
	$theme_folder = $alkaline->getFilename($theme_folder);
	if(!in_array($theme_folder, $theme_folders)){
		$data = file_get_contents(PATH . THEMES . $theme_folder . '/theme.xml');
		if(empty($data)){ $alkaline->addNote('Alkaline could not install a new theme. Its XML file is missing or corrupted.', 'error'); continue; }
		
		$xml = new SimpleXMLElement($data);
		
		$fields = array('theme_uid' => $xml->uid,
			'theme_title' => $xml->title,
			'theme_folder' => $theme_folder,
			'theme_build' => $xml->build,
			'theme_version' => $xml->version,
			'theme_creator_name' => $xml->creator->name,
			'theme_creator_uri' => $xml->creator->uri);
		
		$theme_intalled_id = $alkaline->addRow($fields, 'themes');
		$themes_installed[] = $theme_intalled_id;
	}
	else{
		$data = file_get_contents(PATH . THEMES . $theme_folder . '/theme.xml');
		$xml = new SimpleXMLElement($data);
		$keys = array_keys($theme_uids, $xml->uid);
		foreach($keys as $key){
			if($xml->build != $theme_builds[$key]){
				$id = $theme_ids[$key];
		
				$fields = array('theme_title' => $xml->title,
					'theme_folder' => $theme_folder,
					'theme_build' => $xml->build,
					'theme_version' => $xml->version,
					'theme_creator_name' => $xml->creator->name,
					'theme_creator_uri' => $xml->creator->uri);
				$alkaline->updateRow($fields, 'themes', $id);
				$themes_updated[] = $id;
			}
		}
	}
}

$themes_installed_count = count($themes_installed);
if($themes_installed_count > 0){
	if($themes_installed_count == 1){
		$notification = 'You have successfully installed 1 theme.';
	}
	else{
		$notification = 'You have successfully installed ' . $themes_installed_count . ' themes.';
	}
	
	$alkaline->addNote($notification, 'success');
	
	$themes = $alkaline->getTable('themes');
}

$themes_updated_count = count($themes_updated);
if($themes_updated_count > 0){
	if($themes_updated_count == 1){
		$notification = 'You have successfully updated 1 theme.';
	}
	else{
		$notification = 'You have successfully updated ' . $themes_updated_count . ' themes.';
	}
	
	$alkaline->addNote($notification, 'success');
	
	$themes = $alkaline->getTable('themes');
}

// Check for updates
$latest_themes = @$alkaline->boomerang('latest-themes');
if(!empty($latest_themes)){
	foreach($latest_themes as $latest_theme){
		foreach($themes as $theme){
			if($theme['theme_uid'] == $latest_theme['theme_uid']){
				if($latest_theme['theme_build'] > $theme['theme_build']){
					$fields = array('theme_build_latest' => $latest_theme['theme_build'],
						'theme_version_latest' => $latest_theme['theme_version']);
					$alkaline->updateRow($fields, 'themes', $theme['theme_id']);
				}
				else{
					if(!empty($theme['theme_build_latest']) or !empty($theme['theme_version_latest'])){
						$fields = array('theme_build_latest' => '',
							'theme_version_latest' => '');
						$alkaline->updateRow($fields, 'themes', $theme['theme_id']);
					}
				}
			}
		}
	}
}

$themes = $alkaline->getTable('themes');
$theme_count = @count($themes);

define('TAB', 'settings');
define('TITLE', 'Alkaline Themes');
require_once(PATH . ADMIN . 'includes/header.php');

?>

<div class="actions"><a href="<?php echo BASE . ADMIN . 'configuration' . URL_CAP; ?>"><button>Change theme</button></a></div>

<h1><img src="<?php echo BASE . ADMIN; ?>images/icons/themes.png" alt="" /> Themes (<?php echo $theme_count; ?>)</h1>

<p>Themes change the look and feel of your Alkaline library. You can browse and download additional themes at the <a href="http://www.alkalineapp.com/users/">Alkaline Lounge</a>.</p>

<p>
	<input type="search" name="filter" placeholder="Filter" class="s" results="0" />
</p>

<table class="filter">
	<tr>
		<th>Theme</th>
		<th class="center">Preview</th>
		<th class="center">Version</th>
		<th class="center">Update</th>
	</tr>
	<?php

	foreach($themes as $theme){
		echo '<tr class="ro">';
		echo '<td><strong class="large">' . $theme['theme_title'] . '</strong>';
		
		if(!empty($theme['theme_creator_name'])){
			echo ' \ ';
			if(!empty($theme['theme_creator_uri'])){
				echo '<a href="' . $theme['theme_creator_uri'] . '" class="nu">' . $theme['theme_creator_name'] . '</a>';
			}
			else{
				echo $theme['theme_creator_name'];
			}
		}
		
		echo '</td>';
		echo '<td class="center"><a href="' . BASE . '?theme=' . $theme['theme_folder'] . '">Preview</a></td>';
		echo '<td class="center">' . $theme['theme_version'] . ' <span class="small">(' . $theme['theme_build'] . ')</span></td>';
		if(!empty($theme['theme_build_latest'])){
			echo '<td class="center"><a href="http://www.alkalineapp.com/users/themes/">Download</a>';
			if(!empty($theme['theme_version_latest'])){
				echo ' (v' . $theme['theme_version_latest'] .')';
			}
			echo '</td>';
		}
		else{
			echo '<td class="center quiet">&#8212;</td>';
		}
		echo '</tr>';
	}

	?>
</table>

<?php

require_once(PATH . ADMIN . 'includes/footer.php');

?>