<?php

namespace Wikibase\Client\Tests\RecentChanges;

use Language;
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
 * @author Daniel Kinzler
 */
class ExternalChangeFactoryTest extends \MediaWikiTestCase {

	private function getExternalChangeFactory() {
		return new ExternalChangeFactory( 'testrepo', Language::factory( 'qqx' ) );
	}

	public function testNewFromRecentChange_itemUpdated() {
		$recentChange = $this->makeRecentChange(
			'',
			'wikibase-comment-update',
			null,
			'wikibase-item~update',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( '(wikibase-comment-update)', 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	private function assertExternalChangeEquals( ExternalChange $expected, ExternalChange $actual, $message = '' ) {
		if ( $message !== '' ) {
			$message .= "\n";
		}

		$this->assertEquals( $expected->getChangeType(), $actual->getChangeType(), $message . 'getChangeType' );
		$this->assertEquals( $expected->getEntityId(), $actual->getEntityId(), $message . 'getEntityId' );

		$expectedRev = $expected->getRev();
		$actualRev = $actual->getRev();

		$this->assertEquals( $expectedRev->getPageId(), $actualRev->getPageId(), $message . 'rev:getPageId' );
		$this->assertEquals( $expectedRev->getParentId(), $actualRev->getParentId(), $message . 'rev:getParentId' );
		$this->assertEquals( $expectedRev->getRevId(), $actualRev->getRevId(), $message . 'rev:getRevId' );
		$this->assertEquals( $expectedRev->getSiteId(), $actualRev->getSiteId(), $message . 'rev:getSiteId' );
		$this->assertEquals( $expectedRev->getTimestamp(), $actualRev->getTimestamp(), $message . 'rev:getTimestamp' );
		$this->assertEquals( $expectedRev->getUserName(), $actualRev->getUserName(), $message . 'rev:getUserName' );
	}

	public function testNewFromRecentChange_siteLinkChange() {
		// at the moment, we don't do anything with this info :( and just say
		// 'wikibase-comment-update' for these changes.
		$recentChange = $this->makeRecentChange(
			'',
			array(
				'message' => 'wikibase-comment-sitelink-add',
				'sitelink' => array(
					'newlink' => array( 'site' => 'dewiki', 'page' => 'Kanada' )
				)
			),
			null,
			'wikibase-item~update',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( '(wikibase-comment-update)', 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_pageLinkedOnRepo() {
		$recentChange = $this->makeRecentChange(
			'',
			array(
				'message' => 'wikibase-comment-linked'
			),
			null,
			'wikibase-item~add',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( '(wikibase-comment-linked)', 'add' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_withRepoComment() {
		$comment = '/* wbsetclaim-update:2||1 */ [[Property:P213]]: [[Q850]]';

		$recentChange = $this->makeRecentChange(
			$comment,
			array(
				'message' => 'this-shall-be-ignored'
			),
			null,
			'wikibase-item~update',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( $comment, 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_compositeComment() {
		$recentChange = $this->makeRecentChange(
			'',
			null,
			array(
				'wikibase-comment-update',
				'wikibase-comment-update'
			),
			'wikibase-item~update',
			false
		);

		$expected = new ExternalChange(
			new ItemId( 'Q4' ),
			$this->makeRevisionData( '(wikibase-comment-multi: 2)' ),
			'update'
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$expected,
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_botEdit() {
		$recentChange = $this->makeRecentChange(
			'',
			'wikibase-comment-update',
			null,
			'wikibase-item~update',
			true
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( '(wikibase-comment-update)', 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_nonBotEdit() {
		$recentChange = $this->makeRecentChange(
			'',
			'wikibase-comment-update',
			null,
			'wikibase-item~update',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( '(wikibase-comment-update)', 'update' ),
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
			$this->makeRevisionData( $expectedComment ),
			$expectedType
		);
	}

	private function makeRevisionData( $comment ) {
		return new RevisionData(
			'Cat',
			'20130819111741',
			strval( $comment ),
			'testrepo',
			array(
				'page_id' => 5,
				'rev_id' => 92,
				'parent_id' => 90,
				'id' => 4,
				'type' => 'wikibase-item~update',
				'time' => '20130819111741',
				'object_id' => 'q4',
				'user_id' => 1,
				'revision_id' => 92,
				'entity_type' => 'item',
				'user_text' => 'Cat',
				'bot' => 0,
				'comment' => strval( $comment ),
 			)
	  	);
	}

	/**
	 * @param string $comment
	 * @param null|string|array $commentOverride
	 * @param null|string|array $compositeCommentOverride
	 * @param string $changeType
	 * @param bool $isBot
	 *
	 * @return RecentChange
	 */
	private function makeRecentChange( $comment, $commentOverride, $compositeCommentOverride, $changeType, $isBot ) {
		$recentChange = new RecentChange();
		$recentChange->counter = 2;

		$attribs = $this->makeAttribs(
			$this->makeRCParams( $comment, $commentOverride, $compositeCommentOverride, $changeType, $isBot ),
			$isBot
		);

		$attribs['rc_comment'] = $comment;
		$recentChange->setAttribs( $attribs );

		return $recentChange;
	}

	/**
	 * @param string $comment
	 * @param null|string|array $commentOverride
	 * @param null|string|array $compositeCommentOverride
	 * @param string $changeType
	 * @param boolean $bot
	 *
	 * @return array
	 */
	private function makeRCParams( $comment, $commentOverride, $compositeCommentOverride, $changeType, $bot ) {
		$params = array(
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
			),
			'comment' => $comment
		);

		if ( $commentOverride ) {
			$params['wikibase-repo-change']['comment'] = $commentOverride;
		}

		if ( $compositeCommentOverride ) {
			$params['wikibase-repo-change']['composite-comment'] = $compositeCommentOverride;
		}

		return $params;
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
