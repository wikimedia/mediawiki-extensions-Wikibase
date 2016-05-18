<?php

namespace Wikibase\Test;

use Wikibase\Client\WikibaseClient;
use Wikibase\SettingsArray;

/**
 * Copyright © 02.07.13 by the authors listed below.
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ClientDefaultsTest extends \MediaWikiTestCase {

	public function settingsProvider() {
		$cases = array(
			array( // #0: no local repo, all values set
				array( // $settings
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
					'repoDatabase' => 'foo',
					'changesDatabase' => 'doo',
					'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ),
				),
				array( // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
				),
				false, // $repoIsLocal
				array( // $expected
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
					'repoDatabase' => 'foo',
					'changesDatabase' => 'doo',
					'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ),
				)
			),

			array( // #1: no local repo, no values set
				array( // $settings
				),
				array( // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
				),
				false, // $repoIsLocal
				array( // $expected
					'repoUrl' => '//www.wikidata.org',   // hardcoded default
					'repoArticlePath' => '/wiki/$1', // hardcoded default
					'repoScriptPath' => '/w', // hardcoded default
					'siteGlobalID' => 'mw_mywiki',
					'repoDatabase' => null,
					'changesDatabase' => null,
					'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ) . '-mw_mywiki',
				)
			),

			array( // #2: local repo, all values set
				array( // $settings
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
					'repoDatabase' => 'foo',
					'changesDatabase' => 'doo',
					'sharedCacheKeyPrefix' => 'foo:WBL/' . rawurlencode( WBL_VERSION ),
				),
				array( // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
				),
				true, // $repoIsLocal
				array( // $expected
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
					'repoDatabase' => 'foo',
					'changesDatabase' => 'doo',
					'sharedCacheKeyPrefix' => 'foo:WBL/' . rawurlencode( WBL_VERSION ),
				)
			),

			array( // #3: local repo, no values set
				array( // $settings
				),
				array( // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
				),
				true, // $repoIsLocal
				array( // $expected
					'repoUrl' => 'http://www.acme.com',
					'repoArticlePath' => '/mywiki',
					'repoScriptPath' => '/mediawiki',
					'siteGlobalID' => 'mw_mywiki',
					'repoDatabase' => false,
					'changesDatabase' => false,
					'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ) . '-mw_mywiki',
				)
			),

			array( // #4: derive changesDatabase
				array( // $settings
					'repoDatabase' => 'mw_foowiki',
				),
				array( // $wg
				),
				false, // $repoIsLocal
				array( // $expected
					'repoDatabase' => 'mw_foowiki',
					'changesDatabase' => 'mw_foowiki',
				)
			),
			array( // #5: sharedCacheKeyPrefix explicitly set
				array( // $settings
					'sharedCacheKeyPrefix' => 'wikibase_shared/wikidata_1_25wmf24'
				),
				array( // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
				),
				true, // $repoIsLocal
				array( // $expected
					'repoUrl' => 'http://www.acme.com',
					'repoArticlePath' => '/mywiki',
					'repoScriptPath' => '/mediawiki',
					'siteGlobalID' => 'mw_mywiki',
					'repoDatabase' => false,
					'changesDatabase' => false,
					'sharedCacheKeyPrefix' => 'wikibase_shared/wikidata_1_25wmf24',
				)
			),
			array( // #6: derive repoNamespaces and entityNamespaces
				array( // $settings
				),
				array( // $wg
				),
				false, // $repoIsLocal
				array( // $expected
					'repoNamespaces' => [ 'item' => '', 'property' => 'Property' ],
					'entityNamespaces' => [ 'item' => 0, 'property' => 120 ],
				)
			),
		);

		if ( defined( 'WB_VERSION' ) ) {
			$repoSettings = WikibaseClient::getDefaultInstance()->getRepoSettings();
			$entityNamespaces = $repoSettings->getSetting( 'entityNamespaces' );
			$namespaceNames = array_map( 'MWNamespace::getCanonicalName', $entityNamespaces );

			$cases[] = array( // #7: default repoNamespaces and entityNamespaces
				array( // $settings
				),
				array( // $wg
				),
				true, // $repoIsLocal
				array( // $expected
					'repoNamespaces' => $namespaceNames,
					'entityNamespaces' => $entityNamespaces,
				)
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testDefaults( array $settings, array $wg, $repoIsLocal, $expected ) {
		$this->setMwGlobals( $wg );

		$defaults = include WBC_DIR . '/config/WikibaseClient.default.php';

		$settings = array_merge( $defaults, $settings );
		$settings = new SettingsArray( $settings );

		//NOTE: thisWikiIsTheRepo us used by some "magic" (dynamic) defaults
		//      to decide how to behave. Normally, this is true if and only if
		//      WB_VERSION is defined.
		$settings->setSetting( 'thisWikiIsTheRepo', $repoIsLocal );

		foreach ( $expected as $key => $exp ) {
			$actual = $settings->getSetting( $key );
			$this->assertSame( $exp, $actual, "Setting $key" );
		}
	}

}
