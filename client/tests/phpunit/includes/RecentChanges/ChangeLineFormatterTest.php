<?php

namespace Wikibase\Client\Tests\RecentChanges;

use ChangesList;
use DerivativeContext;
use HamcrestPHPUnitIntegration;
use Language;
use Linker;
use MediaWikiLangTestCase;
use RecentChange;
use RequestContext;
use Title;
use User;
use Wikibase\Client\RecentChanges\ChangeLineFormatter;
use Wikibase\Client\RecentChanges\ExternalChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;

/**
 * @covers Wikibase\Client\RecentChanges\ChangeLineFormatter
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangeLineFormatterTest extends MediaWikiLangTestCase {
	use HamcrestPHPUnitIntegration;

	protected $repoLinker;

	protected function setUp() {
		parent::setUp();

		// these are required because Linker is used in ChangeLineFormatter
		// @todo eliminate Linker or at least use of Linker in Wikibase :)
		$this->setMwGlobals( [
			'wgScriptPath' => '',
			'wgScript' => '/index.php',
			'wgArticlePath' => '/wiki/$1'
		] );

		$this->repoLinker = new RepoLinker(
			'http://www.wikidata.org',
			'/wiki/$1',
			'/w'
		);
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( array $expectedTags, array $patterns, RecentChange $recentChange ) {
		$context = $this->getTestContext();

		// Use the actual setting, because out handler for the FormatAutocomment hook will check
		// the wiki id against this setting.
		$repoWikiId = WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'repoSiteId' );

		$changesList = ChangesList::newFromContext( $context );
		$changeFactory = new ExternalChangeFactory(
			$repoWikiId,
			Language::factory( 'en' ),
			new BasicEntityIdParser()
		);
		$externalChange = $changeFactory->newFromRecentChange( $recentChange );

		$formatter = new ChangeLineFormatter(
			$changesList->getUser(),
			Language::factory( 'en' ),
			$this->repoLinker
		);

		$formattedLine = $formatter->format(
			$externalChange,
			$recentChange->getTitle(),
			$recentChange->counter,
			$changesList->recentChangesFlags( [ 'wikibase-edit' => true ], '' )
		);

		foreach ( $expectedTags as $key => $tagMatcher ) {
			$message = $key . "\n\t" . $formattedLine;
			$this->assertThatHamcrest(
				$message,
				$formattedLine,
				is( htmlPiece( havingChild( $tagMatcher ) ) )
			);
		}

		foreach ( $patterns as $pattern ) {
			$this->assertRegExp( $pattern, $formattedLine );
		}
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormatDataForEnhancedLine( array $expectedTags, array $patterns, RecentChange $recentChange ) {
		$formatter = new ChangeLineFormatter(
			$this->getTestContext()->getUser(),
			Language::factory( 'en' ),
			$this->repoLinker
		);

		// Use the actual setting, because out handler for the FormatAutocomment hook will check
		// the wiki id against this setting.
		$repoWikiId = WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'repoSiteId' );

		$changeFactory = new ExternalChangeFactory(
			$repoWikiId,
			Language::factory( 'en' ),
			new BasicEntityIdParser()
		);
		$externalChange = $changeFactory->newFromRecentChange( $recentChange );

		$data = [
			'untouchedKey' => 'foo',
			'characterDiff' => '(0)',
			'separatorAfterCharacterDiff' => '. .',
		];

		$formatter->formatDataForEnhancedLine(
			$data,
			$externalChange,
			$recentChange->getTitle(),
			$recentChange->counter
		);

		$this->assertArrayNotHasKey( 'characterDiff', $data );
		$this->assertArrayNotHasKey( 'separatorAfterCharacterDiff', $data );
		$this->assertArrayHasKey( 'untouchedKey', $data );
		$this->assertArrayHasKey( 'recentChangesFlags', $data );
		$this->assertArrayHasKey( 'timestampLink', $data );
		$this->assertEquals( $data['untouchedKey'], 'foo' );
		$this->assertEquals( [ 'wikibase-edit' => true ], $data['recentChangesFlags'] );

		$map = [
			'difflink' => 'currentAndLastLinks',
			'histlink' => 'currentAndLastLinks',
			'entitylink' => 'currentAndLastLinks',
			'deletionlog' => 'currentAndLastLinks',
			'changeslist-separator' => 'separatorAfterCurrentAndLastLinks',
			'userlink' => 'userLink',
			'usertoollinks' => 'userTalkLink',
			'usertalk' => 'userTalkLink',
			'usercontribs' => 'userTalkLink',
			'comment' => 'comment',
		];

		foreach ( $expectedTags as $key => $tagMatcher ) {
			$part = explode( '-', $key, 2 )[1];
			if ( isset( $map[$part] ) ) {
				$this->assertArrayHasKey( $map[$part], $data );
				$message = $key . "\n\t" . $data[$map[$part]];
				$this->assertThatHamcrest(
					$message,
					$data[$map[$part]],
					is( htmlPiece( havingChild( $tagMatcher ) ) )
				);
			}
		}

		$formattedLine = '';
		foreach ( array_unique( array_values( $map ) ) as $key ) {
			if ( isset( $data[$key] ) ) {
				$formattedLine .= $data[$key];
			}
		}

		foreach ( $patterns as $pattern ) {
			$this->assertRegExp( $pattern, $formattedLine );
		}
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormatDataForEnhancedBlockLine( array $expectedTags, array $patterns, RecentChange $recentChange ) {
		$formatter = new ChangeLineFormatter(
			$this->getTestContext()->getUser(),
			Language::factory( 'en' ),
			$this->repoLinker
		);

		// Use the actual setting, because out handler for the FormatAutocomment hook will check
		// the wiki id against this setting.
		$repoWikiId = WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'repoSiteId' );

		$changeFactory = new ExternalChangeFactory(
			$repoWikiId,
			Language::factory( 'en' ),
			new BasicEntityIdParser()
		);
		$externalChange = $changeFactory->newFromRecentChange( $recentChange );

		$data = [
			'untouchedKey' => 'foo',
			'articleLink' => Linker::link( $recentChange->getTitle() ),
			'characterDiff' => '(0)',
			'separatorAftercharacterDiff' => '. .',
		];

		$formatter->formatDataForEnhancedBlockLine(
			$data,
			$externalChange,
			$recentChange->getTitle(),
			$recentChange->counter
		);

		$this->assertArrayNotHasKey( 'separatorAftercharacterDiff', $data );
		$this->assertArrayNotHasKey( 'characterDiff', $data );
		$this->assertArrayHasKey( 'untouchedKey', $data );
		$this->assertArrayHasKey( 'recentChangesFlags', $data );
		$this->assertArrayHasKey( 'timestampLink', $data );
		$this->assertEquals( $data['untouchedKey'], 'foo' );
		$this->assertEquals( [ 'wikibase-edit' => true ], $data['recentChangesFlags'] );

		$map = [
			'entitylink' => 'articleLink',
			'deletionlog' => 'articleLink',
			'titlelink' => 'articleLink',
			'difflink' => 'historyLink',
			'histlink' => 'historyLink',
			'changeslist-separator' => 'separatorAfterLinks',
			'userlink' => 'userLink',
			'usertoollinks' => 'userTalkLink',
			'usertalk' => 'userTalkLink',
			'usercontribs' => 'userTalkLink',
			'comment' => 'comment',
		];

		foreach ( $expectedTags as $key => $tagMatcher ) {
			$part = explode( '-', $key, 2 )[1];
			if ( isset( $map[$part] ) ) {
				$this->assertArrayHasKey( $map[$part], $data );
				$message = $key . "\n\t" . $data[$map[$part]];
				$this->assertThatHamcrest(
					$message,
					$data[$map[$part]],
					is( htmlPiece( havingChild( $tagMatcher ) ) )
				);
			}
		}

		$formattedLine = '';
		foreach ( array_unique( array_values( $map ) ) as $key ) {
			if ( isset( $data[$key] ) ) {
				$formattedLine .= $data[$key];
			}
		}

		foreach ( $patterns as $pattern ) {
			$this->assertRegExp( $pattern, $formattedLine );
		}
	}

	private function getTestContext() {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setLanguage( Language::factory( 'en' ) );
		$context->setUser( User::newFromId( 0 ) );

		return $context;
	}

	public function formatProvider() {
		$commentHtml = '<span><a href="http://acme.test">Linky</a> <script>we can run scripts here</script><span/>';

		return [
			'edit-change' => [
				$this->getEditSiteLinkChangeTagMatchers(),
				$this->getEditSiteLinkPatterns(),
				$this->getEditSiteLinkRecentChange(
					'/* wbsetclaim-update:2||1 */ [[Property:P213]]: [[Q850]]'
				)
			],
			'log-change' => [
				$this->getLogChangeTagMatchers(),
				[
					'/Log Change Comment/',
				],
				$this->getLogRecentChange()
			],
			'comment-fallback' => [
				[],
				[
					'/<span class=\"comment\">.*\(Associated .*? item deleted\. Language links removed\.\)/'
				],
				$this->getEditSiteLinkRecentChange(
					'',
					null,
					[
						'message' => 'wikibase-comment-remove',
					],
					null
				)
			],
			'comment-injection' => [
				[],
				[
					'/\(&lt;script&gt;evil&lt;\/script&gt;\)/'
				],
				$this->getEditSiteLinkRecentChange(
					'<script>evil</script>'
				)
			],
			'comment-html' => [
				[],
				[
					'/<span class=\"comment\">.*' . preg_quote( $commentHtml, '/' ) . '/',
				],
				$this->getEditSiteLinkRecentChange(
					'this shall be ignored',
					$commentHtml,
					[
						'message' => 'this-shall-be-ignored',
					],
					null
				)
			],
		];
	}

	public function getEditSiteLinkPatterns() {
		return [
			'/title=Special%3AEntityPage%2FQ4&amp;curid=5&amp;action=history/',
			'/title=Special%3AEntityPage%2FQ4&amp;curid=5&amp;diff=92&amp;oldid=90/',
			'/<span class="comment">\('
				. '‎<span dir="auto"><span class="autocomment">Changed claim: <\/span><\/span> '
				. '<a .*?>Property:P213<\/a>: <a .*?>Q850<\/a>'
				. '\)<\/span>/',
		];
	}

	public function getEditSiteLinkChangeTagMatchers() {
		return [
			'edit-difflink' => both( withTagName( 'a' ) )->andAlso( havingTextContents( 'diff' ) ),
			'edit-histlink' => both( withTagName( 'a' ) )->andAlso( havingTextContents( 'hist' ) ),
			'edit-changeslist-separator' => allOf(
				withTagName( 'span' ),
				withClass( 'mw-changeslist-separator' ),
				havingTextContents( '. .' )
			),
			'edit-change-flag' => allOf(
				withTagName( 'abbr' ),
				withClass( 'wikibase-edit' ),
				havingTextContents( 'D' )
			),
			'edit-titlelink' => both( withTagName( 'a' ) )
				->andAlso( havingTextContents( 'Canada' ) ),
			'edit-titlelink-wrapper' => allOf(
				withTagName( 'span' ),
				withClass( 'mw-title' ),
				havingChild(
					both( withTagName( 'a' ) )->andAlso( havingTextContents( 'Canada' ) )
				)
			),
			'edit-entitylink' => allOf(
				withTagName( 'a' ),
				withClass( 'wb-entity-link' ),
				withAttribute( 'href' )->havingValue(
					'http://www.wikidata.org/wiki/Special:EntityPage/Q4'
				),
				havingTextContents( 'Q4' )
			),
			'edit-changeslist-date' => allOf(
				withTagName( 'span' ),
				withClass( 'mw-changeslist-date' ),
				havingTextContents( '11:17' )
			),
			'edit-userlink' => allOf(
				withTagName( 'a' ),
				withClass( 'mw-userlink' ),
				withAttribute( 'href' )->havingValue( 'http://www.wikidata.org/wiki/User:Cat' ),
				havingTextContents( 'Cat' )
			),
			'edit-usertoollinks' => both( withTagName( 'span' ) )->andAlso(
				withClass( 'mw-usertoollinks' )
			),
			'edit-usertalk' => allOf(
				withTagName( 'a' ),
				withAttribute( 'href' )->havingValue(
					'http://www.wikidata.org/wiki/User_talk:Cat'
				),
				havingTextContents( 'talk' )
			),
			'edit-usercontribs' => allOf(
				withTagName( 'a' ),
				withAttribute( 'href' )->havingValue(
					'http://www.wikidata.org/wiki/Special:Contributions/Cat'
				),
				havingTextContents( 'contribs' )
			),
			'edit-comment' => both( withTagName( 'span' ) )->andAlso( withClass( 'comment' ) ),
		];
	}

	protected function getEditSiteLinkRecentChange(
		$comment,
		$commentHtml = null,
		$legacyComment = null,
		$compositeLegacyComment = null
	) {
		$params = [
			'wikibase-repo-change' => [
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
			]
		];

		if ( $legacyComment ) {
			$params['wikibase-repo-change']['comment'] = $legacyComment;
		}

		if ( $commentHtml ) {
			$params['comment-html'] = $commentHtml;
		}

		if ( $compositeLegacyComment ) {
			$params['wikibase-repo-change']['composite-comment'] = $compositeLegacyComment;
		}

		$title = $this->makeTitle( NS_MAIN, 'Canada', 52, 114 );
		return $this->makeRecentChange( $params, $title, $comment );
	}

	protected function getLogChangeTagMatchers() {
		return [
			'delete-deletionlog' => both(
				tagMatchingOutline( '<a href="http://www.wikidata.org/wiki/Special:Log/delete"/>' )
			)
				->andAlso( havingTextContents( 'Deletion log' ) ),
			'delete-changeslist-separator' => both(
				tagMatchingOutline( '<span class="mw-changeslist-separator"/>' )
			)
				->andAlso( havingTextContents( '. .' ) ),
			'delete-change-flag' => both( tagMatchingOutline( '<abbr class="wikibase-edit"/>' ) )
				->andAlso( havingTextContents( 'D' ) ),
			'delete-titlelink' => both( withTagName( 'a' ) )
				->andAlso( havingTextContents( 'Canada' ) ),
			'delete-titlelink-wrapper' => both(
				tagMatchingOutline( '<span class="mw-title"/>' )
			)
				->andAlso(
					havingChild(
						both( withTagName( 'a' ) )->andAlso( havingTextContents( 'Canada' ) )
					)
				),
			'delete-changeslist-date' => both(
				tagMatchingOutline( '<span class="mw-changeslist-date"/>' )
			)
				->andAlso( havingTextContents( '15:18' ) ),
			'delete-userlink' => both(
				tagMatchingOutline(
					'<a class="mw-userlink" href="http://www.wikidata.org/wiki/User:Cat"/>'
				)
			)
				->andAlso( havingTextContents( 'Cat' ) ),
			'delete-usertoollinks' => tagMatchingOutline( '<span class="mw-usertoollinks"/>' ),
			'delete-usertalk' => both(
				tagMatchingOutline( '<a href="http://www.wikidata.org/wiki/User_talk:Cat"/>' )
			)
				->andAlso( havingTextContents( 'talk' ) ),
			'delete-contribs' => both(
				tagMatchingOutline(
					'<a href="http://www.wikidata.org/wiki/Special:Contributions/Cat"/>'
				)
			)
				->andAlso( havingTextContents( 'contribs' ) ),
			'delete-comment' => tagMatchingOutline( '<span class="comment"/>' ),
		];
	}

	protected function getLogRecentChange() {
		$params = [
			'wikibase-repo-change' => [
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
				'bot' => false
			]
		];

		$title = $this->makeTitle( NS_MAIN, 'Canada', 12, 53 );
		return $this->makeRecentChange( $params, $title, 'Log Change Comment' );
	}

	/**
	 * @param int $ns
	 * @param string $text
	 * @param int $pageId
	 * @param int $currentRevision
	 *
	 * @return Title
	 */
	private function makeTitle( $ns, $text, $pageId, $currentRevision ) {
		$title = $this->getMock( Title::class );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $ns ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $text ) );

		$title->expects( $this->any() )
			->method( 'getDBkey' )
			->will( $this->returnValue( str_replace( ' ', '_', $text ) ) );

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( $pageId ) );

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( $currentRevision ) );

		$title->expects( $this->any() )
			->method( 'getLength' )
			->will( $this->returnValue( 1234 ) );

		return $title;
	}

	private function makeRecentChange( array $params, Title $title, $comment ) {
		$attribs = [
			'rc_id' => 1234,
			'rc_timestamp' => $params['wikibase-repo-change']['time'],
			'rc_user' => 0,
			'rc_user_text' => $params['wikibase-repo-change']['user_text'],
			'rc_namespace' => $title->getNamespace(),
			'rc_title' => $title->getDBkey(),
			'rc_comment' => $comment,
			'rc_comment_text' => $comment, // For Ic3a434c0
			'rc_comment_data' => null,
			'rc_minor' => true,
			'rc_bot' => $params['wikibase-repo-change']['bot'],
			'rc_new' => false,
			'rc_cur_id' => $title->getArticleID(),
			'rc_this_oldid' => $title->getLatestRevID(),
			'rc_last_oldid' => $title->getLatestRevID(),
			'rc_type' => RC_EXTERNAL,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_patrolled' => 2,
			'rc_ip' => '127.0.0.1',
			'rc_old_len' => 123,
			'rc_new_len' => 123,
			'rc_deleted' => false,
			'rc_logid' => 0,
			'rc_log_type' => null,
			'rc_log_action' => '',
			'rc_params' => serialize( $params ),
		];

		$recentChange = RecentChange::newFromRow( (object)$attribs );
		$recentChange->counter = 1;

		return $recentChange;
	}

}
