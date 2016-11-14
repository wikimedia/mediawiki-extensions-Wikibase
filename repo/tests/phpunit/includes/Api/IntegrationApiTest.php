<?php

namespace Wikibase\Test\Repo\Api;

use ApiTestCase;
use TestSites;
use Wikibase\Repo\WikibaseRepo;

/**
 * This class holds simple integration tests for Wikibase API modules
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @gorup WikibaseRepo
 * @group BreakingTheSlownessBarrier
 * @group Database
 * @group medium
 *
 * @author Addshore
 */
class IntegrationApiTest extends ApiTestCase {

	protected function setUp() {
		parent::setUp();
		$sitesTable = WikibaseRepo::getDefaultInstance()->getSiteStore();
		$sitesTable->clear();
		$sitesTable->saveSites( TestSites::getSites() );
	}

	public function apiRequestProvider() {
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
		list( $result ) = $this->doApiRequest( $params );
		$this->assertEquals( $expected, $result );
	}

}
