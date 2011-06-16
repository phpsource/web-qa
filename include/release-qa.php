<?php /* $Id$ */

/*
What this file does:
	- Generates the download links found at qa.php.net
	- Determines which test results are emailed to news.php.net/php.qa.reports
	- Defines $QA_RELEASES for internal and external (api.php) use, contains all qa related information for future PHP releases

Documentation:
	$QA_RELEASES documentation:
		Configuration:
		- Key is future PHP version number
			- Example: If 5.3.6 is the latest stable release, then use 5.3.7 because 5.3.7-dev is our qa version
			- Typically, this is the only part needing changed
		- active (bool): 
			- It's active and being tested here 
			- Meaning, the version will be reported to the qa.reports list, and be linked at qa.php.net
			- File extensions .tar.gz and .tar.bz2 are assumed to be available
		- snap (array):
			- Define the array to link at qa.php.net, otherwise array() to not list it
			- prefix: prefix of the filename, minus the .tar.gz/bz2 extensions
			- baseurl: base url of snaps server
			- We define a prefix because our snapshot filenames are not consistent with version (e.g., php-trunk)
			- File extensions .tar.gz and .tar.bz2 are assumed to be available
		- rc (array):
			- version: 0 if no RC, otherwise an integer of the RC number
			- md5_bz2: md5 checksum of this rcs .tar.bz2 file
			- md5_gz:  md5 checksum of this rcs .tar.gz file
			- date: date of RC release e.g., 21 May 2011
			- baseurl: base url of where these rc downloads are located
		Other variables within $QA_RELEASES are later defined including:
			- reported: versions that make it to the qa.reports mailing list
			- rcs: all current rcs, including paths to dl urls (w/ md5 info)
			- snaps: all current snaps, including paths to dl urls
			- dev_version: dev version
			- $QA_RELEASES is made available at qa.php.net/api.php

TODO:
	- Save all reports (on qa server) for all tests, categorize by PHP version (see buildtest-process.php)
	- Consider storing rc downloads at one location, independent of release master
	- Consider not linking to snaps if rcs exist
	- Determine best way to handle snap/rc baseurl, currently assumes .tar.gz/tar.bz2 will exist
	- Determine if $QA_RELEASES is compatible with all current, and most future configurations
	- Determine if $QA_RELEASES can be simplified
	- Determine if alpha/beta options are desired
	- Unify then create defaults for most settings
	- Add option to allow current releases (e.g., retrieve current release info via daily cron, cache, check, configure ~ALLOW_CURRENT_RELEASES)
*/

$QA_RELEASES = array(
	
	'5.3.7' => array(
		'active'		=> true,
		'snaps'			=> array(
			'prefix'	=> 'php5.3-latest',
			'baseurl'	=> 'http://snaps.php.net/',
		),
		'rc'			=> array(
			'number'	=> 1,
			'md5_bz2'	=> '295a457505049cc75d723560715be5d6',
			'md5_gz'	=> '4fd555292ea0a1bc3acd1d3ad4c98c27',
			'date'		=> '16 June 2011',
			'baseurl'	=> 'http://downloads.php.net/johannes/',
		),
	),

	'5.4.0' => array(
		'active'		=> true,
		'snaps'			=> array(
			'prefix'	=> 'php5.4-latest',
			'baseurl'	=> 'http://snaps.php.net/',
		),
		'rc'			=> array(
			'number'	=> 0,
			'md5_bz2'	=> '',
			'md5_gz'	=> '',
			'date'		=> '',
			'baseurl'	=> 'http://downloads.php.net/gandhi/',
		),
	),
	
	'trunk' => array(
		'active'		=> false,
		'snaps'			=> array(
			'prefix'	=> 'php-trunk-latest',
			'baseurl'	=> 'http://snaps.php.net/',
		),
	),
);
/*** End Configuration *******************************************************************/

// $QA_RELEASES eventually contains just about everything, also for external use
// rc       : These are encouraged for use (e.g., linked at qa.php.net)
// reported : These are allowed to report @ the php.qa.reports mailing list
// snap     : Snapshots that are being monitored by the QA team

