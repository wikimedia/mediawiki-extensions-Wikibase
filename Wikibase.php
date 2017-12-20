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
 * @license GPL-2.0+
 */
// Hack! Only to be loaded when running browser tests on Jenkins CI
if ( isset( $wgWikimediaJenkinsCI ) && $wgWikimediaJenkinsCI == true && getenv( 'MEDIAWIKI_ENVIRONMENT' ) ) {
	wfLoadExtension( 'WikibaseLib', __DIR__ . '/lib/extension.json' );
	wfLoadExtension( 'WikibaseRepo', __DIR__ . '/repo2/extension.json' );
	require_once __DIR__ . '/repo/config/Wikibase.example.php';
} else {
	wfLoadExtension( 'WikibaseLib', __DIR__ . '/lib/extension.json' );
	wfLoadExtension( 'WikibaseRepo', __DIR__ . '/repo3/extension.json' );
	require_once __DIR__ . '/repo/config/Wikibase.example.php';
}
