<?php

namespace Wikibase\Repo\Tests;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\ObservableMessageReporter;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Store\Sql\SqlChangeStore;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLBFactory;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLoadBalancer;
use Wikibase\Repo\ChangePruner;

/**
 * @covers \Wikibase\Repo\ChangePruner
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangePrunerTest extends MediaWikiIntegrationTestCase {

	private $messages = [];

	private $loadBalancer;
	private $lbFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->loadBalancer = new FakeLoadBalancer( [ 'dbr' => $this->db ] );
		$this->lbFactory = new FakeLBFactory( [ 'lb' => $this->loadBalancer ] );
	}

	public function testConstructorWithInvalidBatchSize() {
		$this->expectException( InvalidArgumentException::class );
		new ChangePruner( $this->lbFactory, 0, 0, 0, false );
	}

	public function testConstructorWithInvalidKeepSeconds() {
		$this->expectException( InvalidArgumentException::class );
		new ChangePruner( $this->lbFactory, 1, -1, 0, false );
	}

	public function testConstructorWithInvalidGraceSeconds() {
		$this->expectException( InvalidArgumentException::class );
		new ChangePruner( $this->lbFactory, 1, 0, -1, false );
	}

	public function testPrune() {
		$pruner = new ChangePruner( $this->lbFactory, 1, 1, 1, false );

		$this->db->delete( 'wb_changes', '*' );

		$this->assertSame( 0, $this->db->selectRowCount( 'wb_changes' ),
			'sanity check: wb_changes table is empty' );

		$this->addTestChanges();
		$this->assertEquals( 2, $this->db->selectRowCount( 'wb_changes' ),
			'sanity check: 2 changes added to wb_changes'
		);

		$pruner->setMessageReporter( $this->newMessageReporter() );
		$pruner->prune();

		$this->assertCount( 6, $this->messages, 'pruner has reported 6 messages' );

		$this->assertStringContainsString( 'pruning entries older than 2015-01-01T00:00:06Z', $this->messages[0] );
		$this->assertStringContainsString( '1 rows pruned', $this->messages[1] );
		$this->assertStringContainsString( 'pruning entries older than 2015-01-01T00:03:01Z', $this->messages[2] );
		$this->assertStringContainsString( '1 rows pruned', $this->messages[3] );
		$this->assertStringContainsString( '0 rows pruned', $this->messages[5] );

		$this->assertSame( 0, $this->db->selectRowCount( 'wb_changes' ), 'wb_changes table is empty' );
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
