<?php

namespace Wikibase\Client\Tests\RecentChanges;

use RecentChange;
use Wikibase\Client\RecentChanges\ExternalChange;
use Wikibase\Client\RecentChanges\ExternalChangeFactory;
use Wikibase\Client\RecentChanges\RevisionData;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\RecentChanges\ExternalChangeFactory
 *
 * @group WikibaseClient
 * @group Database
 * @group medium
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalChangeFactoryTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider newFromRecentChangeProvider
	 */
	public function testNewFromRecentChange( $expected, RecentChange $recentChange ) {
		$externalChangeFactory = new ExternalChangeFactory( 'testrepo' );
		$externalChange = $externalChangeFactory->newFromRecentChange( $recentChange );

		$this->assertEquals( $expected, $externalChange );
	}

	public function newFromRecentChangeProvider() {
		$rev = new RevisionData ( 'Cat', 5, 92, 90, '20130819111741',
			array( 'key' => 'wikibase-comment-update' ), 'testrepo'
		);

		$externalChange = new ExternalChange( new ItemId( 'Q4' ), $rev, 'update' );

		return array(
			array( $externalChange, $this->getEditRecentChange( true ), 'bot edit' ),
			array( $externalChange, $this->getEditRecentChange( false ), 'non bot edit' )
		);
	}

	/**
	 * @param boolean $bot
	 * @return RecentChange
	 */
	protected function getEditRecentChange( $bot ) {
		$recentChange = new RecentChange();
		$recentChange->counter = 2;

		$params = array(
			'wikibase-repo-change' => array(
				'id' => 4,
				'type' => 'wikibase-item~update',
				'time' => '20130819111741',
				'object_id' => 'q4',
				'user_id' => 1,
				'revision_id' => 92,
				'entity_type' => 'item',
				'user_text' => 'Cat',
				'bot' => $bot ? 1 : 0,
				'page_id' => 5,
				'rev_id' => 92,
				'parent_id' => 90,
				'comment' => array(
					'message' => 'wikibase-comment-sitelink-add',
					'sitelink' => array(
						'newlink' => array( 'site' => 'dewiki', 'page' => 'Kanada' )
					)
				)
			)
		);

		$attribs = array(
			'rc_id' => 315,
			'rc_timestamp' => '20130819111741',
			'rc_user' => 0,
			'rc_user_text' => 'Cat',
			'rc_namespace' => 0,
			'rc_title' => 'Canada',
			'rc_comment' => '',
			'rc_minor' => 1,
			'rc_bot' => $bot ? 1 : 0,
			'rc_new' => 0,
			'rc_cur_id' => 52,
			'rc_this_oldid' => 114,
			'rc_last_oldid' => 114,
			'rc_type' => 5,
			'rc_patrolled' => 1,
			'rc_ip' => '',
			'rc_old_len' => 2,
			'rc_new_len' => 2,
			'rc_deleted' => 0,
			'rc_logid' => 0,
			'rc_log_type' => null,
			'rc_log_action' => '',
			'rc_params' => serialize( $params )
		);

		$recentChange->setAttribs( $attribs );

		return $recentChange;
	}

}
