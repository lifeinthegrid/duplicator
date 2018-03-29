<?php
$API['BaseRootPath'] =  str_ireplace('dup-installer', '', dirname(__FILE__));
$API['BaseRootURL']  = '//' . $_SERVER['HTTP_HOST'] . str_ireplace('dup-installer', '', dirname($_SERVER['PHP_SELF']));

if (file_exists("{$API['BaseRootPath']}\installer.php")) 
{
	header( "Location: {$API['BaseRootURL']}/installer.php" ) ;
} 

echo "Please browse to the 'installer.php' from your web browser to proceed with your install!";

?>