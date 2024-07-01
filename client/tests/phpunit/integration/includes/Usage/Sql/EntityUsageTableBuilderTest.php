<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\Usage\Sql;

use MediaWikiIntegrationTestCase;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\Client\Usage\Sql\EntityUsageTable;
use Wikibase\Client\Usage\Sql\EntityUsageTableBuilder;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;
use Wikibase\Lib\Rdbms\DomainDb;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikimedia\Rdbms\LBFactorySingle;

/**
 * @covers \Wikibase\Client\Usage\Sql\EntityUsageTableBuilder
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityUsageTableBuilderTest extends MediaWikiIntegrationTestCase {

	public function testFillUsageTable(): void {
		$this->putWikidataItemPageProps( [
			11 => 'Q11',
			22 => 'Q22',
			33 => 'Q33',
			44 => 'Q44',
			88 => '',
			99 => '--broken--',
		] );

		$domainDbFactory = new ClientDomainDbFactory(
			LBFactorySingle::newFromConnection( $this->db ),
			[ DomainDb::LOAD_GROUP_FROM_CLIENT ]
		);

		$primer = new EntityUsageTableBuilder(
			new ItemIdParser(),
			$domainDbFactory->newLocalDb(),
			2
		);
		$primer->setProgressReporter( $this->getMessageReporter( $this->exactly( 3 ) ) );
		$primer->setExceptionHandler( $this->getExceptionHandler( $this->exactly( 2 ) ) );

		$primer->fillUsageTable();

		$actual = $this->fetchAllUsageStrings();
		ksort( $actual );

		$expected = [
			11 => 'Q11#X',
			22 => 'Q22#X',
			33 => 'Q33#X',
			44 => 'Q44#X',
		];

		$this->assertEquals( $expected, $actual );
	}

	private function putWikidataItemPageProps( array $entries ): void {
		$rows = [];
		foreach ( $entries as $pageId => $entityId ) {
			$rows[] = [
				'pp_page' => (int)$pageId,
				'pp_propname' => 'wikibase_item',
				'pp_value' => (string)$entityId,
			];
		}
		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'page_props' )
			->rows( $rows )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * @return string[]
	 */
	private function fetchAllUsageStrings(): array {
		$res = $this->getDb()->newSelectQueryBuilder()
			->select( [ 'eu_page_id', 'eu_entity_id', 'eu_aspect' ] )
			->from( EntityUsageTable::DEFAULT_TABLE_NAME )
			->caller( __METHOD__ )->fetchResultSet();

		$usages = [];
		foreach ( $res as $row ) {
			$key = (int)$row->eu_page_id;

			$usages[$key] = $row->eu_entity_id . '#' . $row->eu_aspect;
		}

		return $usages;
	}

	private function getExceptionHandler( $matcher ): ExceptionHandler {
		$mock = $this->createMock( ExceptionHandler::class );
		$mock->expects( $matcher )
			->method( 'handleException' );

		return $mock;
	}

	private function getMessageReporter( $matcher ): MessageReporter {
		$mock = $this->createMock( MessageReporter::class );
		$mock->expects( $matcher )
			->method( 'reportMessage' );

		return $mock;
	}

}
