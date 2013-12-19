<?php

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

require_once( __DIR__ . '/evilMediaWikiBootstrap.php' );

$pwd = exec( 'pwd' );
chdir( __DIR__ . '/../..' );
passthru( 'composer update' );
chdir( $pwd );

require_once( __DIR__ . '/../../vendor/autoload.php' );