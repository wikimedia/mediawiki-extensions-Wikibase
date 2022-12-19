<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\ChangeModification;

use MediaWikiIntegrationTestCase;
use RecentChange;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;

/**
 * Extend this class in order to set up integration tests against the
 * `recentchanges` table
 * @license GPL-2.0-or-later
 */
abstract class RecentChangesModificationTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = 'recentchanges';
	}

	/**
	 * JSON encode the given RepoRevisionIdentifiers
	 *
	 * @param RepoRevisionIdentifier[] $revisionIdentifiers
	 *
	 * @return string JSON
	 */
	protected function revisionIdentifiersToJson( array $revisionIdentifiers ): string {
		return json_encode(
			array_map(
				function ( RepoRevisionIdentifier $revisionIdentifier ) {
					return $revisionIdentifier->toArray();
				},
				$revisionIdentifiers
			)
		);
	}

	public function revisionIdentifierProvider() {
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
					new RepoRevisionIdentifier( 'Q42', '20111111111111', 1002 ),
				],
				6,
			],
			'redact one entry with two RepoRevisionIdentifiers' => [
				[ 'UNIQ-001' ],
				[
					new RepoRevisionIdentifier( 'Q42', '20111111111111', 1002 ),
					new RepoRevisionIdentifier( 'Q45345', '20111111111111', 1002 ),
				],
				6,
			],
			'redact two entries with one RepoRevisionIdentifier' => [
				[ 'UNIQ-005', 'UNIQ-006' ],
				[
					new RepoRevisionIdentifier( 'Q2013', '20111111111115', 2013 ),
				],
				12,
			],
			'redact multiple entries with multiple RepoRevisionIdentifiers' => [
				[ 'UNIQ-001', 'UNIQ-005', 'UNIQ-006' ],
				[
					new RepoRevisionIdentifier( 'Q42', '20111111111111', 1002 ),
					new RepoRevisionIdentifier( 'Q45345', '20111111111111', 1002 ),
					new RepoRevisionIdentifier( 'Q2013', '20111111111115', 2013 ),
				],
				4,
			],
		];
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
			'rc_comment' => 'Testing',
			'rc_minor' => false,
			'rc_bot' => false,
			'rc_new' => false,
			'rc_cur_id' => 0,
			'rc_last_oldid' => 11,
			'rc_this_oldid' => 12,
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

	/**
	 * Empty the recentchanges table and put some changes in there.
	 *
	 * All changes have a unique rc_title value to make them easy to identify.
	 */
	protected function initRecentChanges() {
		$this->db->delete( 'recentchanges', '*' );
		$changes = [
			[
				'rc_timestamp' => '20111111111111',
				'rc_title' => 'UNIQ-001',
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 1001,
						'rev_id' => 1002,
						'object_id' => 'Q42',
					],
				],
			],
			[
				'rc_timestamp' => '20111111111111',
				'rc_title' => 'UNIQ-002',
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 1001,
						'rev_id' => 1002,
						'object_id' => 'Q24',
					],
				],
			],
			[
				'rc_timestamp' => '20121212121212',
				'rc_title' => 'UNIQ-003',
				'rc_source' => 'foo', // different source, will always be ignored
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 1001,
						'rev_id' => 1002,
						'object_id' => 'Q42',
					],
				],
			],
			[
				'rc_timestamp' => '20111111111111',
				'rc_title' => 'UNIQ-004',
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 1,
						'rev_id' => 2,
						'object_id' => 'Q42',
					],
				],
			],
			[
				'rc_timestamp' => '20111111111115',
				'rc_title' => 'UNIQ-005',
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 2014,
						'rev_id' => 2013,
						'object_id' => 'Q2013',
					],
				],
			],
			[
				'rc_timestamp' => '20111111111115',
				'rc_title' => 'UNIQ-006',
				'rc_params' => [
					'wikibase-repo-change' => [
						'parent_id' => 2014,
						'rev_id' => 2013,
						'object_id' => 'Q2013',
					],
				],
			],
		];

		foreach ( $changes as $changeData ) {
			$this->newChange( $changeData )->save();
		}
	}

}
