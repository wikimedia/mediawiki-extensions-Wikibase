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

	public function testNewFromRecentChange_itemUpdated() {
		$commentData = 'wikibase-comment-update';

		$recentChange = $this->makeRecentChange( $commentData, 'wikibase-item~update', false );

		$externalChangeFactory = new ExternalChangeFactory( 'testrepo' );

		$this->assertEquals(
			$this->makeExpectedExternalChange( 'wikibase-comment-update', 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_siteLinkChange() {
		// at the moment, we don't do anything with this info :( and just say
		// 'wikibase-comment-update' for these changes.
		$commentData = array(
			'message' => 'wikibase-comment-sitelink-add',
			'sitelink' => array(
				'newlink' => array( 'site' => 'dewiki', 'page' => 'Kanada' )
			)
		);

		$recentChange = $this->makeRecentChange( $commentData, 'wikibase-item~update', false );

		$externalChangeFactory = new ExternalChangeFactory( 'testrepo' );

		$this->assertEquals(
			$this->makeExpectedExternalChange( 'wikibase-comment-update', 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_pageLinkedOnRepo() {
		$commentData = array(
			'message' => 'wikibase-comment-linked'
		);

		$recentChange = $this->makeRecentChange( $commentData, 'wikibase-item~add', false );

		$externalChangeFactory = new ExternalChangeFactory( 'testrepo' );

		$this->assertEquals(
			$this->makeExpectedExternalChange( 'wikibase-comment-linked', 'add' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_withRepoComment() {
		$commentData = '/* wbsetclaim-update:2||1 */ [[Property:P213]]: [[Q850]]';

		$recentChange = $this->makeRecentChange( $commentData, 'wikibase-item~update', false );

		$externalChangeFactory = new ExternalChangeFactory( 'testrepo' );

		$this->assertEquals(
			$this->makeExpectedExternalChange( 'wikibase-comment-update', 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_compositeComment() {
		$commentData = 'wikibase-comment-update';

		$recentChange = new RecentChange();
		$recentChange->counter = 2;

		$rcParams = $this->makeRCParams( $commentData, 'wikibase-item~update', false );

		$rcParams['wikibase-repo-change']['composite-comment'] = array(
			'wikibase-comment-update',
			'wikibase-comment-update'
		);

		$recentChange->setAttribs( $this->makeAttribs( $rcParams, false ) );

		$expected = new ExternalChange(
			new ItemId( 'Q4' ),
			$this->makeRevisionData( array(
				'key' => 'wikibase-comment-multi',
				'numparams' => 2
			) ),
			'update'
		);

		$externalChangeFactory = new ExternalChangeFactory( 'testrepo' );

		$this->assertEquals(
			$expected,
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_botEdit() {
		$commentData = 'wikibase-comment-update';

		$recentChange = $this->makeRecentChange( $commentData, 'wikibase-item~update', true );

		$expected = new ExternalChange(
			new ItemId( 'Q4' ),
			$this->makeRevisionData( array(
				'key' => 'wikibase-comment-update'
			) ),
			'update'
		);

		$externalChangeFactory = new ExternalChangeFactory( 'testrepo' );

		$this->assertEquals(
			$this->makeExpectedExternalChange( 'wikibase-comment-update', 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_nonBotEdit() {
		$commentData = 'wikibase-comment-update';

		$recentChange = $this->makeRecentChange( $commentData, 'wikibase-item~update', false );

		$externalChangeFactory = new ExternalChangeFactory( 'testrepo' );

		$this->assertEquals(
			$this->makeExpectedExternalChange( 'wikibase-comment-update', 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	/**
	 * @param string $expectedComment
	 * @param string $expectedType
	 *
	 * @return ExternalChange
	 */
	private function makeExpectedExternalChange( $expectedComment, $expectedType ) {
		return new ExternalChange(
			new ItemId( 'Q4' ),
			$this->makeRevisionData( array(
				'key' => $expectedComment
			) ),
			$expectedType
		);
	}

	private function makeRevisionData( array $comment ) {
		return new RevisionData(
			'Cat',
			5,
			92,
			90,
			'20130819111741',
			$comment,
			'testrepo'
	  	);
	}

	/**
	 * @param array|string $commentData
	 * @param string $changeType
	 * @param bool $isBot
	 *
	 * @return RecentChange
	 */
	private function makeRecentChange( $commentData, $changeType, $isBot ) {
		$recentChange = new RecentChange();
		$recentChange->counter = 2;

		$attribs = $this->makeAttribs(
			$this->makeRCParams( $commentData, $changeType, $isBot ),
			$isBot
		);

		$recentChange->setAttribs( $attribs );

		return $recentChange;
	}

	/**
	 * @param array|string $commentData
	 * @param string $changeType
	 * @param boolean $bot
	 *
	 * @return array
	 */
	private function makeRCParams( $commentData, $changeType, $bot ) {
		return array(
			'wikibase-repo-change' => array(
				'id' => 4,
				'type' => $changeType,
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
				'comment' => $commentData
			)
		);
	}

	private function makeAttribs( array $rcParams, $bot ) {
		return array(
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
			'rc_params' => serialize( $rcParams )
		);
	}

}
