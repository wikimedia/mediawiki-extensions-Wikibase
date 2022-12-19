<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\ChangeModification;

use MediaWiki\MediaWikiServices;
use Title;
use Wikibase\Client\ChangeModification\ChangeVisibilityNotificationJob;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\SiteLinkCommentCreator;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \Wikibase\Client\ChangeModification\ChangeVisibilityNotificationJob
 *
 * @group Wikibase
 * @group WikibaseChange
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ChangeVisibilityNotificationJobTest extends RecentChangesModificationTest {

	/**
	 * @dataProvider revisionIdentifierProvider
	 */
	public function testRun( array $expectedRedactedTitles, array $revisionIdentifiers, $visibilityBitFlag ) {
		$this->initRecentChanges();

		$job = new ChangeVisibilityNotificationJob(
			$this->getClientDomainDb(),
			MediaWikiServices::getInstance()->getMainConfig()->get( 'UpdateRowsPerQuery' ),
			[
				'revisionIdentifiersJson' => $this->revisionIdentifiersToJson( $revisionIdentifiers ),
				'visibilityBitFlag' => $visibilityBitFlag,
			]
		);
		$job->run();

		$this->assertTitlesRedacted( $expectedRedactedTitles, $visibilityBitFlag );
		$this->assertOtherTitlesUntouched( $expectedRedactedTitles );
	}

	public function testRun_recentChangeFactoryRoundtrip() {
		$this->initRecentChanges();

		$recentChangeFactory = new RecentChangeFactory(
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' ),
			$this->createMock( SiteLinkCommentCreator::class ),
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			$this->createStub( ClientDomainDb::class )
		);
		$entityChangeFactory = new EntityChangeFactory(
			$this->createMock( EntityDiffer::class ),
			$this->createMock( EntityIdParser::class ),
			[]
		);

		$entityChange = $entityChangeFactory->newForEntity(
			'blah',
			new NumericPropertyId( 'P42' ),
			[ 'user_text' => 'a-nice-user' ]
		);
		$entityChange->setTimestamp( '20161111111111' );
		$entityChange->setMetadata( [
			'rev_id' => 342,
			'parent_id' => 343,
		] );
		$additionalRecentChange = $recentChangeFactory->newRecentChange(
			$entityChange,
			Title::makeTitle( NS_MAIN, 'UNIQ-FROM-RecentChangeFactory' )
		);
		$additionalRecentChange->save();

		$job = new ChangeVisibilityNotificationJob(
			$this->getClientDomainDb(),
			MediaWikiServices::getInstance()->getMainConfig()->get( 'UpdateRowsPerQuery' ),
			[
				'revisionIdentifiersJson' => $this->revisionIdentifiersToJson( [
					new RepoRevisionIdentifier( 'P42', '20161111111111', 342, 343 ),
				] ),
				'visibilityBitFlag' => 16,
			]
		);
		$job->run();

		$this->assertTitlesRedacted( [ 'UNIQ-FROM-RecentChangeFactory' ], 16 );
		$this->assertOtherTitlesUntouched( [ 'UNIQ-FROM-RecentChangeFactory' ] );
	}

	/**
	 * Make sure rows for titles that are in $expectedRedactedTitles correctly got changed.
	 */
	private function assertTitlesRedacted( array $expectedRedactedTitles, $visibilityBitFlag ) {
		if ( !$expectedRedactedTitles ) {
			$this->assertTrue( (bool)"Nothing to do" );
			return;
		}

		$rcRedactedCount = $this->db->selectRowCount(
			'recentchanges',
			'rc_deleted',
			[
				'rc_title' => $expectedRedactedTitles,
				'rc_deleted' => $visibilityBitFlag,
			],
			__METHOD__
		);
		$this->assertSame( count( $expectedRedactedTitles ), $rcRedactedCount, 'Missing rc_deleted changes.' );
	}

	/**
	 * Make sure rows for titles that are not in $expectedRedactedTitles didn't get changed.
	 */
	private function assertOtherTitlesUntouched( array $expectedRedactedTitles ) {
		// Count the rows that have rc_deleted set that are not in $expectedRedactedTitles
		$where = [ 'rc_deleted > 0' ];
		if ( $expectedRedactedTitles ) {
			$where[] = 'rc_title NOT IN (' . $this->db->makeList( $expectedRedactedTitles ) . ')';
		}

		$rcFalsePositiveCount = $this->db->selectRowCount(
			'recentchanges',
			'rc_deleted',
			$where,
			__METHOD__
		);

		$this->assertSame( 0, $rcFalsePositiveCount, 'Unexpected rc_deleted changes.' );
	}

	public function testToString() {
		$job = new ChangeVisibilityNotificationJob(
			$this->getClientDomainDb(),
			MediaWikiServices::getInstance()->getMainConfig()->get( 'UpdateRowsPerQuery' ),
			[
				'revisionIdentifiersJson' => $this->revisionIdentifiersToJson( [
					new RepoRevisionIdentifier( 'Q1', '1', 1, 1 ),
				] ),
				'visibilityBitFlag' => 6,
			]
		);

		$this->assertMatchesRegularExpression( '/^ChangeVisibilityNotification/', $job->toString() );
	}

	private function getClientDomainDb(): ClientDomainDb {
		$mwServices = MediaWikiServices::getInstance();
		$lbFactory = $mwServices->getDBLoadBalancerFactory();

		return ( new ClientDomainDbFactory( $lbFactory ) )->newLocalDb();
	}
}
