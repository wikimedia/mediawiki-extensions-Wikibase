<?php

namespace Wikibase\Client\Tests\RecentChanges;

use Language;
use RecentChange;
use Wikibase\Client\RecentChanges\ExternalChange;
use Wikibase\Client\RecentChanges\ExternalChangeFactory;
use Wikibase\Client\RecentChanges\RevisionData;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\RecentChanges\ExternalChangeFactory
 *
 * @group WikibaseClient
 * @group Database
 * @group medium
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class ExternalChangeFactoryTest extends \MediaWikiTestCase {

	private function getExternalChangeFactory() {
		return new ExternalChangeFactory(
			'testrepo',
			Language::factory( 'qqx' ),
			new BasicEntityIdParser()
		);
	}

	public function testNewFromRecentChange_itemUpdated() {
		$recentChange = $this->makeRecentChange(
			'',
			null,
			'wikibase-comment-update',
			null,
			'wikibase-item~update',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( '(wikibase-comment-update)', null, 'update' ),
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
			null,
			[
				'message' => 'wikibase-comment-sitelink-add',
				'sitelink' => [
					'newlink' => [ 'site' => 'dewiki', 'page' => 'Kanada' ]
				]
			],
			null,
			'wikibase-item~update',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( '(wikibase-comment-update)', null, 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_pageLinkedOnRepo() {
		$recentChange = $this->makeRecentChange(
			'',
			null,
			[
				'message' => 'wikibase-comment-linked'
			],
			null,
			'wikibase-item~add',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( '(wikibase-comment-linked)', null, 'add' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_withRepoComment() {
		$comment = '/* wbsetclaim-update:2||1 */ [[Property:P213]]: [[Q850]]';

		$recentChange = $this->makeRecentChange(
			$comment,
			null,
			[
				'message' => 'this-shall-be-ignored'
			],
			null,
			'wikibase-item~update',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( $comment, null, 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_withHtmlComment() {
		$comment = '/* wbsetclaim-update:2||1 */ this shall be ignored';
		$commentHtml = '<span><a href="http://acme.test">Linky</a> <script>we can run scripts here</script><span/>';

		$recentChange = $this->makeRecentChange(
			$comment,
			$commentHtml,
			[
				'message' => 'this-shall-be-ignored'
			],
			null,
			'wikibase-item~update',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( $comment, $commentHtml, 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_compositeComment() {
		$recentChange = $this->makeRecentChange(
			'',
			null,
			null,
			[
				'wikibase-comment-update',
				'wikibase-comment-update'
			],
			'wikibase-item~update',
			false
		);

		$expected = new ExternalChange(
			new ItemId( 'Q4' ),
			$this->makeRevisionData( '(wikibase-comment-multi: 2)', null ),
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
			null,
			'wikibase-comment-update',
			null,
			'wikibase-item~update',
			true
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( '(wikibase-comment-update)', null, 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	public function testNewFromRecentChange_nonBotEdit() {
		$recentChange = $this->makeRecentChange(
			'',
			null,
			'wikibase-comment-update',
			null,
			'wikibase-item~update',
			false
		);

		$externalChangeFactory = $this->getExternalChangeFactory();

		$this->assertExternalChangeEquals(
			$this->makeExpectedExternalChange( '(wikibase-comment-update)', null, 'update' ),
			$externalChangeFactory->newFromRecentChange( $recentChange )
		);
	}

	/**
	 * @param string $expectedComment
	 * @param string|null $commentHtml
	 * @param string $expectedType
	 *
	 * @return ExternalChange
	 */
	private function makeExpectedExternalChange( $expectedComment, $commentHtml, $expectedType ) {
		return new ExternalChange(
			new ItemId( 'Q4' ),
			$this->makeRevisionData( $expectedComment, $commentHtml ),
			$expectedType
		);
	}

	/**
	 * @param string $comment
	 * @param string|null $commentHtml
	 *
	 * @return RevisionData
	 */
	private function makeRevisionData( $comment, $commentHtml = null ) {
		return new RevisionData(
			'Cat',
			'20130819111741',
			strval( $comment ),
			$commentHtml,
			'testrepo',
			[
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
			]
		);
	}

	/**
	 * @param string $comment
	 * @param string|null $commentHtml
	 * @param null|string|array $legacyComment
	 * @param null|string|array $compositeLegacyComment
	 * @param string $changeType
	 * @param bool $isBot
	 *
	 * @return RecentChange
	 */
	private function makeRecentChange( $comment, $commentHtml, $legacyComment, $compositeLegacyComment, $changeType, $isBot ) {
		$recentChange = new RecentChange();
		$recentChange->counter = 2;

		$attribs = $this->makeAttribs(
			$this->makeRCParams( $comment, $commentHtml, $legacyComment, $compositeLegacyComment, $changeType, $isBot ),
			$isBot
		);

		$attribs['rc_comment'] = $comment;
		$recentChange->setAttribs( $attribs );

		return $recentChange;
	}

	/**
	 * @param string $comment
	 * @param null|string $commentHtml
	 * @param null|string|array $legacyComment
	 * @param null|string|array $compositeLegacyComment
	 * @param string $changeType
	 * @param boolean $bot
	 *
	 * @return array
	 */
	private function makeRCParams( $comment, $commentHtml, $legacyComment, $compositeLegacyComment, $changeType, $bot ) {
		$params = [
			'wikibase-repo-change' => [
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
			],
			'comment' => $comment,
		];

		if ( $commentHtml !== null ) {
			$params['comment-html'] = $commentHtml;
		}

		if ( $legacyComment ) {
			$params['wikibase-repo-change']['comment'] = $legacyComment;
		}

		if ( $compositeLegacyComment ) {
			$params['wikibase-repo-change']['composite-comment'] = $compositeLegacyComment;
		}

		return $params;
	}

	private function makeAttribs( array $rcParams, $bot ) {
		return [
			'rc_id' => 315,
			'rc_timestamp' => '20130819111741',
			'rc_user' => 0,
			'rc_user_text' => 'Cat',
			'rc_namespace' => 0,
			'rc_title' => 'Canada',
			'rc_comment' => '',
			'rc_comment_text' => '', // For Ic3a434c0
			'rc_comment_data' => null,
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
		];
	}

}
