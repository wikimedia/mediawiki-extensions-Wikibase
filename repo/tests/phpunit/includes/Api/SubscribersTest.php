<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use ApiQuery;
use FauxRequest;
use MediaWikiLangTestCase;
use RequestContext;
use Wikibase\Repo\Api\Subscribers;

/**
 * @covers Wikibase\Repo\Api\Subscribers
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 * @group Database
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani
 */
class ApiSubscribersTest extends MediaWikiLangTestCase {

	protected function setUp() {
		$this->tablesUsed[] = 'wb_changes_subscription';
		parent::setUp();
	}

	public function addDBData() {
		$db = wfGetDB( DB_MASTER );
		$dump = [
			'wb_changes_subscription' => [
				[
					'cs_entity_id' => 'Q3',
					'cs_subscriber_id' => 'enwiki'
				],
				[
					'cs_entity_id' => 'Q3',
					'cs_subscriber_id' => 'dewiki'
				],
				[
					'cs_entity_id' => 'Q4',
					'cs_subscriber_id' => 'dewiki'
				],
				[
					'cs_entity_id' => 'Q5',
					'cs_subscriber_id' => 'fawiki'
				],
			],
		];

		foreach ( $dump as $table => $rows ) {
			// Clean everything
			$db->delete( $table, '*' );

			foreach ( $rows as $row ) {
				$db->insert( $table, $row );
			}
		}
	}

	/**
	 * @param array $params
	 *
	 * @return ApiQuery
	 */
	private function getQueryModule( array $params ) {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $params, true ) );

		$main = new ApiMain( $context );

		$query = new ApiQuery( $main, $params['action'] );

		return $query;
	}

	/**
	 * @param array $params
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params ) {
		$module = new Subscribers(
			$this->getQueryModule( $params ),
			'subscribers'
		);

		$module->execute();

		$result = $module->getResult();
		$data = $result->getResultData( null, [
			'BC' => [],
			'Types' => [],
			'Strip' => 'all',
		] );
		return $data;
	}

	public function subscribersProvider() {
		return [
			'only Q3' => [
				[
					'action' => 'query',
					'query' => 'subscribers',
					'wblsentities' => 'Q3',
				],
				[
					"Q3" => [
						'subscribers' =>
						[
							[ '*' => 'dewiki' ],
					        [ '*' => 'enwiki' ]
						]
					]
				],
			],
			'two wikis in two entities' => [
				[
					'action' => 'query',
					'query' => 'subscribers',
					'wblsentities' => 'Q3|Q5',
				],
				[
					"Q3" => [
						'subscribers' =>
							[
								[ '*' => 'dewiki' ],
								[ '*' => 'enwiki' ]
							]
					],
					"Q5" => [
						'subscribers' =>
							[
								[ '*' => 'fawiki' ]
							]
					]
				],
			],
			'continue' => [
				[
					'action' => 'query',
					'query' => 'subscribers',
					'wblsentities' => 'Q3|Q5',
					'wblscontinue' => 'Q3|enwiki',
				],
				[
					"Q3" => [
						'subscribers' =>
							[
								[ '*' => 'enwiki' ]
							]
					],
					"Q5" => [
						'subscribers' =>
							[
								[ '*' => 'fawiki' ]
							]
					]
				],
			],
		];
	}

	/**
	 * @dataProvider subscribersProvider
	 */
	public function testSubscribers( array $params, array $expected ) {
		$result = $this->callApiModule( $params );

		if ( isset( $result['error'] ) ) {
			$this->fail( 'API error: ' . print_r( $result['error'], true ) );
		}

		$this->assertArrayHasKey( 'query', $result );
		$this->assertArrayHasKey( 'subscribers', $result['query'] );
		$this->assertSame( $expected, $result['query']['subscribers'] );
	}

}
