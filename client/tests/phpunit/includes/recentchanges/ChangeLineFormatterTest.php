<?php

namespace Wikibase\Test;

use ChangesList;
use ContentHandler;
use RecentChange;
use RequestContext;
use Title;
use WikiPage;
use MediaWikiTestCase;
use ValueParsers\ParserOptions;
use Wikibase\ChangeLineFormatter;
use Wikibase\ExternalChangeGenerator;
use Wikibase\RepoLinker;

/**
 * @covers Wikibase\ChangeLineFormatter
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangeLineFormatterTest extends MediaWikiTestCase {

	protected $title;

	protected $repoLinker;

	public function setUp() {
		parent::setUp();

		// ensure title exists and is not a red link
		$this->title = Title::newFromText( 'Canada' );
		$content = ContentHandler::makeContent( "it is a country", $this->title );
		$page = WikiPage::factory( $this->title );
		$page->doEditContent( $content, "testing", EDIT_NEW );

		$this->setMwGlobals( array(
			'wgLanguageCode' => 'en',
			'wgScriptPath' => '',
			'wgScript' => '/index.php',
			'wgArticlePath' => '/wiki/$1'
		) );

		$this->repoLinker = new RepoLinker(
			'http://www.wikidata.org',
			'/wiki/$1',
			'/w',
			array(
				'wikibase-item' => '',
				'wikibase-property' => 'Property'
			)
		);
	}

	public function tearDown() {
		parent::tearDown();

		$page = WikiPage::factory( $this->title );
		$page->doDeleteArticle( 'test done' );
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( array $expectedTags, array $patterns, RecentChange $recentChange ) {
		$context = new RequestContext();
		$changesList = ChangesList::newFromContext( $context );

		$changeGenerator = new ExternalChangeGenerator( 'testrepo' );

		$externalChange = $changeGenerator->newFromRecentChange( $recentChange );

		$formatter = new ChangeLineFormatter(
			$changesList->getUser(),
			$changesList->getLanguage(),
			$this->repoLinker,
			$changesList->recentChangesFlags( array( 'wikibase-edit' => true ), '' )
		);

		$formattedLine = $formatter->format(
			$externalChange,
			$recentChange->getTitle(),
			$recentChange->counter
		);

		foreach( $expectedTags as $key => $tag ) {
			$this->assertTag( $tag, $formattedLine, $key );
		}

		foreach( $patterns as $pattern ) {
			$this->assertRegExp( $pattern, $formattedLine );
		}
	}

	public function formatProvider() {
		return array(
			array(
				$this->getEditChangeTagMatchers(),
				$this->getEditPatterns(),
				$this->getEditRecentChange()
			),
			array(
				$this->getLogChangeTagMatchers(),
				array(),
				$this->getLogRecentChange()
			)
		);
	}

	public function getEditPatterns() {
		return array(
			'/title=Q4&amp;curid=5&amp;action=history/',
			'/title=Q4&amp;curid=5&amp;diff=92&amp;oldid=90/'
		);
	}

	public function getEditChangeTagMatchers() {
		return array(
			'edit-difflink' => array(
				'tag' => 'a',
				'content' => 'diff'
			),
			'edit-histlink' => array(
				'tag' => 'a',
				'content' => 'hist'
			),
			'edit-changeslist-separator' => array(
				'tag' => 'span',
				'attributes' => array(
					'class' => 'mw-changeslist-separator'
				),
				'content' => '. .'
			),
			'edit-change-flag' => array(
				'tag' => 'abbr',
				'attributes' => array(
					'class' => 'wikibase-edit'
				),
				'content' => 'D'
			),
			'edit-entitylink' => array(
				'tag' => 'a',
				'attributes' => array(
					'class' => 'plainlinks wb-entity-link',
					'href' => 'http://www.wikidata.org/wiki/Q4'
				),
				'content' => 'Q4'
			),
			'edit-changeslist-date' => array(
				'tag' => 'span',
				'attributes' => array(
					'class' => 'mw-changeslist-date'
				),
				'content' => '11:17'
			),
			'edit-userlink' => array(
				'tag' => 'a',
				'attributes' => array(
					'class' => 'plainlinks mw-userlink',
					'href' => 'http://www.wikidata.org/wiki/User:Cat'
				),
				'content' => 'Cat'
			),
			'edit-usertoollinks' => array(
				'tag' => 'span',
				'attributes' => array(
					'class' => 'mw-usertoollinks'
				)
			),
			'edit-usertalk' => array(
				'tag' => 'a',
				'attributes' => array(
					'class' => 'plainlinks',
					'href' => 'http://www.wikidata.org/wiki/User_talk:Cat'
				),
				'content' => 'Talk'
			),
			'edit-usercontribs' => array(
				'tag' => 'a',
				'attributes' => array(
					'class' => 'plainlinks',
					'href' => 'http://www.wikidata.org/wiki/Special:Contributions/Cat'
				),
				'content' => 'contribs'
			),
			'edit-comment' => array(
				'tag' => 'span',
				'attributes' => array(
					'class' => 'comment'
				)
			)
		);
	}

	protected function getEditRecentChange() {
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
				'bot' => 0,
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
			'rc_cur_time' => '20130819111741',
			'rc_user' => 0,
			'rc_user_text' => 'Cat',
			'rc_namespace' => 0,
			'rc_title' => 'Canada',
			'rc_comment' => '',
			'rc_minor' => 1,
			'rc_bot' => 0,
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

	protected function getLogChangeTagMatchers() {
		return array(
			'delete-deletionlog' => array(
				'tag' => 'a',
				'attributes' => array(
					'class' => 'plainlinks',
					'href' => 'http://www.wikidata.org/wiki/Special:Log/delete'
				),
				'content' => 'Deletion log'
			),
			'delete-changeslist-separator' => array(
				'tag' => 'span',
				'attributes' => array(
					'class' => 'mw-changeslist-separator'
				),
				'content' => '. .'
			),
			'delete-change-flag' => array(
				'tag' => 'abbr',
				'attributes' => array(
					'class' => 'wikibase-edit'
				),
				'content' => 'D'
			),
			'delete-titlelink' => array(
				'tag' => 'a',
				'attributes' => array(
					'href' => '/wiki/Canada'
				),
				'content' => 'Canada'
			),
			'delete-changeslist-date' => array(
				'tag' => 'span',
				'attributes' => array(
					'class' => 'mw-changeslist-date'
				),
				'content' => '15:18'
			),
			'delete-userlink' => array(
				'tag' => 'a',
				'attributes' => array(
					'class' => 'plainlinks mw-userlink',
					'href' => 'http://www.wikidata.org/wiki/User:Cat'
				),
				'content' => 'Cat'
			),
			'delete-usertoollinks' => array(
				'tag' => 'span',
				'attributes' => array(
					'class' => 'mw-usertoollinks'
				)
			),
			'delete-usertalk' => array(
				'tag' => 'a',
				'attributes' => array(
					'class' => 'plainlinks',
					'href' => 'http://www.wikidata.org/wiki/User_talk:Cat'
				),
				'content' => 'Talk'
			),
			'delete-contribs' => array(
				'tag' => 'a',
				'attributes' => array(
					'class' => 'plainlinks',
					'href' => 'http://www.wikidata.org/wiki/Special:Contributions/Cat'
				),
				'content' => 'contribs'
			),
			'delete-comment' => array(
				'tag' => 'span',
				'attributes' => array(
					'class' => 'comment'
				)
			)
		);
	}

	protected function getLogRecentChange() {
		$recentChange = new RecentChange();
		$recentChange->counter = 1;

		$params = array(
			'wikibase-repo-change' => array(
				'id' => 20,
				'type' => 'wikibase-item~remove',
				'time' => '20130820151835',
				'object_id' => 'q20',
				'user_id' => 1,
				'revision_id' => 0,
				'entity_type' => 'item',
				'user_text' => 'Cat',
				'page_id' => 0,
				'rev_id' => 0,
				'parent_id' => 0,
				'comment' => array(
					'message' => 'wikibase-comment-remove'
				)
			)
		);

		$attribs = array(
			'rc_id' => 316,
			'rc_timestamp' => '20130820151835',
			'rc_cur_time' => '20130820151835',
			'rc_user' => 0,
			'rc_user_text' => 'Cat',
			'rc_namespace' => 0,
			'rc_title' => 'Canada',
			'rc_comment' => '',
			'rc_minor' => 1,
			'rc_bot' => 0,
			'rc_new' => 0,
			'rc_cur_id' => 12,
			'rc_this_oldid' => 53,
			'rc_last_oldid' => 53,
			'rc_type' => 5,
			'rc_patrolled' => 1,
			'rc_ip' => '',
			'rc_old_len' => 5,
			'rc_new_len' => 5,
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
