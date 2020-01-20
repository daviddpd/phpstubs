<?php

// MySQL Storage Module
// $Id: mysql.class.php,v 1.3 2003/10/29 01:31:40 dpd Exp $

// MySQL Class/Object for easy querying of databases.
/*

Leaving in the old CVS ID string ... apparently at least started
this back in 2003 or before. 

A framework or PDO might be better, but I keep coming back to this,
becaus its simple, and low overhead, just needs mysqli.

*/

class mysql 
{

	
	var $link;		// the resource link_identifier using in mysqli_* php functions
	var $host;		// host:port / localhost:socket
	var $user;		// user name
	var $passwd;	// user's password
	var $database;	// which database to access


	// Constructor
	// 
	// Default all null, except host, default path to Linux socket.
	
	function __construct(){

		$this->link = mysqli_init();
		$this->host = "localhost:/var/lib/mysql/mysql.sock";
		$this->user = NULL;
		$this->passwd = NULL;
		$this->database = NULL;
		$this->sql = NULL;
		$this->cacert = "/etc/ssl/certs/ca-bundle.crt";
		$this->port = 3306;
	}
	
	
	// Open the link to the database
	
	function open() {
		mysqli_ssl_set( $this->link, NULL, NULL, $this->cacert, NULL, NULL);
		mysqli_options ($this->link, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, 1 );
		mysqli_options($this->link, MYSQLI_OPT_CONNECT_TIMEOUT, 1);
//		mysqli_options ($this->link, MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8' );		
//		mysqli_set_charset($this->link, "utf8");
		$ret = mysqli_real_connect ($this->link, $this->host, $this->user, $this->passwd);
		if ( !$ret ) { return(0); }
		mysqli_select_db($this->link, $this->database);
		return(1);
	}
	
	// close the link to the database.
	
	function close() {
		mysqli_close($this->link);
	}

