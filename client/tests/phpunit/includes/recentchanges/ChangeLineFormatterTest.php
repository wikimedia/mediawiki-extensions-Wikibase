<?php
namespace Wikibase\Test;

use ChangesList;
use ContentHandler;
use RecentChange;
use RequestContext;
use Title;
use WikiPage;
use MediaWikiTestCase;
use Wikibase\ChangeLineFormatter;
use Wikibase\RepoLinker;

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
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

		wfSuppressWarnings();

		// ensure title exists and is not a red link
		$this->title = Title::newFromText( 'Canada' );
		$content = ContentHandler::makeContent( "it is a country", $this->title );
		$page = WikiPage::factory( $this->title );
		$page->doEditContent( $content, "testing", EDIT_NEW );

		wfRestoreWarnings();

		$this->setMwGlobals( array(
			'wgArticlePath' => '/wiki/$1'
		) );

		$this->repoLinker = new RepoLinker(
			'http://www.wikidata.org',
			'/wiki/$1',
			'/w',
			array()
		);
	}

	public function tearDown() {
		parent::tearDown();

		$page = WikiPage::factory( $this->title );
		$page->doDeleteArticle( 'test done' );
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

	public function formatProvider() {
		$recentEditChange = $this->getEditRecentChange();

		$expectedEditChange = '(<a class="plainlinks" tabindex="2" href="http://www.wikidata.org/w/'
			. 'index.php?title=Q4&amp;curid=5&amp;diff=92&amp;oldid=90">diff</a> | '
			. '<a class="plainlinks" href="http://www.wikidata.org/w/index.php?title=Q4'
			. '&amp;curid=5&amp;action=history">hist</a>) <span class="mw-changeslist-separator">'
			. '. .</span> <abbr class=\'wikibase-edit\' title=\'Wikidata edit\'>D</abbr> '
			. '<a href="/wiki/Canada" title="Canada">Canada</a> (<a class="plainlinks '
			. 'wb-entity-link" href="http://www.wikidata.org/wiki/Q4">Q4</a>); <span '
			. 'class="mw-changeslist-date">11:17</span> <span class="mw-changeslist-separator">'
			. '. .</span> <a class="plainlinks mw-userlink" href="'
			. 'http://www.wikidata.org/wiki/User:Cat">Cat</a> <span class="mw-usertoollinks">'
			. '(<a class="plainlinks" href="http://www.wikidata.org/wiki/User_talk:Cat">Talk</a>'
			. ' | <a class="plainlinks" href="http://www.wikidata.org/wiki/Special:Contributions/'
			. 'Cat">contribs</a>)</span> <span class="comment">(Wikidata item changed)</span>';

		$recentLogChange = $this->getLogRecentChange();

		$expectedLogChange = '(<a class="plainlinks" href="http://www.wikidata.org/wiki/Special:Log/'
			. 'delete">Deletion log</a>) <span class="mw-changeslist-separator">. .</span> '
			. '<abbr class=\'wikibase-edit\' title=\'Wikidata edit\'>D</abbr> <a href='
			. '"/wiki/Canada" title="Canada">Canada</a>; <span class="mw-changeslist-date">15:18'
			. '</span> <span class="mw-changeslist-separator">. .</span> <a class="plainlinks'
			. ' mw-userlink" href="http://www.wikidata.org/wiki/User:Cat">Cat</a> <span '
			. 'class="mw-usertoollinks">(<a class="plainlinks" href="http://www.wikidata.org/wiki/'
			. 'User_talk:Cat">Talk</a> | <a class="plainlinks" href="http://www.wikidata.org/'
			. 'wiki/Special:Contributions/Cat">contribs</a>)</span> <span class="comment">'
			. '(Associated Wikidata item deleted. Language links removed.)</span>';

		$changes = array();

		$changes[] = array(
			$recentEditChange,
			$expectedEditChange
		);

		$changes[] = array(
			$recentLogChange,
			$expectedLogChange
		);

		return $changes;
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( RecentChange $recentChange, $expected ) {
		$context = new RequestContext();
		$changesList = ChangesList::newFromContext( $context );
		$changeLineFormatter = new ChangeLineFormatter( $changesList, 'en', $this->repoLinker );
		$formattedLine = $changeLineFormatter->format( $recentChange );
		$this->assertEquals( $expected, $formattedLine, 'formatted change line' );
	}

}
