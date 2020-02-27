<?php
function parseArgs() {

	$longopts  = array(
		"file:",
		"key:",
		"debug",
	);

	$options = getopt("f:k:d", $longopts);
	return $options;

}


$opt = parseArgs();
print_r ( $opt );


?>