foreach ($QA_RELEASES as $pversion => $info) {

	if (isset($info['active']) && $info['active']) {
	
		// Allow -dev versions of all active types
		// Example: 5.3.6-dev
		$QA_RELEASES['reported'][] = "{$pversion}-dev";
		$QA_RELEASES[$pversion]['dev_version'] = "{$pversion}-dev";

		// Allowed snaps, unless 'snaps' => array() (empty)
		if (!empty($info['snaps'])) {
			$QA_RELEASES[$pversion]['snaps']['files']['bz2']['path'] = $info['snaps']['baseurl'] . $info['snaps']['prefix'] . '.tar.bz2';
			$QA_RELEASES[$pversion]['snaps']['files']['gz']['path']  = $info['snaps']['baseurl'] . $info['snaps']['prefix'] . '.tar.gz';
		}
		
		// Allow -dev version of upcoming rcs
		// @todo confirm this php version format for RC of all dev versions
		if ((int)$info['rc']['number'] > 0) {
			$QA_RELEASES['reported'][] = "{$pversion}RC{$info['rc']['number']}-dev";
			if (!empty($info['rc']['baseurl'])) {
				
				// php.net filename format for RC releases
				// example: php-5.3.0RC2
				$fn_base = 'php-' . $pversion . 'RC' . $info['rc']['number'];
				
				$QA_RELEASES[$pversion]['rc']['files']['bz2']['path']= $info['rc']['baseurl'] . $fn_base . '.tar.bz2'; 
				$QA_RELEASES[$pversion]['rc']['files']['bz2']['md5'] = $info['rc']['md5_bz2'];
				$QA_RELEASES[$pversion]['rc']['files']['gz']['path'] = $info['rc']['baseurl'] . $fn_base . '.tar.gz';
				$QA_RELEASES[$pversion]['rc']['files']['gz']['md5']  = $info['rc']['md5_gz'];
			}
		} else {
			$QA_RELEASES[$pversion]['rc']['enabled'] = false;
		}
	}
}

// Sorted information for later use
// @todo need these?
// $QA_RELEASES['rcs']   : All current versions with active rcs
// $QA_RELEASES['snaps'] : All current versions with active snaps
foreach ($QA_RELEASES as $pversion => $info) {
	
	if (isset($info['active']) && $info['active']) {

		if (!empty($info['rc']['number'])) {
			$QA_RELEASES['rcs'][$pversion] = $info['rc'];
		}
	
		if (!empty($info['snaps'])) {
			$QA_RELEASES['snaps'][$pversion] = $info['snaps'];
		}
	}
}

/* Content */
function show_release_qa($QA_RELEASES) {
	
	echo "<!-- RELEASE QA -->\n";
	
	if (!empty($QA_RELEASES['rcs'])) {
		
		$plural = count($QA_RELEASES['rcs']) > 1 ? 's' : '';
		
		// RC Releases
		echo "<span class='lihack'>\n";
		echo "Providing QA for the following <a href='http://qa.php.net/rc.php'>release candidate{$plural}</a>:\n";
		echo "<ul>\n";

		// @todo check for vars, like if md5_* are set
		foreach ($QA_RELEASES['rcs'] as $pversion => $info) {

			// pure madness
			echo "<li>$pversion : [<a href='{$info['files']['bz2']['path']}'>tar.bz2</a>] (md5 checksum: {$info['files']['bz2']['md5']})</li>\n";
			echo "<li>$pversion : [<a href='{$info['files']['gz']['path']}'>tar.gz</a>] (md5 checksum: {$info['files']['gz']['md5']})</li>\n";
		}
		
		echo "</ul>\n</span>\n";
	}
	
	if (!empty($QA_RELEASES['snaps'])) {
	
		$plural = count($QA_RELEASES['snaps']) > 1 ? 's' : '';
		
		// Snap for dev releases
		echo "Providing QA for the following snapshot{$plural} of future PHP versions:\n";
		echo "<span class='lihack'>\n";
		echo "<ul>\n";

		// @todo check for vars, like if md5_* are set
		foreach ($QA_RELEASES['snaps'] as $pversion => $info) {
			
			// more madness
			echo "<li>$pversion : ";
			echo "[<a href='{$info['files']['bz2']['path']}'>tar.bz2</a>] or ";
			echo "[<a href='{$info['files']['gz']['path']}'>tar.gz</a>]</li>\n";
		}
		
		echo "</ul>\n</span>\n";
	}

	echo "<!-- END -->\n";
}
