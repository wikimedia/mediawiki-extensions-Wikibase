<?php

namespace Wikibase\Test;

use MWNamespace;
use Wikibase\SettingsArray;
use Wikibase\WikibaseSettings;

/**
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ClientDefaultsTest extends \MediaWikiTestCase {

	public function settingsProvider() {
		$cases = [
			[ // #0: no local repo, all values set
				[ // $settings
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
					'repoDatabase' => 'foo',
					'changesDatabase' => 'doo',
					'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ),
				],
				[ // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
				],
				false, // $repoIsLocal
				[ // $expected
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
					'repoDatabase' => 'foo',
					'changesDatabase' => 'doo',
					'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ),
				]
			],

			[ // #1: no local repo, no values set
				[ // $settings
				],
				[ // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
				],
				false, // $repoIsLocal
				[ // $expected
					'repoUrl' => '//www.wikidata.org',   // hardcoded default
					'repoArticlePath' => '/wiki/$1', // hardcoded default
					'repoScriptPath' => '/w', // hardcoded default
					'siteGlobalID' => 'mw_mywiki',
					'repositories' => [
						'' => [
							'repoDatabase' => null,
							'baseUri' => '//www.wikidata.org/entity/',
							'entityNamespaces' => [
								'item' => 0,
								'property' => 120,
							],
						],
					],
					'changesDatabase' => null,
					'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ) . '-mw_mywiki',
				]
			],

			[ // #2: local repo, all values set
				[ // $settings
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
					'repoDatabase' => 'foo',
					'changesDatabase' => 'doo',
					'sharedCacheKeyPrefix' => 'foo:WBL/' . rawurlencode( WBL_VERSION ),
				],
				[ // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
				],
				true, // $repoIsLocal
				[ // $expected
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
					'repoDatabase' => 'foo',
					'changesDatabase' => 'doo',
					'sharedCacheKeyPrefix' => 'foo:WBL/' . rawurlencode( WBL_VERSION ),
				]
			],
		];

		if ( WikibaseSettings::isRepoEnabled() ) {
			$cases[] = [ // #3: local repo, no values set
				[ // $settings
				],
				[ // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
					'wgWBRepoSettings' => [
						'entityNamespaces' => [ 'item' => 303 ],
					],
				],
				true, // $repoIsLocal
				[ // $expected
					'repoUrl' => 'http://www.acme.com',
					'repoArticlePath' => '/mywiki',
					'repoScriptPath' => '/mediawiki',
					'siteGlobalID' => 'mw_mywiki',
					'repositories' => [
						'' => [
							'repoDatabase' => false,
							'baseUri' => 'http://www.acme.com/entity/',
						],
					],
					'changesDatabase' => false,
					'sharedCacheKeyPrefix' => 'wikibase_shared/' . rawurlencode( WBL_VERSION ) . '-mw_mywiki',
				]
			];
		}

		$cases[] = [ // #4: derive changesDatabase
			[ // $settings
				'repositories' => [
					'' => [
						'repoDatabase' => 'mw_foowiki'
					],
				],
			],
			[ // $wg
			],
			false, // $repoIsLocal
			[ // $expected
				'changesDatabase' => 'mw_foowiki',
			]
		];

		if ( WikibaseSettings::isRepoEnabled() ) {
			$cases[] = [ // #5: sharedCacheKeyPrefix explicitly set
				[ // $settings
					'sharedCacheKeyPrefix' => 'wikibase_shared/wikidata_1_25wmf24'
				],
				[ // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
					'wgWBRepoSettings' => [ 'entityNamespaces' => [ 'item' => 303 ] ],
				],
				true, // $repoIsLocal
				[ // $expected
					'repoUrl' => 'http://www.acme.com',
					'repoArticlePath' => '/mywiki',
					'repoScriptPath' => '/mediawiki',
					'siteGlobalID' => 'mw_mywiki',
					'changesDatabase' => false,
					'sharedCacheKeyPrefix' => 'wikibase_shared/wikidata_1_25wmf24',
				]
			];
		}

		$cases[] = [ // #6: derive repoNamespaces and entityNamespaces
			[ // $settings
			],
			[ // $wg
			],
			false, // $repoIsLocal
			[ // $expected
				'repoNamespaces' => [ 'item' => '', 'property' => 'Property' ],
				'repositories' => [
					'' => [
						'entityNamespaces' => [ 'item' => 0, 'property' => 120 ],
					],
				],
			]
		];

		if ( WikibaseSettings::isRepoEnabled() ) {
			$repoSettings = WikibaseSettings::getRepoSettings();
			$entityNamespaces = $repoSettings->getSetting( 'entityNamespaces' );
			$namespaceNames = array_map( [ MWNamespace::class, 'getCanonicalName' ], $entityNamespaces );

			$cases[] = [ // #7: default repoNamespaces and entityNamespaces
				[], // $settings
				[], // $wg
				true, // $repoIsLocal
				[ // $expected
					'repoNamespaces' => $namespaceNames,
					'repositories' => [
						'' => [
							'entityNamespaces' => $entityNamespaces,
						],
					],
				]
			];
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

			if ( $key === 'repositories' ) {
				$this->assertRepositorySettingsEqual( $exp, $actual );
				continue;
			}

			$this->assertSame( $exp, $actual, "Setting $key" );
		}
	}

	private function assertRepositorySettingsEqual( $expected, $actual ) {
		foreach ( $expected as $repoName => $expectedRepoSettings ) {
			$actualToCompare = array_intersect_key( $actual[$repoName], $expectedRepoSettings );
			$this->assertSame( $expectedRepoSettings, $actualToCompare );
		}
	}

}
