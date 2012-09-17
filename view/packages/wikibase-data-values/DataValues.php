<?php

/**
 * Initialization file for the DataValues extension.
 *
 * Documentation:	 		https://www.mediawiki.org/wiki/Extension:DataValues
 * Support					https://www.mediawiki.org/wiki/Extension_talk:DataValues
 * Source code:				https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/DataValues.git
 *
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * This documentation group collects source code files belonging to the DataValues extension.
 *
 * @defgroup DataValues DataValues
 */

/**
 * DataValue parsers part of the DataValues extension.
 *
 * @defgroup DataValueParsers DataValueParsers
 * @ingroup DataValues
 */

/**
 * Data values part of the DataValues extension.
 *
 * @defgroup DataValue DataValue
 * @ingroup DataValues
 */

/**
 * Tests part of the DataValues extension.
 *
 * @defgroup DataValuesTests DataValuesTests
 * @ingroup DataValues
 */

namespace {

	if ( !defined( 'MEDIAWIKI' ) ) {
		die( 'Not an entry point.' );
	}

	define( 'DataValues_VERSION', '0.1' );

	$wgExtensionCredits['other'][] = array(
		'path' => __FILE__,
		'name' => 'DataValues',
		'version' => DataValues_VERSION,
		'author' => array(
			'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
		),
		'url' => 'https://www.mediawiki.org/wiki/Extension:DataValues',
		'descriptionmsg' => 'datavalues-desc'
	);

	$wgExtensionMessagesFiles['DataValues'] = __DIR__ . '/DataValues.i18n.php';

}
