<?php

namespace Wikibase\Tests;

use MediaWikiIntegrationTestCase;
use MWNamespace;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\WikibaseRepo;

/**
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ClientDefaultsTest extends MediaWikiIntegrationTestCase {

	public function settingsProvider(): iterable {
		yield 'no local repo, all values set' => [
			[ // $settings
				'repoUrl' => 'http://acme.com',
				'repoArticlePath' => '/wiki',
				'repoScriptPath' => '/w',
				'siteGlobalID' => 'mywiki',
				'repoDatabase' => 'foo',
				'changesDatabase' => 'doo',
				'sharedCacheKeyPrefix' => 'wikibase_shared/',
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
				'sharedCacheKeyPrefix' => 'wikibase_shared/',
			]
		];

		yield 'local repo, all values set' => [
			[ // $settings
				'repoUrl' => 'http://acme.com',
				'repoArticlePath' => '/wiki',
				'repoScriptPath' => '/w',
				'siteGlobalID' => 'mywiki',
				'repoDatabase' => 'foo',
				'changesDatabase' => 'doo',
				'sharedCacheKeyPrefix' => 'foo:WBL/',
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
				'sharedCacheKeyPrefix' => 'foo:WBL/',
			]
		];

		if ( WikibaseSettings::isRepoEnabled() ) {
			yield 'local repo, no values set' => [
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
					'sharedCacheKeyPrefix' => 'wikibase_shared/mw_mywiki',
				]
			];
		}

		yield 'derive changesDatabase' => [
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
			yield 'sharedCacheKeyPrefix explicitly set' => [
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

		if ( WikibaseSettings::isRepoEnabled() ) {
			$repoSettings = WikibaseRepo::getSettings();
			$entityNamespaces = $repoSettings->getSetting( 'entityNamespaces' );
			$namespaceNames = array_map( [ MWNamespace::class, 'getCanonicalName' ], $entityNamespaces );

			yield 'default repoNamespaces and entityNamespaces' => [
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
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testDefaults( array $settings, array $wg, $repoIsLocal, $expected ) {
		$this->markTestSkipped( 'flaky, see T214761' );
		$this->setMwGlobals( $wg );

		$defaults = require __DIR__ . '/../../config/WikibaseClient.default.php';

		$settings = array_merge( $defaults, $settings );
		$settings = new SettingsArray( $settings );

		//NOTE: thisWikiIsTheRepo is used by some "magic" (dynamic) defaults
		//      to decide how to behave. Normally, this is true if and only if
		//      the WikibaseRepo extension is loaded.
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
