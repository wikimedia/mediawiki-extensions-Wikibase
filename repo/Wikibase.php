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
 * Entry point for the Wikibase Repository extension.
 *
 * @see README.md
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase_Repository
 *
 * @license GPL-2.0-or-later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// load parts already converted to extension registration
wfLoadExtension( 'WikibaseRepository', __DIR__ . '/../extension-repo.json' );

// Only needed temporarily for i18n messages.
require_once __DIR__ . '/../view/WikibaseView.php';

call_user_func( function() {
	global $wgExtensionMessagesFiles,
		$wgHooks,
		$wgMessagesDirs,
		$wgWBRepoSettings;

	// i18n messages, kept for backward compatibility (T257442)
	$wgMessagesDirs['Wikibase'] = __DIR__ . '/i18n';
	$wgMessagesDirs['WikibaseApi'] = __DIR__ . '/i18n/api';
	$wgMessagesDirs['WikibaseLib'] = __DIR__ . '/../lib/i18n';
	$wgExtensionMessagesFiles['WikibaseAlias'] = __DIR__ . '/Wikibase.i18n.alias.php';
	$wgExtensionMessagesFiles['WikibaseNS'] = __DIR__ . '/Wikibase.i18n.namespaces.php';
	$wgExtensionMessagesFiles['wikibaserepomagic'] = __DIR__ . '/WikibaseRepo.i18n.magic.php';

	$wgHooks['HtmlPageLinkRendererEnd'][] = 'Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler::onHtmlPageLinkRendererEnd';
	$wgHooks['ShowSearchHit'][] = 'Wikibase\Repo\Hooks\ShowSearchHitHandler::onShowSearchHit';
	$wgHooks['ShowSearchHitTitle'][] = 'Wikibase\Repo\Hooks\ShowSearchHitHandler::onShowSearchHitTitle';

	$wgWBRepoSettings = array_merge(
		require __DIR__ . '/../lib/config/WikibaseLib.default.php',
		require __DIR__ . '/config/Wikibase.default.php'
	);

	// Tell client/config/WikibaseClient.example.php not to configure an example repo, because this wiki is the repo;
	// added in July 2020, this is hopefully just a fairly short-lived hack.
	define( 'WB_NO_CONFIGURE_EXAMPLE_REPO', 1 );
} );
