<?php

/**
 * MediaWiki setup for the Wikibase Query extension.
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
 * @ingroup WikibaseQuery
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

global $wgExtensionCredits, $wgExtensionMessagesFiles, $wgAutoloadClasses, $wgHooks, $wgVersion;

if ( version_compare( $wgVersion, '1.20c', '<' ) ) {
	die( '<b>Error:</b> Wikibase requires MediaWiki 1.20 or above.' );
}

// Include the WikibaseRepo extension if that hasn't been done yet, since it's required for Wikibase Query to work.
if ( !defined( 'WB_VERSION' ) ) {
	@include_once( __DIR__ . '/../repo/Wikibase.php' );
}

if ( !defined( 'WB_VERSION' ) ) {
	die( '<b>Error:</b> Wikibase Query depends on the <a href="https://www.mediawiki.org/wiki/Extension:Wikibase_Repo">Wikibase Repo</a> extension.' );
}

define( 'WIKIBASE_QUERY_VERSION', '0.1 alpha' );

$wgExtensionCredits['wikibase'][] = array(
	'path' => __DIR__,
	'name' => 'Wikibase Query',
	'version' => WIKIBASE_QUERY_VERSION,
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Wikibase_Query',
	'descriptionmsg' => 'wikibasequery-desc'
);

$wgExtensionMessagesFiles['WikibaseQuery'] = __DIR__ . '/WikibaseQuery.i18n.php';

foreach ( include( __DIR__ . '/WikibaseQuery.classes.php' ) as $class => $file ) {
	$wgAutoloadClasses[$class] = __DIR__ . '/' . $file;
}

/**
 * Hook to add PHPUnit test cases.
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
 *
 * @since 0.1
 *
 * @param array $files
 *
 * @return boolean
 */
$wgHooks['UnitTestsList'][]	= function( array &$files ) {
	// @codeCoverageIgnoreStart
	$testFiles = array(
		'Query',

		// TODO: below tests are disabled since the EntityContentFactory has no proper registration
		// system for new types of entities yet
//		'QueryContent',
//		'QueryHandler',
	);

	foreach ( $testFiles as $file ) {
		$files[] = __DIR__ . '/tests/phpunit/' . $file . 'Test.php';
	}

	return true;
	// @codeCoverageIgnoreEnd
};

$wgWBRepoSettings['entityPrefixes']['y'] = 'query';

define( 'CONTENT_MODEL_WIKIBASE_QUERY', "wikibase-query" );

$wgHooks['FormatAutocomments'][] = array( 'Wikibase\Autocomment::onFormat', array( CONTENT_MODEL_WIKIBASE_QUERY, "wikibase-query" ) );

$wgContentHandlers[CONTENT_MODEL_WIKIBASE_QUERY] = '\Wikibase\QueryHandler';

$wgExtraNamespaces[WB_NS_QUERY] = 'Query';
$wgExtraNamespaces[WB_NS_QUERY_TALK] = 'Query_talk';

$wgWBRepoSettings['entityNamespaces'][CONTENT_MODEL_WIKIBASE_QUERY] = WB_NS_QUERY;


$wgAutoloadClasses['Wikibase\HistoryQueryAction'] 		= __DIR__ . '/Query/HistoryQueryAction.php';
$wgAutoloadClasses['Wikibase\EditQueryAction'] 			= __DIR__ . '/Query/EditQueryAction.php';
$wgAutoloadClasses['Wikibase\ViewQueryAction'] 			= __DIR__ . '/Query/ViewQueryAction.php';
$wgAutoloadClasses['Wikibase\SubmitQueryAction'] 		= __DIR__ . '/Query/EditQueryAction.php';

$wgAutoloadClasses['Wikibase\QueryContent'] 			= __DIR__ . '/Query/QueryContent.php';
$wgAutoloadClasses['Wikibase\QueryHandler'] 			= __DIR__ . '/Query/QueryHandler.php';