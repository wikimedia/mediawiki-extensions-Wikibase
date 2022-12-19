<?php

namespace Wikibase\Tests;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\WikibaseSettings;

/**
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @coversNothing
 */
class ClientDefaultsTest extends MediaWikiIntegrationTestCase {

	public function settingsProvider(): iterable {
		yield 'no local repo, all values set' => [
			[ // $settings
				'repoUrl' => 'http://acme.com',
				'repoArticlePath' => '/wiki',
				'repoScriptPath' => '/w',
				'siteGlobalID' => 'mywiki',
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
		];

		yield 'local repo, all values set' => [
			[ // $settings
				'repoUrl' => 'http://acme.com',
				'repoArticlePath' => '/wiki',
				'repoScriptPath' => '/w',
				'siteGlobalID' => 'mywiki',
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
						'entitySources' => [
							'local' => [
								'repoDatabase' => false,
								'entityNamespaces' => [ 'item' => 303 ],
								'baseUri' => 'http://www.acme.com/entity/',
								'rdfNodeNamespacePrefix' => 'wd',
								'rdfPredicateNamespacePrefix' => '',
								'interwikiPrefix' => '',
							],
						],
					],
				],
				true, // $repoIsLocal
				[ // $expected
					'repoUrl' => 'http://www.acme.com',
					'repoArticlePath' => '/mywiki',
					'repoScriptPath' => '/mediawiki',
					'siteGlobalID' => 'mw_mywiki',
					'entitySources' => [
						'local' => [
							'repoDatabase' => false,
							'entityNamespaces' => [ 'item' => '303/main' ],
							'baseUri' => 'http://www.acme.com/entity/',
							'rdfNodeNamespacePrefix' => 'wd',
							'rdfPredicateNamespacePrefix' => '',
							'interwikiPrefix' => '',
						],
					],
					'sharedCacheKeyPrefix' => 'wikibase_shared/mw_mywiki',
				],
			];
		}

		if ( WikibaseSettings::isRepoEnabled() ) {
			yield 'sharedCacheKeyPrefix explicitly set' => [
				[ // $settings
					'sharedCacheKeyPrefix' => 'wikibase_shared/wikidata_1_25wmf24',
				],
				[ // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBname' => 'mw_mywiki',
					'wgWBRepoSettings' => [
						'localEntitySourceName' => 'local',
						'entitySources' => [
							'local' => [
								'repoDatabase' => false,
								'entityNamespaces' => [ 'item' => 303 ],
								'baseUri' => 'http://www.acme.com/entity/',
								'rdfNodeNamespacePrefix' => 'wd',
								'rdfPredicateNamespacePrefix' => '',
								'interwikiPrefix' => '',
							],
						],
					],
				],
				true, // $repoIsLocal
				[ // $expected
					'repoUrl' => 'http://www.acme.com',
					'repoArticlePath' => '/mywiki',
					'repoScriptPath' => '/mediawiki',
					'siteGlobalID' => 'mw_mywiki',
					'sharedCacheKeyPrefix' => 'wikibase_shared/wikidata_1_25wmf24',
				],
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
			$this->assertSame( $exp, $actual, "Setting $key" );
		}
	}

}
