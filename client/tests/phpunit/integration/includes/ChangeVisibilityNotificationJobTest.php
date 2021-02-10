<?php

namespace Wikibase\Client\Tests;

use Language;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use RecentChange;
use Title;
use Wikibase\Client\ChangeVisibilityNotificationJob;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\SiteLinkCommentCreator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;

/**
 * @covers \Wikibase\Client\ChangeVisibilityNotificationJob
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ChangeVisibilityNotificationJobTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = 'recentchanges';
	}

	public function visibilityNotificationProvider() {
		return [
			'redact nothing' => [
				[],
				[
					new RepoRevisionIdentifier( 'Q404', '20111111111111', 1002, 1001 ),
				],
				6,
			],
			'redact one entry with one RepoRevisionIdentifier' => [
				[ 'UNIQ-001' ],
				[
					new RepoRevisionIdentifier( 'Q42', '20111111111111', 1002, 1001 ),
				],
				6,
			],
			'redact one entry with two RepoRevisionIdentifiers' => [
				[ 'UNIQ-001' ],
				[
					new RepoRevisionIdentifier( 'Q42', '20111111111111', 1002, 1001 ),
					new RepoRevisionIdentifier( 'Q45345', '20111111111111', 1002, 1001 ),
				],
				6,
			],
			'redact two entries with one RepoRevisionIdentifier' => [
				[ 'UNIQ-005', 'UNIQ-006' ],
				[
					new RepoRevisionIdentifier( 'Q2013', '20111111111115', 2013, 2014 ),
				],
				12,
			],
			'redact multiple entries with multiple RepoRevisionIdentifiers' => [
				[ 'UNIQ-001', 'UNIQ-005', 'UNIQ-006' ],
				[
					new RepoRevisionIdentifier( 'Q42', '20111111111111', 1002, 1001 ),
					new RepoRevisionIdentifier( 'Q45345', '20111111111111', 1002, 1001 ),
					new RepoRevisionIdentifier( 'Q2013', '20111111111115', 2013, 2014 ),
				],
				4,
			],
		];
	}

	/**
	 * @dataProvider visibilityNotificationProvider
	 */
	public function testRun( array $expectedRedactedTitles, array $revisionIdentifiers, $visibilityBitFlag ) {
		$this->initRecentChanges();

		$job = new ChangeVisibilityNotificationJob(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			[
				'revisionIdentifiersJson' => $this->revisionIdentifiersToJson( $revisionIdentifiers ),
				'visibilityBitFlag' => $visibilityBitFlag
			]
		);
		$job->run();

		$this->assertTitlesRedacted( $expectedRedactedTitles, $visibilityBitFlag );
		$this->assertOtherTitlesUntouched( $expectedRedactedTitles );
	}

	/**
	 * JSON encode the given RepoRevisionIdentifiers
	 *
	 * @param RepoRevisionIdentifier[] $revisionIdentifiers
	 *
	 * @return string JSON
	 */
	private function revisionIdentifiersToJson( array $revisionIdentifiers ): string {
		return json_encode(
			array_map(
				function ( RepoRevisionIdentifier $revisionIdentifier ) {
					return $revisionIdentifier->toArray();
				},
				$revisionIdentifiers
			)
		);
	}

	public function testRun_recentChangeFactoryRoundtrip() {
		$this->initRecentChanges();

		$recentChangeFactory = new RecentChangeFactory(
			Language::factory( 'qqx' ),
			$this->createMock( SiteLinkCommentCreator::class )
		);
		$entityChangeFactory = new EntityChangeFactory(
			$this->createMock( EntityDiffer::class ),
			$this->createMock( EntityIdParser::class ),
			[]
		);

		$entityChange = $entityChangeFactory->newForEntity(
			'blah',
			new PropertyId( 'P42' ),
			[ 'user_text' => 'a-nice-user' ]
		);
		$entityChange->setTimestamp( '20161111111111' );
		$entityChange->setMetadata( [
			'rev_id' => 342,
			'parent_id' => 343
		] );
		$additionalRecentChange = $recentChangeFactory->newRecentChange(
			$entityChange,
			Title::newFromText( 'UNIQ-FROM-RecentChangeFactory' )
		);
		$additionalRecentChange->save();

		$job = new ChangeVisibilityNotificationJob(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			[
				'revisionIdentifiersJson' => $this->revisionIdentifiersToJson( [
					new RepoRevisionIdentifier( 'P42', '20161111111111', 342, 343 ),
				] ),
				'visibilityBitFlag' => 16
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

		$dbr = wfGetDB( DB_REPLICA );

		$rcRedactedCount = $dbr->selectRowCount(
			'recentchanges',
			'rc_deleted',
			[
				'rc_title' => $expectedRedactedTitles,
				'rc_deleted' => $visibilityBitFlag
			],
			__METHOD__
		);
		$this->assertSame( count( $expectedRedactedTitles ), $rcRedactedCount, 'Missing rc_deleted changes.' );
	}

	/**
	 * Make sure rows for titles that are not in $expectedRedactedTitles didn't get changed.
	 */
	private function assertOtherTitlesUntouched( array $expectedRedactedTitles ) {
		$dbr = wfGetDB( DB_REPLICA );

		// Count the rows that have rc_deleted set that are not in $expectedRedactedTitles
		$where = [ 'rc_deleted > 0' ];
		if ( $expectedRedactedTitles ) {
			$where[] = 'rc_title NOT IN (' . $dbr->makeList( $expectedRedactedTitles ) . ')';
		}

		$rcFalsePositiveCount = $dbr->selectRowCount(
			'recentchanges',
			'rc_deleted',
			$where,
			__METHOD__
		);

		$this->assertSame( 0, $rcFalsePositiveCount, 'Unexpected rc_deleted changes.' );
	}

	public function testToString() {
		$job = new ChangeVisibilityNotificationJob(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			[
				'revisionIdentifiersJson' => $this->revisionIdentifiersToJson( [
					new RepoRevisionIdentifier( 'Q1', '1', 1, 1 ),
				] ),
				'visibilityBitFlag' => 6
			]
		);

		$this->assertRegExp( '/^ChangeVisibilityNotification/', $job->toString() );
	}

	private function newChange( array $changeData ) {
		if ( isset( $changeData['rc_params'] ) && !is_string( $changeData['rc_params'] ) ) {
			$changeData['rc_params'] = serialize( $changeData['rc_params'] );
		}

		$user = $this->getTestUser()->getUser();

		$defaults = [
			'rc_id' => 0,
			'rc_timestamp' => '20000000000000',
			'rc_user' => $user->getId(),
			'rc_user_text' => $user->getName(),
			'rc_namespace' => 0,
			'rc_title' => '',
			'rc_comment' => '',
			'rc_minor' => false,
			'rc_bot' => false,
			'rc_new' => false,
			'rc_cur_id' => 0,
			'rc_this_oldid' => 0,
			'rc_last_oldid' => 0,
			'rc_type' => RC_EXTERNAL,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_patrolled' => 0,
			'rc_ip' => '127.0.0.1',
			'rc_old_len' => 0,
			'rc_new_len' => 0,
			'rc_deleted' => false,
			'rc_logid' => 0,
			'rc_log_type' => null,
			'rc_log_action' => '',
			'rc_params' => '',
		];

		$changeData = array_merge( $defaults, $changeData );

		// The faked-up RecentChange row needs to have the proper fields for
		// MediaWiki core change Ic3a434c0. And can't have them without that
		// patch or the ->save() in initRecentChanges() will fail.
		$changeData += [
			'rc_comment_text' => $changeData['rc_comment'],
			'rc_comment_data' => null,
		];

		$change = RecentChange::newFromRow( (object)$changeData );
		$change->setExtra( [
			'pageStatus' => 'changed'
		] );

		return $change;
	}

	/**
	 * Empty the recentchanges table and put some changes in there.
	 *
	 * All changes have a unique rc_title value to make them easy to identify.
	 */
	private function initRecentChanges() {
		wfGetDB( DB_MASTER )->delete( 'recentchanges', '*' );

		$change = $this->newChange( [
			'rc_timestamp' => '20111111111111',
			'rc_namespace' => 0,
			'rc_title' => 'UNIQ-001',
			'rc_comment' => 'Testing',
			'rc_type' => RC_EXTERNAL,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_last_oldid' => 11,
			'rc_this_oldid' => 12,
			'rc_params' => [
				'wikibase-repo-change' => [
					'parent_id' => 1001,
					'rev_id' => 1002,
					'object_id' => 'Q42'
				]
			]
		] );
		$change->save();

		$change = $this->newChange( [
			'rc_timestamp' => '20111111111111',
			'rc_namespace' => 0,
			'rc_title' => 'UNIQ-002',
			'rc_comment' => 'Testing',
			'rc_type' => RC_EXTERNAL,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_last_oldid' => 11,
			'rc_this_oldid' => 12,
			'rc_params' => [
				'wikibase-repo-change' => [
					'parent_id' => 1001,
					'rev_id' => 1002,
					'object_id' => 'Q24'
				]
			]
		] );
		$change->save();

		$change = $this->newChange( [
			'rc_timestamp' => '20121212121212',
			'rc_namespace' => 0,
			'rc_title' => 'UNIQ-003',
			'rc_comment' => 'Testing',
			'rc_type' => RC_EXTERNAL,
			'rc_source' => 'foo', // different source, will always be ignored
			'rc_last_oldid' => 11,
			'rc_this_oldid' => 12,
			'rc_params' => [
				'wikibase-repo-change' => [
					'parent_id' => 1001,
					'rev_id' => 1002,
					'object_id' => 'Q42'
				]
			]
		] );
		$change->save();

		$change = $this->newChange( [
			'rc_timestamp' => '20111111111111',
			'rc_namespace' => 0,
			'rc_title' => 'UNIQ-004',
			'rc_comment' => 'Testing',
			'rc_type' => RC_EXTERNAL,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_last_oldid' => 11,
			'rc_this_oldid' => 12,
			'rc_params' => [
				'wikibase-repo-change' => [
					'parent_id' => 1,
					'rev_id' => 2,
					'object_id' => 'Q42'
				]
			]
		] );
		$change->save();

		$change = $this->newChange( [
			'rc_timestamp' => '20111111111115',
			'rc_namespace' => 0,
			'rc_title' => 'UNIQ-005',
			'rc_comment' => 'Testing',
			'rc_type' => RC_EXTERNAL,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_last_oldid' => 11,
			'rc_this_oldid' => 12,
			'rc_params' => [
				'wikibase-repo-change' => [
					'parent_id' => 2014,
					'rev_id' => 2013,
					'object_id' => 'Q2013'
				]
			]
		] );
		$change->save();

		$change = $this->newChange( [
			'rc_timestamp' => '20111111111115',
			'rc_namespace' => 0,
			'rc_title' => 'UNIQ-006',
			'rc_comment' => 'Testing',
			'rc_type' => RC_EXTERNAL,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_last_oldid' => 11,
			'rc_this_oldid' => 12,
			'rc_params' => [
				'wikibase-repo-change' => [
					'parent_id' => 2014,
					'rev_id' => 2013,
					'object_id' => 'Q2013'
				]
			]
		] );
		$change->save();
	}

}
