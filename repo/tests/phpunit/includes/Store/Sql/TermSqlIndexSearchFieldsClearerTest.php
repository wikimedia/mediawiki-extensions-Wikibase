<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\Store\Sql\TermSqlIndexSearchFieldsClearer;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \Wikibase\Repo\Store\Sql\TermSqlIndexSearchFieldsClearer
 *
 * @group Wikibase
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Lucas Werkmeister
 */
class TermSqlIndexSearchFieldsClearerTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'wb_terms';
		$this->clearTermsTable();
		$this->insertTerms();
	}

	private function clearTermsTable() {
		wfGetDB( DB_MASTER )->delete( 'wb_terms', '*' );
	}

	private function insertTerms() {
		$terms = [];

		$terms[] = [
			'term_entity_id' => 1,
			'term_full_entity_id' => 'Q1',
			'term_entity_type' => 'item',
			'term_language' => 'de',
			'term_type' => 'label',
			'term_text' => 'Universum',
			'term_search_key' => 'universum',
			'term_weight' => 1.0,
		];

		$terms[] = [
			'term_entity_id' => 42,
			'term_full_entity_id' => 'Q42',
			'term_entity_type' => 'item',
			'term_language' => 'en',
			'term_type' => 'description',
			'term_text' => 'cOmMoNlY uSeD eXaMpLe ItEm',
			'term_search_key' => 'commonly used example item',
			'term_weight' => 0.5,
		];

		$terms[] = [
			'term_entity_id' => 12345,
			'term_full_entity_id' => 'Q12345',
			'term_entity_type' => 'item',
			'term_language' => 'en',
			'term_type' => 'alias',
			'term_text' => 'not an example item',
			'term_search_key' => 'not an example item',
			'term_weight' => 0.1,
		];

		wfGetDB( DB_MASTER )->insert( 'wb_terms', $terms );
	}

	/**
	 * @return TermSqlIndexSearchFieldsClearer
	 */
	private function getClearer() {
		return new TermSqlIndexSearchFieldsClearer(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			0
		);
	}

	/**
	 * @return int
	 */
	private function getUnclearedRowCount() {
		return wfGetDB( DB_REPLICA )->selectRowCount(
			'wb_terms',
			'*',
			'term_search_key != "" OR term_weight != 0.0'
		);
	}

	public function testClear_NoOptions_ClearsEverything() {
		$this->getClearer()->clear();

		$this->assertSame(
			0,
			$this->getUnclearedRowCount(),
			'all rows should be cleared'
		);
	}

	public function testClear_WithFromId_ClearsOnlyFromThatId() {
		$fromId = (int)wfGetDB( DB_REPLICA )->selectField(
			'wb_terms',
			'term_row_id',
			'1',
			__METHOD__,
			[
				'ORDER BY' => 'term_row_id',
				'OFFSET' => 2,
			]
		);
		$clearer = $this->getClearer();
		$clearer->setFromId( $fromId );

		$clearer->clear();

		$this->assertSame(
			2,
			$this->getUnclearedRowCount(),
			'first two rows should not be cleared'
		);
	}

	public function testClear_WithoutClearTermWeight_ClearsOnlyTermSearchKey() {
		$clearer = $this->getClearer();
		$clearer->setClearTermWeight( false );

		$clearer->clear();

		$termSearchKeys = wfGetDB( DB_REPLICA )->selectFieldValues( 'wb_terms', 'term_search_key' );
		$termWeights = wfGetDB( DB_REPLICA )->selectFieldValues( 'wb_terms', 'term_weight' );
		$this->assertSame( [ '', '', '' ], $termSearchKeys );
		$this->assertNotContains( 0.0, $termWeights );
	}

	public function testClear_ReportsProgress() {
		$progress = '';
		$progressReporter = new ObservableMessageReporter();
		$progressReporter->registerReporterCallback(
			function ( $message ) use ( &$progress ) {
				$progress .= $message . "\n";
			}
		);

		$clearer = $this->getClearer();
		$clearer->setBatchSize( 1 );
		$clearer->setProgressReporter( $progressReporter );

		$rowIds = wfGetDB( DB_REPLICA )
			->selectFieldValues( 'wb_terms', 'term_row_id' );

		$clearer->clear();

		foreach ( $rowIds as $rowId ) {
			$this->assertContains(
				(string)$rowId,
				$progress,
				'each batch should mention row IDs processed'
			);
		}
	}

	public function testClearBatch_OnClearedTable_NoUpdates() {
		$dbr = wfGetDB( DB_REPLICA );
		$dbw = $this->getMock( IDatabase::class );
		$dbw->expects( $this->never() )->method( 'update' );

		$clearer = $this->getClearer();
		$clearer->clear();

		$clearer->clearBatch( $dbr, $dbw, 0, 1000 );
	}

}
