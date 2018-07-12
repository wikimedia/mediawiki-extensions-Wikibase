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
 * Testing entry point. Do not use for production setups!
 *
 * @see README.md
 * @see http://wikiba.se
 *
 * @license GPL-2.0-or-later
 */

if ( !array_key_exists( 'wgEnableWikibaseRepo', $GLOBALS ) || $GLOBALS['wgEnableWikibaseRepo'] ) {
	require_once __DIR__ . '/repo/Wikibase.php';

	if ( isset( $wgWikimediaJenkinsCI ) && $wgWikimediaJenkinsCI == true ) {
		// Use example config for testing
		require_once __DIR__ . '/repo/config/Wikibase.example.php';
	}
}

if ( !array_key_exists( 'wgEnableWikibaseClient', $GLOBALS ) || $GLOBALS['wgEnableWikibaseClient'] ) {
	require_once __DIR__ . '/client/WikibaseClient.php';

	if ( isset( $wgWikimediaJenkinsCI ) && $wgWikimediaJenkinsCI == true ) {
		// Use example config for testing
		require_once __DIR__ . '/client/config/WikibaseClient.example.php';
	}
}
