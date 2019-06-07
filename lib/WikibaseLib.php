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
 * out more at http://wikiba.se               \/        \/
 * and join the open data revolution.              <>
 */

/**
 * Entry point for the WikibaseLib extension.
 *
 * @see README.md
 * @see https://www.mediawiki.org/wiki/Extension:WikibaseLib
 *
 * @license GPL-2.0-or-later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// load parts already converted to extension registration
wfLoadExtension( 'WikibaseLib', __DIR__ . '/../extension-lib-wip.json' );

define( 'WBL_VERSION', '0.5 alpha' );

// Nasty hack: some lib's tests rely on ItemContent class defined in Repo! Load it in client-only mode to have tests pass
if ( !defined( 'WB_VERSION' ) && defined( 'MW_PHPUNIT_TEST' ) ) {
	global $wgAutoloadClasses;
	$wgAutoloadClasses['Wikibase\\ItemContent'] = __DIR__ . '/../repo/includes/Content/ItemContent.php';
	$wgAutoloadClasses['Wikibase\\EntityContent'] = __DIR__ . '/../repo/includes/Content/EntityContent.php';
	$wgAutoloadClasses['Wikibase\\Repo\\Content\\EntityContentDiff'] = __DIR__ . '/../repo/includes/Content/EntityContentDiff.php';
}

call_user_func( function() {
	global $wgResourceModules;

	// Resource Loader Modules:
	$wgResourceModules = array_merge(
		$wgResourceModules,
		require __DIR__ . '/resources/Resources.php'
	);
} );
