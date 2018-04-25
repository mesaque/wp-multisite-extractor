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

$query = sprintf( "SHOW TABLES FROM %s LIKE '%s%d%%'" , MYSQL_DB_WP, PREFIX, SITE_ID );

$result = $conn_mysql->query( $query );
$table_names = array();
while ( $row = $result->fetch_row() ):
	$table_names[] = $row[0];
endwhile;

$tables_agg = implode(' ', $table_names);
$tables_agg .= sprintf(' %susers %susermeta', PREFIX, PREFIX);

exec("mysqldump --user=".MYSQL_USER." --password=".MYSQL_PASSWORD." --host=".MYSQL_HOST." " .MYSQL_DB_WP." {$tables_agg} > ".ABSPATH."/tmp/".MYSQL_DB_WP.".sql");

exec("sed -i.bak s/wp_usermeta/wp_".SITE_ID."_usermeta/g tmp/".MYSQL_DB_WP.".sql");
exec("sed -i.bak s/wp_users/wp_".SITE_ID."_users/g tmp/".MYSQL_DB_WP.".sql");
exec("sed -i.bak s/wp_capabilities/wp_".SITE_ID."_capabilities/g tmp/".MYSQL_DB_WP.".sql");
exec("sed -i.bak s/wp_user_level/wp_".SITE_ID."_user_level/g tmp/".MYSQL_DB_WP.".sql");

$wp_core_version = exec("php wp-cli.phar --path=".WP_ROOT_PATH." core version");

exec("php wp-cli.phar --path=tmp/ core download  --force --version=" . $wp_core_version);

$url = exec("php wp-cli.phar --path=".WP_ROOT_PATH." site list --field=url --site__in=" . SITE_ID);
$plugins =  json_decode( exec("php wp-cli.phar --path=".WP_ROOT_PATH." plugin list  --format=json --url=" . $url), true );
$themes =  json_decode( exec("php wp-cli.phar --path=".WP_ROOT_PATH." theme list  --format=json --url=" . $url), true );

array_walk($plugins, function($value, $i) use (&$plugins) {
	if( 'inactive' == $value['status'] ) unset( $plugins[$i]);
});

array_walk($themes, function($value, $i) use (&$themes) {
	if( 'inactive' == $value['status'] ) unset( $themes[$i]);
});

foreach ($plugins as $key => $value):
	if( ! file_exists(WP_ROOT_PATH."/wp-content/plugins/{$value['name']}.php" ) ):
		exec("cp -rf ".WP_ROOT_PATH."/wp-content/plugins/{$value['name']} tmp/wp-content/plugins/" );
	else:
		exec("cp -rf ".WP_ROOT_PATH."/wp-content/plugins/{$value['name']}.php tmp/wp-content/plugins/" );
	endif;
endforeach;

exec("cp -rf ".WP_ROOT_PATH."/wp-content/themes/{$themes[0]['name']} tmp/wp-content/themes/" );