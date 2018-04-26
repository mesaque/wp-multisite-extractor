<?php
mysqli_report(MYSQLI_REPORT_STRICT);
ini_set("memory_limit", -1);
set_time_limit(0);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
define( 'ABSPATH', dirname(__FILE__) . '/' );
( @include_once ABSPATH .'options.php' ) or die( 'missing options functions file' );

if ( ! class_exists( 'mysqli' ) ) :
    echo 'PHP extension not exist';
    exit;
endif;

define( 'MYSQL_USER', $opt->get('user') );
define( 'MYSQL_DB_WP', $opt->get('name') );
define( 'MYSQL_PASSWORD', $opt->get('password') );
define( 'MYSQL_HOST', $opt->get('host') );
define( 'PREFIX', $opt->get('prefix') );
define( 'SITE_ID', $opt->get('siteid') );
define( 'WP_ROOT_PATH', $opt->get('wprootpath') );


$conn_mysql = new mysqli( MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB_WP );

$query_tables  = sprintf( "SHOW TABLES FROM %s LIKE '%s%d%%'" , MYSQL_DB_WP, PREFIX, SITE_ID );
$query_plugins = sprintf( "SELECT * FROM %s.%s%d_options WHERE option_name = 'active_plugins'" , MYSQL_DB_WP, PREFIX, SITE_ID );
$query_plugins_net = sprintf( "SELECT * FROM %s.%ssitemeta WHERE meta_key = 'active_sitewide_plugins'" , MYSQL_DB_WP, PREFIX );
$query_themes  = sprintf( "SELECT * FROM %s.%s%d_options WHERE option_name = 'stylesheet'", MYSQL_DB_WP, PREFIX, SITE_ID );

$result = $conn_mysql->query( $query_tables );
$table_names  = array();
$plugin_names = array();
$theme_names  = array();

while ( $row = $result->fetch_row() ):
	$table_names[] = $row[0];
endwhile;
$result = $conn_mysql->query( $query_plugins );
while ( $row = $result->fetch_row() ):
	$pl =  unserialize($row[2])[0];
	$plugin_names[] = explode('/', $pl)[0];
endwhile;
$result = $conn_mysql->query( $query_plugins_net );
while ( $row = $result->fetch_row() ):
	$pl = unserialize($row[3]);
	foreach ($pl as $key => $value):
		if( false !== strpos($key, '/')):
			$plugin_names[] = explode('/', $key)[0];
		else:
			$plugin_names[] = $key;
		endif;
	endforeach;
endwhile;
$result = $conn_mysql->query( $query_themes );
while ( $row = $result->fetch_row() ):
	$theme_names[] = $row[2];
endwhile;


$tables_agg = implode(' ', $table_names);
$tables_agg .= sprintf(' %susers %susermeta', PREFIX, PREFIX);

if (!file_exists(ABSPATH.'tmp')) {
    mkdir(ABSPATH.'tmp', 0777, true);
}

exec("mysqldump --user=".MYSQL_USER." --password=".MYSQL_PASSWORD." --host=".MYSQL_HOST." " .MYSQL_DB_WP." {$tables_agg} > ".ABSPATH."/tmp/".MYSQL_DB_WP.".sql");

exec("sed -i.bak s/".PREFIX."usermeta/".PREFIX.SITE_ID."_usermeta/g tmp/".MYSQL_DB_WP.".sql");
exec("sed -i.bak s/".PREFIX."users/".PREFIX.SITE_ID."_users/g tmp/".MYSQL_DB_WP.".sql");
exec("sed -i.bak s/".PREFIX."capabilities/".PREFIX.SITE_ID."_capabilities/g tmp/".MYSQL_DB_WP.".sql");
exec("sed -i.bak s/".PREFIX."user_level/".PREFIX.SITE_ID."_user_level/g tmp/".MYSQL_DB_WP.".sql");

$wp_core_version = exec("php wp-cli.phar --path=".WP_ROOT_PATH." core version");

exec("php wp-cli.phar --path=tmp/ core download  --force --version=" . $wp_core_version);

foreach ($plugin_names as $key => $value):
	if( ! file_exists(WP_ROOT_PATH."/wp-content/plugins/{$value}" ) ):
		exec("cp -rf ".WP_ROOT_PATH."/wp-content/plugins/{$value} tmp/wp-content/plugins/" );
	else:
		exec("cp -rf ".WP_ROOT_PATH."/wp-content/plugins/{$value} tmp/wp-content/plugins/" );
	endif;
endforeach;

exec("cp -rf ".WP_ROOT_PATH."/wp-content/themes/{$theme_names[0]} tmp/wp-content/themes/" );

if (!file_exists(ABSPATH.'tmp/wp-content/uploads/')) {
    mkdir(ABSPATH.'tmp/wp-content/uploads/', 0777, true);
}

exec("cp -rf ".WP_ROOT_PATH."/wp-content/uploads/sites/".SITE_ID."/* tmp/wp-content/uploads/" );