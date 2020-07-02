<?php

/**
 * Welcome to the inside of Wikibase,              <>
 * the software that powers                   /\        /\
 * Wikidata and other                       <{  }>    <{  }>
 * structured data websites.        <>   /\   \/   /\   \/   /\   <>
 *                                     //  \\    //  \\    //  \\
 * It is Free Software.              <{{    }}><{{    }}><{{    }}>
 *                                /\   \\  //    \\  //    \\  //   /\
 *                              <{  }>   ><        \/        ><   <{  }>
 *                                \/   //  \\              //  \\   \/
 *                            <>     <{{    }}>     +--------------------------+
 *                                /\   \\  //       |                          |
 *                              <{  }>   ><        /|  W  I  K  I  B  A  S  E  |
 *                                \/   //  \\    // |                          |
 * We are                            <{{    }}><{{  +--------------------------+
 * looking for people                  \\  //    \\  //    \\  //
 * like you to join us in           <>   \/   /\   \/   /\   \/   <>
 * developing it further. Find              <{  }>    <{  }>
 * out more at https://wikiba.se              \/        \/
 * and join the open data revolution.              <>
 */

/**
 * Entry point for the Wikibase Client extension.
 *
 * @see README.md
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase_Client
 *
 * @license GPL-2.0-or-later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "Not an entry point.\n" );
}

define( 'WBC_VERSION', '0.5 alpha' );

// load parts already converted to extension registration
wfLoadExtension( 'WikibaseClient', __DIR__ . '/../extension-client-wip.json' );

// Sub-extensions needed by WikibaseClient
require_once __DIR__ . '/../lib/WikibaseLib.php';

call_user_func( function() {
	global $wgExtensionMessagesFiles,
		$wgHooks,
		$wgMessagesDirs,
		$wgWBClientSettings;

	// i18n
	$wgMessagesDirs['wikibaseclient'] = __DIR__ . '/i18n';
	$wgMessagesDirs['wikibaseclientapi'] = __DIR__ . '/i18n/api';
	$wgExtensionMessagesFiles['Wikibaseclientalias'] = __DIR__ . '/WikibaseClient.i18n.alias.php';
	$wgExtensionMessagesFiles['wikibaseclientmagic'] = __DIR__ . '/WikibaseClient.i18n.magic.php';

	$wgHooks['OldChangesListRecentChangesLine'][] =
		'\Wikibase\Client\Hooks\ChangesListLinesHandler::onOldChangesListRecentChangesLine';
	$wgHooks['EnhancedChangesListModifyLineData'][] =
		'\Wikibase\Client\Hooks\ChangesListLinesHandler::onEnhancedChangesListModifyLineData';
	$wgHooks['EnhancedChangesListModifyBlockLineData'][] =
		'\Wikibase\Client\Hooks\ChangesListLinesHandler::onEnhancedChangesListModifyBlockLineData';

	$wgWBClientSettings = array_merge(
		require __DIR__ . '/../lib/config/WikibaseLib.default.php',
		require __DIR__ . '/config/WikibaseClient.default.php'
	);
} );
