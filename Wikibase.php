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
 * Testing entry point. Do not use for production setups!
 *
 * @see README.md
 * @see https://wikiba.se
 *
 * @license GPL-2.0-or-later
 */

if ( $wgEnableWikibaseRepo ?? true ) {
	wfLoadExtension( 'WikibaseRepository', __DIR__ . '/extension-repo.json' );

	if ( $wgWikimediaJenkinsCI ?? false ) {
		require_once __DIR__ . '/repo/config/Wikibase.ci.php';
	}
}

if ( $wgEnableWikibaseClient ?? true ) {
	wfLoadExtension( 'WikibaseClient', __DIR__ . '/extension-client.json' );

	if ( $wgWikimediaJenkinsCI ?? false ) {
		require_once __DIR__ . '/client/config/WikibaseClient.ci.php';
	}
}
