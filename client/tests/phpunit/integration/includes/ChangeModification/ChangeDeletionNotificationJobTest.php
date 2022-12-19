<?php

declare( strict_types=1 );
namespace Wikibase\Client\Tests\Integration\ChangeModification;

use MediaWiki\MediaWikiServices;
use Wikibase\Client\ChangeModification\ChangeDeletionNotificationJob;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;

/**
 * @covers \Wikibase\Client\ChangeModification\ChangeDeletionNotificationJob
 *
 * @group Wikibase
 * @group WikibaseChange
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class ChangeDeletionNotificationJobTest extends RecentChangesModificationTest {

	private function countRevisions( array $conditions = null ): int {
		return $this->db->selectRowCount(
			'recentchanges',
			'*',
			$conditions,
			__METHOD__
		);
	}

	private function assertRevisionsDeleted( array $expectedDeleted ): void {
		if ( !$expectedDeleted ) {
			return;
		}

		$deletedRevisionsCount = $this->countRevisions( [ 'rc_title' => $expectedDeleted ] );

		$this->assertSame( 0, $deletedRevisionsCount, 'Unique revisions failed to delete' );
	}

	private function assertOthersUntouched( int $beforeDelCount, array $expectedDeleted ): void {
		$revisionCount = $this->countRevisions();
		$expectedCount = $beforeDelCount - count( $expectedDeleted );

		$this->assertSame( $expectedCount, $revisionCount, 'Too many revisions deleted' );
	}

	private function getClientDomainDb(): ClientDomainDb {
		$mwServices = MediaWikiServices::getInstance();
		$lbFactory = $mwServices->getDBLoadBalancerFactory();

		return ( new ClientDomainDbFactory( $lbFactory ) )->newLocalDb();
	}

	public function testToString(): void {
		$job = new ChangeDeletionNotificationJob(
			$this->getClientDomainDb(),
			MediaWikiServices::getInstance()->getMainConfig()->get( 'UpdateRowsPerQuery' ),
			[
				'revisionIdentifiersJson' => $this->revisionIdentifiersToJson( [
					new RepoRevisionIdentifier( 'Q1', '1', 1 ),
				] ),
			]
		);

		$this->assertMatchesRegularExpression( '/^ChangeDeletionNotification/', $job->toString() );
	}

	/**
	 * @dataProvider revisionIdentifierProvider
	 */
	public function testRun( array $expectedDeleted, array $revisionIdentifiers ): void {
		$this->initRecentChanges();
		$beforeCount = $this->countRevisions();

		$job = new ChangeDeletionNotificationJob(
			$this->getClientDomainDb(),
			MediaWikiServices::getInstance()->getMainConfig()->get( 'UpdateRowsPerQuery' ),
			[
				'revisionIdentifiersJson' => $this->revisionIdentifiersToJson( $revisionIdentifiers ),
			]
		);
		$job->run();

		$this->assertRevisionsDeleted( $expectedDeleted );
		$this->assertOthersUntouched( $beforeCount, $expectedDeleted );
	}
}
