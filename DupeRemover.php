#!/usr/bin/php
<?php

#	What: Duplicate file Checker
#	Why: for fun
#	Who: Frantzcy@Paisible.com
#	When: noted in git
#	Where: Spare time
#	
#  Yet to come
#	more comments
#	--html
#	--htmlout $filename


$listMd5 = array();
$listCreatedDirs = array();
$listSizes = array();

$configs = array(
	"move" => 0,
	"dir" => getcwd(),
	"dir_dupe" => "/tmp/_dupe",
	"create_dir_dupe" => 0,
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
			if(($parms[$xParm+1]+0) >= 1){
				$configs['debug'] = $parms[++$xParm]+0;
			}else{
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
			
		case "--dir_dupe":
			$configs['dir_dupe'] = $parms[++$xParm];
		break;
		
		case "--create_dir_dupe":
			$configs['create_dir_dupe'] = 1;
		break;
		
		case "--list":
			$configs['list'] = 1;
		break;

		case "--list_same":
			$configs['list_same'] = 1;
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

if(!$configs['copy'] && !$configs['move'] && !$configs['list'] && !$configs['list_all'] && !$configs['list_same']){
	print "No options, what is it you want !!?!!\n";
	exit;
}


if($configs['debug']){
	$configs['copy'] = 0;
	$configs['delete'] = 0;
	$configs['list_all'] = 0;
	$configs['list'] = 0;
	$configs['create_dir_dupe'] = 0;
	$configs['move'] = 0;

	print_r($configs);
}






moveDupe(getcwd(), "");

if($configs['list_same']){
	print_r($listSizes);
	foreach($listSizes as $size => $listFileNames){
		if(count($listFileNames) > 1){
			printf("files of this size %s\n",$size);
			foreach($listFileNames as $fileName){
				printf("%s %s\n",md5_file($fileName),$fileName);
			}
		}
	}
}


exit(0);


#-----------------------------------------------------------------------------
function moveDupe($dir)
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
				if($file == ".git" ) continue;

				$fileName = "$dir/$file";
				if(!is_readable($fileName))	continue;
				
				if (is_dir($fileName)) {
					debugTrace("---- DirName [$fileName]",35);
					moveDupe($dirName);
				}else{
					debugTrace("---- FileName[$fileName]",35);
					if($configs['list_all']){
						isdupe($fileName);
					}else{
						$fileSize = filesize($fileName);
						if(isset($listSizes[$fileSize])) {
							debugTrace("---- push($fileSize)",35);
							array_push($listSizes[$fileSize],$fileName);
						}else{
							debugTrace("---- new($fileSize)",35);
							$listSizes[$fileSize] = array($fileName);
						}
						isdupe($fileName);
					}
				}
			}
		}
	}
	debugTrace("Closing dir : $dir",30);
}


function isdupe ($fileName)
{
    GLOBAL $listMd5, $configs, $listCreatedDirs, $listSizes;

	$soIsIt = 0;
	$m = md5_file($fileName);
	if (isset($listMd5[$m])) {
		$listMd5[$m]++;
		$dir = getDirFromFileName($fileName);
		
		if(!isset($listCreatedDirs[$dir]) && ($configs['move'] || $configs['copy'])){
			CreateDirectory($configs['dir_dupe'],$dir);
		}

		if (($configs['move'] || $configs['copy']) && copy("$fileName","$configs[dir_dupe]/$fileName")) {
			if($configs['move']){
				unlink("$fileName");
			}
		}

		if($configs['delete']){
			unlink("$fileName");
		}

		if($configs['list'] || $configs['list_all']){
			echo "$m $fileName dupe\n";
			$soIsIt = 1;
		}
	} else {
		$listMd5[$m] = 1;
		if($configs['list_all']){
			echo "$m $fileName \n";
		}
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
