<?php

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

$pwd = exec( 'pwd' );
passthru( 'composer update' );
chdir( $pwd );

require_once( __DIR__ . '/vendor/autoload.php' );