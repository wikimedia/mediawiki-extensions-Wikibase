<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use ApiQuery;
use FauxRequest;
use HashSiteStore;
use MediaWikiLangTestCase;
use RequestContext;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Repo\Api\ListSubscribers;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\ListSubscribers
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani
 */
class ListSubscribersTest extends MediaWikiLangTestCase {

	protected function setUp(): void {
		$this->tablesUsed[] = 'wb_changes_subscription';
		parent::setUp();
	}

	public function addDBData() {
		$dump = [
			'wb_changes_subscription' => [
				[
					'cs_entity_id' => 'Q3',
					'cs_subscriber_id' => 'enwiki',
				],
				[
					'cs_entity_id' => 'Q3',
					'cs_subscriber_id' => 'dewiki',
				],
				[
					'cs_entity_id' => 'Q4',
					'cs_subscriber_id' => 'dewiki',
				],
				[
					'cs_entity_id' => 'Q5',
					'cs_subscriber_id' => 'fawiki',
				],
			],
		];

		foreach ( $dump as $table => $rows ) {
			// Clean everything
			$this->db->delete( $table, '*' );

			foreach ( $rows as $row ) {
				$this->db->insert( $table, $row );
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

		$query = $main->getModuleManager()->getModule( 'query' );

		return $query;
	}

	/**
	 * @param array $params
	 *
	 * @return array[]
	 */
	private function callApiModule( array $params ) {
		$apiMain = $this->getQueryModule( $params );
		$errorReporter = WikibaseRepo::getApiHelperFactory()
			->getErrorReporter( $apiMain );
		$module = new ListSubscribers(
			$apiMain,
			'subscribers',
			$errorReporter,
			new ItemIdParser(),
			new HashSiteStore()
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
					'list' => 'wbsubscribers',
					'wblsentities' => 'Q3',
				],
				[
					"Q3" => [
						'subscribers' =>
						[
							[ 'site' => 'dewiki' ],
							[ 'site' => 'enwiki' ],
						],
					],
				],
			],
			'two wikis in two entities' => [
				[
					'action' => 'query',
					'list' => 'wbsubscribers',
					'wblsentities' => 'Q3|Q5',
				],
				[
					"Q3" => [
						'subscribers' =>
							[
								[ 'site' => 'dewiki' ],
								[ 'site' => 'enwiki' ],
							],
					],
					"Q5" => [
						'subscribers' =>
							[
								[ 'site' => 'fawiki' ],
							],
					],
				],
			],
			'continue' => [
				[
					'action' => 'query',
					'list' => 'wbsubscribers',
					'wblsentities' => 'Q3|Q5',
					'wblscontinue' => 'Q3|enwiki',
				],
				[
					"Q3" => [
						'subscribers' =>
							[
								[ 'site' => 'enwiki' ],
							],
					],
					"Q5" => [
						'subscribers' =>
							[
								[ 'site' => 'fawiki' ],
							],
					],
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
