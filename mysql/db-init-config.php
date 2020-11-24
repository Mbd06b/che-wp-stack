
<?php

/**
 * Configuration for database connection
 *
 */

$arrayTable = psFile_kv("../website/bedrock/.env");


$host       = $arrayTable["DB_HOST"];;
$username   = $arrayTable["DB_USER"];;
$password   = $arrayTable["DB_PASSWORD"];;
$dbname     = $arrayTable["DB_NAME"];
$workspaceUrl = $arrayTable["WP_HOME"];

$dsn        = "mysql:host=$host;dbname=$dbname";
$options    = array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
              );

function psFile_kv($wFile,$d = "=") { return parseFileKeyVal($wFile,$d); }

// function credit: http://www.phpsalt.com/lib/file/psFileKeyValL.html
function parseFileKeyVal($wFile,$d = "=")
{
	$ary = @file($wFile);
	if ( is_array($ary) == true )
	  {
	  foreach ($ary as $line)
	    {
	    $line = trim($line);
	    if ( ($line !="") && (substr($line,0,1) != "#") )
	      {
	      list($key,$val) = explode($d,$line,2);
	      $key = trim($key); $val = trim($val);
	      $res[$key] = $val;
	      }
	    }
	  }
	return $res;
}