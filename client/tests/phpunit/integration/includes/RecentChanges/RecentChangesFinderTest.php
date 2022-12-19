<?php

namespace Wikibase\Client\Tests\Integration\RecentChanges;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use RecentChange;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangesFinder;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;

/**
 * @covers \Wikibase\Client\RecentChanges\RecentChangesFinder
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RecentChangesFinderTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = 'recentchanges';
	}

	public function provideGetRecentChangeId() {
		// Note: this provides change data without rc_user(_text);
		// those fields will be filled in by newChange(),
		// because data providers run before the test DB has been set up
		return [
			'same' => [ true, [
				'rc_id' => 4353,
				'rc_timestamp' => '20111111111111',
				'rc_namespace' => 0,
				'rc_title' => 'Test',
				'rc_comment' => 'Testing',
				'rc_type' => RC_EXTERNAL,
				'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
				'rc_last_oldid' => 11,
				'rc_this_oldid' => 12,
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 1,
						'rev_id' => 2,
					],
				],
			] ],
			'irrelevant differences' => [ true, [
				'rc_id' => 1117,
				'rc_timestamp' => '20111111111111',
				'rc_namespace' => 0,
				'rc_title' => 'Test',
				'rc_comment' => 'Kittens', // ignored
				'rc_type' => RC_EXTERNAL,
				'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
				'rc_last_oldid' => 1111, // ignored
				'rc_this_oldid' => 1112, // ignored
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 1,
						'rev_id' => 2,
					],
				],
			] ],
			'repo change mismatch' => [ false, [
				'rc_id' => 17,
				'rc_timestamp' => '20111111111111',
				'rc_namespace' => 0,
				'rc_title' => 'Test',
				'rc_comment' => 'Testing',
				'rc_type' => RC_EXTERNAL,
				'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
				'rc_last_oldid' => 11,
				'rc_this_oldid' => 12,
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 7,
						'rev_id' => 8,
					],
				],
			] ],
			'local timestamp mismatch' => [ false, [
				'rc_id' => 17,
				'rc_timestamp' => '20111111112233',
				'rc_namespace' => 0,
				'rc_title' => 'Test',
				'rc_comment' => 'Testing',
				'rc_type' => RC_EXTERNAL,
				'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
				'rc_last_oldid' => 11,
				'rc_this_oldid' => 12,
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 1,
						'rev_id' => 2,
					],
				],
			] ],
			'local title mismatch' => [ false, [
				'rc_id' => 17,
				'rc_timestamp' => '20111111111111',
				'rc_namespace' => 0,
				'rc_title' => 'Kittens',
				'rc_comment' => 'Testing',
				'rc_type' => RC_EXTERNAL,
				'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
				'rc_last_oldid' => 11,
				'rc_this_oldid' => 12,
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 1,
						'rev_id' => 2,
					],
				],
			] ],
			'external change exists but not from repo' => [ false, [
				'rc_id' => 17,
				'rc_timestamp' => '20121212121212',
				'rc_namespace' => 0,
				'rc_title' => 'Kittens',
				'rc_comment' => 'Testing',
				'rc_type' => RC_EXTERNAL,
				'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
				'rc_last_oldid' => 11,
				'rc_this_oldid' => 12,
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 11,
						'rev_id' => 12,
					],
				],
			] ],
		];
	}

	/**
	 * @dataProvider provideGetRecentChangeId
	 */
	public function testGetRecentChangeId( $expected, array $changeData ) {
		$connectionManager = new SessionConsistentConnectionManager(
			MediaWikiServices::getInstance()->getDBLoadBalancer()
		);
		$detector = new RecentChangesFinder( $connectionManager );

		$relevantId = $this->initRecentChanges();

		$change = $this->newChange( $changeData );

		if ( $expected ) {
			$this->assertSame( $relevantId, $detector->getRecentChangeId( $change ) );
		} else {
			$this->assertNull( $detector->getRecentChangeId( $change ) );
		}
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
			'pageStatus' => 'changed',
		] );

		return $change;
	}

	private function initRecentChanges() {
		$change = $this->newChange( [
			'rc_timestamp' => '20111111111111',
			'rc_namespace' => 0,
			'rc_title' => 'Test',
			'rc_comment' => 'Testing',
			'rc_type' => RC_EXTERNAL,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_last_oldid' => 11,
			'rc_this_oldid' => 12,
			'rc_params' => [
				'wikibase-repo-change' => [
					'parent_id' => 1001, // different id
					'rev_id' => 1002, // different id
				],
			],
		] );
		$change->save();

		$change = $this->newChange( [
			'rc_timestamp' => '20121212121212',
			'rc_namespace' => 0,
			'rc_title' => 'Test',
			'rc_comment' => 'Testing',
			'rc_type' => RC_EXTERNAL,
			'rc_source' => 'foo', // different source
			'rc_last_oldid' => 11,
			'rc_this_oldid' => 12,
			'rc_params' => [
				'wikibase-repo-change' => [
					'parent_id' => 11,
					'rev_id' => 12,
				],
			],
		] );
		$change->save();

		$change = $this->newChange( [
			'rc_timestamp' => '20111111111111',
			'rc_namespace' => 0,
			'rc_title' => 'Test',
			'rc_comment' => 'Testing',
			'rc_type' => RC_EXTERNAL,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_last_oldid' => 11,
			'rc_this_oldid' => 12,
			'rc_params' => [
				'wikibase-repo-change' => [
					'parent_id' => 1,
					'rev_id' => 2,
				],
			],
		] );
		$change->save();

		return $change->getAttribute( 'rc_id' );
	}

}
