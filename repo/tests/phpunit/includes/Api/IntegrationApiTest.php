<?php

namespace Wikibase\Repo\Tests\Api;

use MediaWiki\MediaWikiServices;
use MediaWiki\Tests\Api\ApiTestCase;
use MediaWiki\Tests\Site\TestSites;

/**
 * This class holds simple integration tests for Wikibase API modules
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group BreakingTheSlownessBarrier
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @coversNothing
 */
class IntegrationApiTest extends ApiTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->setupSiteLinkGroups();

		$sitesTable = MediaWikiServices::getInstance()->getSiteStore();
		$sitesTable->clear();
		$sitesTable->saveSites( TestSites::getSites() );
	}

	private function setupSitelinkGroups() {
		$customRepoSettings = $this->getConfVar( 'WBRepoSettings' );
		$customRepoSettings['siteLinkGroups'] = [ 'wikipedia' ];
		$this->overrideConfigValue( 'WBRepoSettings', $customRepoSettings );
	}

	public static function apiRequestProvider() {
		return [
			'wbgetentities-id' => [
				[
					'action' => 'wbgetentities',
					'ids' => 'Q2147483647',
				],
				[
					'entities' => [
						'Q2147483647' => [
							'id' => 'Q2147483647',
							'missing' => '',
						],
					],
					'success' => 1,
				],
			],
			'wbgetentities-sitetitle' => [
				[
					'action' => 'wbgetentities',
					'sites' => 'enwiki',
					'titles' => 'FooBarBazBazBaz1111211',
				],
				[
					'entities' => [
						'-1' => [
							'site' => 'enwiki',
							'title' => 'FooBarBazBazBaz1111211',
							'missing' => '',
						],
					],
					'success' => 1,
				],
			],
		];
	}

	/**
	 * @dataProvider apiRequestProvider
	 */
	public function testApiModuleResult( $params, $expected ) {
		[ $result ] = $this->doApiRequest( $params );
		$this->assertEquals( $expected, $result );
	}

}
