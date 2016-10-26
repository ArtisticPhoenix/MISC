<?php

	ini_set('display_errors', 1);
	error_reporting(-1);
	
	require_once realpath(__DIR__).'/wp-config.php';
	
	$db_prefix = $table_prefix;
	$db_host = escapeshellarg(DB_HOST);
	$db_name = escapeshellarg(DB_NAME);
	$db_user = escapeshellarg(DB_USER);
	$db_pass = escapeshellarg(DB_PASSWORD);
	
	$filename = escapeshellarg( realpath(__DIR__).'/'.date('Y-m-d').'_wp_dump.sql' );
	
	$path = '';
	
	//Export the sql from the database and output the status to the page
	if(isset( $_GET['import'] ) ){
		$filename = escapeshellarg(realpath(__DIR__).'/'.$_GET['import'].'.sql');
		$command = "{$path}mysql --host={$db_host} --user={$db_user} --password={$db_pass} {$db_name} < {$filename}";
	}else{
		$command = "{$path}mysqldump --host={$db_host} --user={$db_user} --password={$db_pass} {$db_name} > {$filename}";
	}
		
			
	echo $command .'<br>';
	
	exec($command, $output, $worked);
	
	
	switch($worked){
		case 0:
			if(!isset( $_GET['import'] ) && is_file($filename)){
				$res['error'] = true;
				$res['messages'][] =  'There was a file write error during the database backup';
			}else{
				$res['messages'][] =  'Database backup successfully exported';
			}
		break;
		case 1:
			//pre_var_export($output);
			$res['error'] = true;
			$res['messages'][] =  'There was an error during the database backup';
		break;
		case 2:
			//pre_var_export($output);
			$res['error'] = true;
			$res['messages'][] =   'There was an error during during the database backup';
		break;
	}
	
	echo 'Done';
	
	
