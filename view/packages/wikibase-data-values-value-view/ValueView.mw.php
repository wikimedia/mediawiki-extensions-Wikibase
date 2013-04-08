<?php

/**
 * MediaWiki setup for the "ValueView" extension.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

global $wgExtensionCredits, $wgExtensionMessagesFiles, $wgHooks, $wgResourceModules;

$wgExtensionCredits['datavalues'][] = array(
	'path' => __DIR__,
	'name' => 'ValueView',
	'version' => ValueView_VERSION,
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]'
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:ValueView',
	'descriptionmsg' => 'valueview-desc',
);

$wgExtensionMessagesFiles['ValueView'] = __DIR__ . '/ValueView.i18n.php';

/**
 * Hook for registering QUnit test cases.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
 * @since 0.1
 *
 * @param array &$testModules
 * @param \ResourceLoader &$resourceLoader
 * @return boolean
 */
$wgHooks['ResourceLoaderTestModules'][] = function ( array &$testModules, \ResourceLoader &$resourceLoader ) {
	// Register jQuery.valueview QUnit tests. Take the predefined test definitions and make them
	// suitable for registration with MediaWiki's resource loader.
	$ownModules = include( __DIR__ . '/ValueView.tests.qunit.php' );
	$ownModulesTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' =>  'DataValues/ValueView',
	);
	foreach( $ownModules as $ownModuleName => $ownModule ) {
		$testModules['qunit'][ $ownModuleName ] = $ownModule + $ownModulesTemplate;
	}
	return true;
};

// Resource Loader module registration
$wgResourceModules = array_merge(
	$wgResourceModules,
	include( __DIR__ . '/ValueView.resources.mw.php' )
);
