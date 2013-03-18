#!/usr/bin/php
<?php
#ini_set('memory_limit','512M');
$listMd5 = array();
$listCreatedDirs = array();
$listSizes = array();

$configs = array(
	"move" => 0,
	"dir" => getcwd(),
	"dir_doublons" => "/tmp/_doublons",
	"create_dir_doublons" => 0,
	"list" => 0,
	"copy" => 0,
	"debug" => 0,
);

$parms = $argv;
$xParm = 0;
while($xParm++ < count($parms)){
	$parm = $parms[$xParm];
	switch($parm){
		case "--move":
			$configs['move'] = 1;
		break;
		
		case "--debug":
			$configs['debug'] = $parms[++$xParm]+0;
			if(!$configs['debug']){
				$configs['debug'] = 1;
			}
		break;

		case "--memory":
			$configs['memory'] = $parms[++$xParm]+0;
			if(!$configs['memory']){
				$configs['memory'] = 128;
			}
			
			ini_set('memory_limit',$configs[memory].'M');
			
		break;

		
		case "--dir":
			$configs['dir'] = $parms[++$xParm];
			
		case "--dir_doublons":
			$configs['dir_doublons'] = $parms[++$xParm];
		break;
		
		case "--create_dir_doublons":
			$configs['create_dir_doublons'] = 1;
		break;
		
		case "--list":
			$configs['list'] = 1;
		break;

		case "--list_all":
			$configs['list_all'] = 1;
		break;

		case "--copy":
			$configs['copy'] = 1;
		break;

		case "--delete":
			$configs['delete'] = 1;
		break;
	}
}

if(!$configs['copy'] && !$configs['move'] && !$configs['list'] && !$configs['list_all']){
	print "No options, what is it you want !!?!!\n";
	exit;
}


if($configs['debug']){
	print_r($configs);
}


#exit;


moveDoublons(getcwd(), "");
#-----------------------------------------------------------------------------
function moveDoublons($dir)
{
    GLOBAL $listMd5, $configs, $listCreatedDirs, $listSizes;
	$listFiles = array();
	$listDirs = array();

	debugTrace("Openning dir : $dir",30);
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if($file == "." ) continue;
				if($file == ".." ) continue;
				if($file == ".Trashes" ) continue;
				if($file == ".svn" ) continue;

				$fileName = "$dir/$file";
				if(!is_readable($fileName))	continue;
				
				if (is_dir($fileName)) {
					debugTrace("---- DirName [$fileName]",35);
					array_push($listDirs, $fileName);
				}else{
					debugTrace("---- FileName[$fileName]",35);
					array_push($listFiles, $fileName);
				}
			}
		}

		debugTrace("Doing Files",30);
		rsort($listFiles);
		foreach ($listFiles as $fileName) {
			debugTrace("---- Doing [$fileName]",40);
			if(!is_readable($fileName)) continue;
			
			if($configs['list_all']){
				isDoublon($fileName);
			}else{
				$fileSize = filesize($fileName);
				if(isset($listSizes[$fileSize])) {
					if($listSizes[$fileSize] != "-DONE-"){
						isDoublon($listSizes[$fileSize]);
						$listSizes[$fileSize] = "-DONE-";
					}
					isDoublon($fileName);
				}else{
					$listSizes[$fileSize] = $fileName;
				}
			}
        }
		unset($listFiles);

		debugTrace("Doing Directories",30);
		rsort($listDirs);
		foreach ($listDirs as $dirName) {
			moveDoublons($dirName);
		}

	}
	debugTrace("Closing dir : $dir",30);
}


function isDoublon ($fileName)
{
    GLOBAL $listMd5, $configs, $listCreatedDirs, $listSizes;

	$soIsIt = 0;
	$m = md5_file($fileName);
	if (isset($listMd5[$m])) {
		$listMd5[$m]++;
		$dir = getDirFromFileName($fileName);
		if(!isset($listCreatedDirs[$dir])){
			CreateDirectory($configs['dir_doublons'],$dir);
		}

		if (($configs['move'] || $configs['copy']) && copy("$fileName","$configs[dir_doublons]/$fileName")) {
			if($configs['move']){
				unlink("$fileName");
			}
		}

		if($configs['delete']){
			unlink("$fileName");
		}


		if($configs['list'] || $configs['list_all']){
			echo "$m $fileName doublon\n";
			$soIsIt = 1;
		}
	} else {
		$listMd5[$m] = 1;
		if($configs['list_all']){
			echo "$m $fileName \n";
		}
		#echo "$m $file\n";
	}
	return $soIsIt;
}


function CreateDirectory($base,$dirToCreate)
{
	if($base == "" ) return;
	if($base == "/" ) return;
	if(!is_writable($base)){
		print "Base directory not writable [$base] !!!\n";
		exit;
	};
	
	if(!file_exists("$base/$dirToCreate")){
		debugTrace("Creating [$base/$dirToCreate]",20);
		mkdir("$base/$dirToCreate",0755,true);
	}
}

function getDirFromFileName($fileName)
{
	return dirname($fileName);
	
}


function debugTrace($msg,$mindebug)
{
	GLOBAL $configs;
	
	if($configs['debug'] >= $mindebug){
		print $msg . "\n";
	}
}
