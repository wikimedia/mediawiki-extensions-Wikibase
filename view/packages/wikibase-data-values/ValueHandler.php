<?php

/**
 * Initialization file for the ValueHandler extension.
 * Extension documentation: http://www.mediawiki.org/wiki/Extension:ValueHandler
 *
 * @file
 * @ingroup ValueHandler
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * This documentation group collects source code files belonging to ValueHandler.
 *
 * Please do not use this group name for other code.
 *
 * @defgroup ValueHandler ValueHandler
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

define( 'ValueHandler_VERSION', '0.1 alpha' );

// Register the internationalization file.
$wgExtensionMessagesFiles['ValueHandler'] = dirname( __FILE__ ) . '/ValueHandler.i18n.php';

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'ValueHandler',
	'version' => ValueHandler_VERSION,
	'author' => array( '[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]' ),
	'url' => 'https://www.mediawiki.org/wiki/Extension:ValueHandler',
	'descriptionmsg' => 'valuehandler-desc',
);

$incDir = dirname( __FILE__ ) . '/includes/';

// Autoload the classes.
$wgAutoloadClasses['ValueHandlerHooks']				= dirname( __FILE__ ) . '/ValueHandler.hooks.php';
$wgAutoloadClasses['ValueHandlerSettings']		  	= dirname( __FILE__ ) . '/ValueHandler.settings.php';




// tests
$wgAutoloadClasses['ValueHandler\Test\StringValueParserTest']	= dirname( __FILE__ ) . '/tests/valueparser/StringValueParserTest.php';
$wgAutoloadClasses['ValueHandler\Test\ValueParserTestBase']		= dirname( __FILE__ ) . '/tests/valueparser/ValueParserTestBase.php';


// Register the hooks
$wgHooks['UnitTestsList'][] = 'ValueHandlerHooks::registerUnitTests';

$egValueHandlerSettings = array();

unset( $incDir );