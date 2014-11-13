<?php

namespace Wikibase\Test\Api;

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
 * @author Adam Shorland
 */
class IntegrationApiTest extends \ApiTestCase {

	public function setUp() {
		parent::setUp();
		$sitesTable = WikibaseRepo::getDefaultInstance()->getSiteStore();
		$sitesTable->clear();
		$sitesTable->saveSites( \TestSites::getSites() );
	}

	public function apiRequestProvider() {
		return array(
			'wbgetentities-id' => array(
				array(
					'action' => 'wbgetentities',
					'ids' => 'Q919191919191',
				),
				array(
					'entities' => array(
						'Q919191919191' => array(
							'id' => 'Q919191919191',
							'missing' => '',
						),
					),
					'success' => 1,
				),
			),
			'wbgetentities-sitetitle' => array(
				array(
					'action' => 'wbgetentities',
					'sites' => 'enwiki',
					'titles' => 'FooBarBazBazBaz1111211',
				),
				array(
					'entities' => array(
						'-1' => array(
							'site' => 'enwiki',
							'title' => 'FooBarBazBazBaz1111211',
							'missing' => '',
						),
					),
					'success' => 1,
				),
			),
		);
	}

	/**
	 * @dataProvider apiRequestProvider
	 */
	public function testApiModuleResult( $params, $expected ) {
		list( $result ) = $this->doApiRequest( $params );
		$this->assertEquals( $expected, $result );
	}

}
