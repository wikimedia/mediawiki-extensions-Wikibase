<?php

namespace Wikibase\Repo\Tests;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use Wikibase\EntityChange;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\ChangePruner;
use Wikibase\Repo\Store\Sql\SqlChangeStore;

/**
 * @covers Wikibase\Repo\ChangePruner
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangePrunerTest extends MediaWikiTestCase {

	private $messages = [];

	public function testConstructorWithInvalidBatchSize() {
		$this->setExpectedException( InvalidArgumentException::class );
		new ChangePruner( 0, 0, 0, false );
	}

	public function testConstructorWithInvalidKeepSeconds() {
		$this->setExpectedException( InvalidArgumentException::class );
		new ChangePruner( 1, -1, 0, false );
	}

	public function testConstructorWithInvalidGraceSeconds() {
		$this->setExpectedException( InvalidArgumentException::class );
		new ChangePruner( 1, 0, -1, false );
	}

	public function testPrune() {
		$pruner = new ChangePruner( 1, 1, 1, false );

		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'wb_changes', '*' );

		$this->assertEquals( 0, $dbw->selectRowCount( 'wb_changes' ),
			'sanity check: wb_changes table is empty' );

		$this->addTestChanges();
		$this->assertEquals( 2, $dbw->selectRowCount( 'wb_changes' ),
			'sanity check: 2 changes added to wb_changes'
		);

		$pruner->setMessageReporter( $this->newMessageReporter() );
		$pruner->prune();

		$this->assertEquals( 6, count( $this->messages ), 'pruner has reported 6 messages' );

		$this->assertContains( 'pruning entries older than 2015-01-01T00:00:06Z', $this->messages[0] );
		$this->assertContains( '1 rows pruned', $this->messages[1] );
		$this->assertContains( 'pruning entries older than 2015-01-01T00:03:01Z', $this->messages[2] );
		$this->assertContains( '1 rows pruned', $this->messages[3] );
		$this->assertContains( '0 rows pruned', $this->messages[5] );

		$this->assertEquals( 0, $dbw->selectRowCount( 'wb_changes' ), 'wb_changes table is empty' );
	}

	private function addTestChanges() {
		$changeStore = new SqlChangeStore( MediaWikiServices::getInstance()->getDBLoadBalancer() );

		$change = new EntityChange( $this->getChangeRowData( '20150101000005' ) );
		$changeStore->saveChange( $change );

		$change = new EntityChange( $this->getChangeRowData( '20150101000300' ) );
		$changeStore->saveChange( $change );
	}

	private function getChangeRowData( $timestamp ) {
		return [
			'type' => 'wikibase-item~update',
			'time' => $timestamp,
			'user_id' => 0,
			'revision_id' => 9002,
			'object_id' => 'Q9000',
			'info' => [ 'diff' => [] ]
		];
	}

	/**
	 * @return MessageReporter
	 */
	private function newMessageReporter() {
		$reporter = new ObservableMessageReporter();

		$reporter->registerReporterCallback(
			function ( $message ) {
				$this->messages[] = $message;
			}
		);

		return $reporter;
	}

}
