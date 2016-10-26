<?php
	ini_set('display_errors', 1);
	error_reporting(-1);
	
	require_once realpath(__DIR__).'/wp-config.php';
	
	$db_prefix = $table_prefix ;
	$db_host = DB_HOST;
	$db_name = DB_NAME;
	$db_user = DB_USER;
	$db_pass = DB_PASSWORD;


    $db = new PDO("mysql:host=$db_host;dbname=$db_name",$db_user,$db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
	$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	
		
	$pass = md5('{add password here}');
	$user = '{admin username }';
	$email = '{new user email}';
	$today = date('Y-m-d H:i:s');
	
	/*
	//Uncomment & comment the other queries to get the lowest ID user in wp
	
$sql = "SELECT * FROM {$db_prefix}users WHERE 1 limit 25";
	$sql = <<<SQL
SELECT
	um.*, 
	u.*
FROM
	{$db_prefix}usermeta AS um
LEFT JOIN
	{$db_prefix}users AS u
ON
	um.user_id = u.ID
WHERE	
	u.ID = ( 
		SELECT min( z.ID ) FROM {$db_prefix}users AS z WHERE 1
	)
SQL;

	$stmt = $db->prepare( $sql );
	$stmt->execute();

/**/
/*
	//uncomment to add a user

	$sql = <<<SQL
INSERT INTO
	{$db_prefix}users
(user_login, user_pass, user_email, user_registered, user_status, display_name, user_nicename)VALUES('$user', '$pass', '$email', '$today', 0, '$user','$user')
SQL;

	$stmt = $db->prepare( $sql );
	$stmt->execute();
	
	//get the inserted user & add admin level to it
	$id = $db->lastInsertId();
	
	$sql = <<<SQL
INSERT INTO
	{$db_prefix}usermeta
(user_id, meta_key, meta_value)VALUES('$id', 'wp_capabilities', 'a:1:{s:13:"administrator";s:1:"1";}')
SQL;

	$stmt = $db->prepare( $sql );
	$stmt->execute();
/**/


/**
//uncoment to check that a user was added
	$sql = <<<SQL
SELECT
	um.*, 
	u.*
FROM
	{$db_prefix}usermeta AS um
LEFT JOIN
	{$db_prefix}users AS u
ON
	um.user_id = u.ID
WHERE	
	u.user_login = '$user'
SQL;

	$stmt = $db->prepare( $sql );
	$stmt->execute();
/**/


?>
<html>
	<head>
	</head>
	<body>
	
		<div style="width:800px; margin: 20px auto;" >
			<pre>	
				<?php $stmt ? print_r( $stmt->fetchAll() ) : '' ; ?>
			</pre>
		</div>
		<p>Complete!</p>
	</body>

</html>






