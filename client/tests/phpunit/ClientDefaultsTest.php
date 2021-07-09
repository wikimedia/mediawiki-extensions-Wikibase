<?php

namespace Wikibase\Tests;

use MediaWikiIntegrationTestCase;
use MWNamespace;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\WikibaseSettings;

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
				'entitySources' => [
					'local' => [
						'repoDatabase' => 'foo',
						'entityNamespaces' => [ 'item' => 120 ],
						'baseUri' => 'http://acme.com/',
						'rdfNodeNamespacePrefix' => 'a',
						'rdfPredicateNamespacePrefix' => '',
						'interwikiPrefix' => 'repo',
					],
				],
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
				'entitySources' => [
					'local' => [
						'repoDatabase' => 'foo',
						'entityNamespaces' => [ 'item' => 120 ],
						'baseUri' => 'http://acme.com/',
						'rdfNodeNamespacePrefix' => 'a',
						'rdfPredicateNamespacePrefix' => '',
						'interwikiPrefix' => 'repo',
					],
				],
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
				'entitySources' => [
					'local' => [
						'repoDatabase' => 'foo',
						'entityNamespaces' => [ 'item' => 120 ],
						'baseUri' => 'http://acme.com/',
						'rdfNodeNamespacePrefix' => 'a',
						'rdfPredicateNamespacePrefix' => '',
						'interwikiPrefix' => 'repo',
					],
				],
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
				'entitySources' => [
					'local' => [
						'repoDatabase' => 'foo',
						'entityNamespaces' => [ 'item' => 120 ],
						'baseUri' => 'http://acme.com/',
						'rdfNodeNamespacePrefix' => 'a',
						'rdfPredicateNamespacePrefix' => '',
						'interwikiPrefix' => 'repo',
					],
				],
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
						'localEntitySourceName' => 'local',
						'entityNamespaces' => [ 'item' => 303 ],
					],
				],
				true, // $repoIsLocal
				[ // $expected
					'repoUrl' => 'http://www.acme.com',
					'repoArticlePath' => '/mywiki',
					'repoScriptPath' => '/mediawiki',
					'siteGlobalID' => 'mw_mywiki',
					'repoNamespaces' => [ 'item' => MWNamespace::getCanonicalName( 303 ) ],
					'entitySources' => [
						'local' => [
							'repoDatabase' => false,
							'entityNamespaces' => [ 'item' => '303/main' ],
							'baseUri' => 'http://www.acme.com/entity/',
							'rdfNodeNamespacePrefix' => 'wd',
							'rdfPredicateNamespacePrefix' => '',
							'interwikiPrefix' => '',
							'type' => 'db',
						],
					],
					'changesDatabase' => false,
					'sharedCacheKeyPrefix' => 'wikibase_shared/mw_mywiki',
				]
			];
		}

		yield 'derive changesDatabase' => [
			[ // $settings
				'entitySources' => [
					'foo' => [
						'repoDatabase' => 'mw_foowiki',
					],
				],
				'itemAndPropertySourceName' => 'foo',
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
	}

	/**
	 * @dataProvider settingsProvider
	 */
	public function testDefaults( array $settings, array $wg, $repoIsLocal, $expected ) {
		$this->setMwGlobals( $wg );
		$this->clearHook( 'WikibaseRepoEntityNamespaces' );

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