	function select($sql) {

		$this->sql = $sql;
		$result = mysqli_query($this->link, $sql);
        $all_rows = array();

	    if (!$result) {
        	echo "Could not successfully run query ($sql) from DB: " . mysqli_error($this->link);
    	    return FALSE;
	    }
    
	    if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_assoc($result)) {				
    	        $all_rows[] = $row;
        	}
	
		}
		mysqli_free_result($result);	

		return $all_rows;
	
	}

	
	//  get(TABLE_NAME, [ COLUMN_NAME  [ VALUE  [ SORTBY [ LIMIT ] ] ] ]
	//  Get rows from a table
	//
	//		$table 	= Which table to querty from $this->database
	//		$q, $v	= Select the rows where the Column Name $q equals Value $v.
	//		$sortby = which column name to sort by
	//		$limit  = int/number - how many rows to get.
	//
	//	Returns a numerically index'ed array of associative arrays
	//
	
	function get($table, $q = NULL , $v = NULL, $sortby = NULL, $limit = NULL, $order = 'ASC', $offset = NULL, $count=NULL) {

		// SELECT * FROM table [ ORBER BY ]
		// SELECT * FROM table WHERE $x=$y [ ORBER BY ]

		$v = mysqli_escape_string ($this->link, $v);
	
		if ( $count != NULL ) {
			$sql_query = "SELECT count(*) as c FROM $this->database.$table  ";
		} else {
			$sql_query = "SELECT * FROM $this->database.$table  ";
		}
		
		if ( ($q != NULL) && ($v != NULL ) ) {
			$sql_query = $sql_query . " WHERE `$q`='$v'";
		}

		
		if ( $sortby != NULL ) {
			$sql_query = $sql_query . " ORDER BY `$sortby` $order";
		}

		if ( $limit != NULL ) {
			if ( $offset != NULL ) {
				$sql_query = $sql_query . " LIMIT $offset,$limit ";
			} else {
				$sql_query = $sql_query . " LIMIT $limit ";
			}
		}
		
		$this->sql = $sql_query;
		$result = mysqli_query($this->link, $sql_query);
        $all_rows = array();

	    if (!$result) {
        	echo "Could not successfully run query ($sql) from DB: " . mysqli_error($this->link);
    	    return FALSE;
	    }
    

	    if (mysqli_num_rows($result) > 0) {
			$create_keys = 0;
		
			# Get One Row at a time
			while ($row = mysqli_fetch_assoc($result)) {

				
				$keys_array = array();
				
				foreach ( $row as $key => $val ) 
				{
            	    $row[$key] =  stripslashes($val) ; 
					if ($create_keys == 1) { $keys_array[] = $key; }
	            }
				
				if ($create_keys == 1) {
					$all_rows[] = $keys_array;
				}
				
				$create_keys = 0;

    	        $all_rows[] = $row;
        	}
	
		}
		mysqli_free_result($result);	

		return $all_rows;
	
	}



	//  add(TABLE_NAME, Associative Array )
	//	Add a row to the TABLE_NAME, based on the column names 
	//		contained in the keys of the array, and the values get inserted.
	//
	
	function add($table, $data) {
		$c = "";
		$v = "";

		foreach ($data as $key => $value) {

			$value = mysqli_escape_string ($this->link, $value);
			if ($c == "") {
				$c = "`$key`";
				$v = "'$value'";
			} else {
				$c = $c . ",`$key`";
				$v = $v . ",'$value'";
			}
		}
		

		$sql_query = "INSERT INTO $this->database.$table ($c) VALUES($v) ";

		//echo $sql_query . "\n";
		$result = mysqli_query($this->link, $sql_query);
		if ( !$result) {
//	        printf("Error: %s\n", mysqli_error($this->link));
	        return FALSE;
		}
		$i = mysqli_affected_rows($this->link);
	
		return $i;
	
	}

	//  remove(TABLE_NAME, COLUMN_NAME, VALUE)
	//
	//		delete a row where column name $c equals the value $v
	//
	
	function remove($table, $c, $v) {
	
		$v = mysqli_escape_string ($this->link, $v);
		$sql_query = "DELETE FROM $this->database.$table WHERE `$c`='$v' LIMIT 1";

		$result = mysqli_query($this->link, $sql_query);
		if ( !$result) {
	        printf("Error: %s\n", mysqli_error($this->link));
	        return FALSE;
		}
		$i = mysqli_affected_rows();

		// echo "<!-- $i / MYSQL ERROR " .  mysqli_error() . " -->";
		return $i;
	
	}


	//  update(TABLE_NAME, COLUMN_NAME, VALUE, DATA)
	//
	//		update a row where column name $c equals the value $v
	//		with the data contained in the Associative Array $data
	//		where the keys of the array are the column names
	//
	
	function update($table, $c, $v, $data) {

		$set = "";

		foreach ($data as $key => $value) {

			$value = mysqli_escape_string ($this->link, $value);
			if ($set != "") { $set = $set . ","; }
			$set = $set . " `$key`='$value'";
			
		}
		
		$where ='';
		if ( is_array($c) && is_array($v) ) {
			$i=0;
			foreach ( $c as $c1 ) {
				$c1 = mysqli_escape_string ($this->link, $c1);
				$v[$i] = mysqli_escape_string ($this->link, $v[$i]);			
				$where_a[] = "`$c1` = '$v[$i]' ";
				$i++;
			}
			$where = implode ( " and ", $where_a ) ;
		
		} else {
			$c = mysqli_escape_string ($this->link, $c);
			$v = mysqli_escape_string ($this->link, $v);
			$where = "`$c` = '$v'";
		}
		$sql_query = "UPDATE $this->database.$table SET $set WHERE $where";
		//echo " === > $sql_query\n";

		$result = mysqli_query($this->link, $sql_query);
		if ( !$result) {
	        printf("Error: %s : %s \n", mysqli_error($this->link), $sql_query);
	        
	        return FALSE;
		}
		$i = mysqli_affected_rows($this->link);
		// echo "<!-- $i / MYSQL ERROR " .  mysqli_error() . " -->";

		return $i;

	}

}

?>
