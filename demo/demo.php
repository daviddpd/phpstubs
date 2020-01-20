<?php
header('Content-type: application/json');
include 'mysql.class.php';

/*

CREATE USER 'demo'@'%' IDENTIFIED BY 'abcd1234';
create database demo;
GRANT ALL PRIVILEGES ON  demo.* TO 'demo'@'%';
FLUSH PRIVILEGES;

CREATE TABLE `demo`.`tokens` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(32) NULL,
  `i` INT NULL,
  `v` VARCHAR(64) NULL,
  PRIMARY KEY (`id`),
  INDEX `token` (`token` ASC));

*/

$mysql_hostname = "localhost";
$mysql_username = "demo";
$mysql_dbname = "demo";
$mysql_password = "abcd1234";

if ( is_file ( "config.php" ) )
{
	include "config.php";
}
#
# This class forces SSL & 
# mysqli_escape_string all values automatically
#
$db = new mysql();
$db->host = $mysql_hostname;
$db->user = $mysql_username;
$db->passwd = $mysql_password;
$db->database = $mysql_dbname;
$db->port = 3306;
$db->open();

#echo json_encode($_POST);
#echo json_encode($_GET);

if ( isset($_POST['token']) && isset ($_POST['i']) ) {

	$rows = $db->get ("tokens", "token", $_POST['token'] );
#	echo json_encode($rows);
	if ( count ( $rows ) == 0 ) {	
		$data['token'] = $_POST['token'];
		if ( isset ( $_POST['i'] )  ) {
			if ( is_int ( $_POST['i'] )  ) {
				$data['i'] = $_POST['i'];	
			} else {
				$data['i'] = 0;			
			}
		}
		if ( isset ( $_POST['v'] )  ) {
			$data['v'] = $_POST['v'];
		}
		$db->add("tokens", $data);
	} else {
		if ( is_string($_POST['i']) && $_POST['i'] == "inc" ) {			
			$i = $rows[0]['i'] + 1;
			$data = array ();
			$data['i'] = $i;
			if ( isset ( $_POST['v'] )  ) {
				$data['v'] = $_POST['v'];
			}
			$db->update("tokens", "token", $_POST['token'], $data);			
		}
	}
}

if ( isset($_POST['token']) ) {
	$token = $_POST['token'];
}
if ( isset($_GET['token']) ) {
	$token = $_GET['token'];
}
if ( isset ( $token ) ) {
	$rows = $db->get ("tokens", "token", $token );
} else {
	$rows = array();
}
echo json_encode($rows);

?>
