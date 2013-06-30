<?php

namespace Wikibase\Test;
use Wikibase\LangLinkHandler;

/**
 * Tests for the LangLinkHandler class.
 *
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
 *        ^--- uses DB indirectly; removed when Title is made not to use the database.
 * @group XXX
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LangLinkHandlerTest extends \MediaWikiTestCase {

	/* @var MockRepository $langLinkHandler */
	protected $mockRepo;

	/* @var LangLinkHandler $langLinkHandler */
	protected $langLinkHandler;

	static $itemData = array(
		1 => array( // matching item
			'id' => 1,
			'label' => array( 'en' => 'Foo' ),
			'links' => array(
				'dewiki' => 'Foo de',
				'enwiki' => 'Foo en',
				'srwiki' => 'Foo sr',
				'dewiktionary' => 'Foo de word',
				'enwiktionary' => 'Foo en word',
			)
		),
		2 => array( // matches, but not in a namespace with external langlinks enabled
			'id' => 2,
			'label' => array( 'en' => 'Talk:Foo' ),
			'links' => array(
				'dewiki' => 'Talk:Foo de',
				'enwiki' => 'Talk:Foo en',
				'srwiki' => 'Talk:Foo sr',
			)
		)
	);

	public function setUp() {
		parent::setUp();

		static $hasSites = false;

		if ( !$hasSites ) {
			$hasSites = true;
		}

		$this->mockRepo = new MockRepository();

		foreach ( self::$itemData as $data ) {
			$item = new \Wikibase\Item( $data );
			$this->mockRepo->putEntity( $item );
		}

		$this->langLinkHandler = new LangLinkHandler(
			'srwiki',
			array(),
			array( NS_TALK ),
			$this->mockRepo,
			\TestSites::newMockSiteStore()
		);
	}

	public static function provideGetEntityLinks() {
		return array(
			array( // #0
				'Xoo', // page
				array() // expected links
			),
			array( // #1
				'Foo_sr', // page
				array( // expected links
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				)
			),
		);
	}

	/**
	 * @dataProvider provideGetEntityLinks
	 */
	public function testGetEntityLinks( $title, $expectedLinks ) {
		if ( is_string( $title ) ) {
			$title = \Title::newFromText( $title );
		}

		$links = array();

		foreach ( $this->langLinkHandler->getEntityLinks( $title ) as $link ) {
			$links[$link->getSiteId()] = $link->getPageName();
		}

		$this->assertArrayEquals( $expectedLinks, $links, false, true );
	}

	public static function provideGetNoExternalLangLinks() {
		return array(
			array( // #0
				array()
			),
			array( // #1
				array( '*' )
			),
			array( // #2
				array( 'de' )
			),
			array( // #3
				array( 'xy', 'de', 'en' )
			),
		);
	}

	protected function makeParserOutput( $langlinks, $noexternallanglinks = array() ) {
		$out = new \ParserOutput();
		$this->langLinkHandler->setNoExternalLangLinks( $out, $noexternallanglinks );

		foreach ( $langlinks as $lang => $link ) {
			$out->addLanguageLink( "$lang:$link" );
		}

		return $out;
	}

	/**
	 * @dataProvider provideGetNoExternalLangLinks
	 */
	public function testGetNoExternalLangLinks( $noexternallanglinks ) {
		$out = $this->makeParserOutput( array(), $noexternallanglinks );
		$nel = $this->langLinkHandler->getNoExternalLangLinks( $out );

		$this->assertEquals( $noexternallanglinks, $nel );
	}

	public static function provideExcludeRepoLinks() {
		return array(
			array( // #0
				array(),
				array(),
				array()
			),
			array( // #1
				array( 'de' ),
				array( 'cs' ),
				array( 'de', 'cs' )
			),
			array(
				array( 'de' ),
				array( '*' ),
				array( 'de', '*' )
			),
			array( // #3
				array( 'xy', 'de', 'en' ),
				array(),
				array( 'xy', 'de', 'en' )
			)
		);
	}

	/**
	 * @dataProvider provideExcludeRepoLinks
	 */
	public function testExcludeRepoLinks( $alreadyExcluded, $toExclude, $expected ) {
		$out = new \ParserOutput();
		$this->langLinkHandler->setNoExternalLangLinks( $out, $alreadyExcluded );
		$this->langLinkHandler->excludeRepoLangLinks( $out, $toExclude );
		$nel = $this->langLinkHandler->getNoExternalLangLinks( $out );

		$this->assertEquals( $expected, $nel );
	}

	public static function provideUseRepoLinks() {
		return array(
			array( // #0
				'Foo_sr',
				array(),
				true
			),
			array( // #1
				'Foo_sr',
				array( '*' ),
				false
			),
			array( // #2
				'Foo_sr',
				array( 'de' ),
				true
			),
			array( // #3
				'Talk:Foo_sr',
				array(),
				false
			),
		);
	}

	/**
	 * @dataProvider provideUseRepoLinks
	 */
	public function testUseRepoLinks( $title, $noexternallanglinks, $expected ) {
		if ( is_string( $title ) ) {
			$title = \Title::newFromText( $title );
			$title->resetArticleID( 1 );
		}

		$out = $this->makeParserOutput( array(), $noexternallanglinks );

		$useRepoLinks = $this->langLinkHandler->useRepoLinks( $title, $out );

		$this->assertEquals( $expected, $useRepoLinks, "use repository links" );
	}

	public static function provideGetEffectiveRepoLinks() {
		return array(
			array( // #0: local overrides remote
				'Foo_sr', // title
				array( // langlinks
					'de' => 'Xoo de',
					'nl' => 'Foo nl',
				),
				array( // noexternallinks
				),
				array( // expectedLinks
					'enwiki' => 'Foo en',
				)
			),
			array( // #1: namespace not covered
				'Talk:Foo_sr', // title
				array( // langlinks
					'de' => 'Talk:Foo de',
					'nl' => 'Talk:Foo nl',
				),
				array( // noexternallinks
				),
				array( // expectedLinks
				)
			),
			array( // #2: disabled
				'Foo_sr', // title
				array( // langlinks
					'de' => 'Foo de',
					'nl' => 'Foo nl',
				),
				array( // noexternallinks
					'*'
				),
				array( // expectedLinks
				)
			),
			array( // #3: suppressed
				'Foo_sr', // title
				array( // langlinks
					'de' => 'Foo de',
					'nl' => 'Foo nl',
				),
				array( // noexternallinks
					'en'
				),
				array( // expectedLinks
				)
			),
			array( // #4: suppressed redundantly
				'Foo_sr', // title
				array( // langlinks
					'de' => 'Foo de',
					'nl' => 'Foo nl',
				),
				array( // noexternallinks
					'de'
				),
				array( // expectedLinks
					'enwiki' => 'Foo en',
				)
			),
		);
	}

	/**
	 * @dataProvider provideGetEffectiveRepoLinks
	 */
	public function testGetEffectiveRepoLinks( $title, $langlinks, $noexternallanglinks, $expectedLinks ) {
		if ( is_string( $title ) ) {
			$title = \Title::newFromText( $title );
		}

		$out = $this->makeParserOutput( $langlinks, $noexternallanglinks );

		$links = $this->langLinkHandler->getEffectiveRepoLinks( $title, $out );

		$this->assertArrayEquals( $expectedLinks, $links, false, true );
	}

	public static function provideAddLinksFromRepository() {
		$cases = self::provideGetEffectiveRepoLinks();

		foreach ( $cases as $i => $case ) {
			// convert associative array to list of links
			$langlinks = self::mapToLinks( $case[1] );
			$expectedLinks = self::mapToLinks( $case[3] );

			// expect the expected effective links plus the provided language links
			$expectedLinks = array_merge( $expectedLinks, $langlinks );

			$cases[$i] = array(
				$case[0],
				$case[1],
				$case[2],
				$expectedLinks
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider provideAddLinksFromRepository
	 */
	public function testAddLinksFromRepository( $title, $langlinks, $noexternallanglinks, $expectedLinks ) {
		if ( is_string( $title ) ) {
			$title = \Title::newFromText( $title );
		}

		$out = $this->makeParserOutput( $langlinks, $noexternallanglinks );

		$this->langLinkHandler->addLinksFromRepository( $title, $out );
		$links = $out->getLanguageLinks();

		$this->assertArrayEquals( $expectedLinks, $links, false, false );
	}

	protected static function mapToLinks( $map ) {
		$links = array();

		foreach ( $map as $wiki => $page ) {
			$lang = preg_replace( '/wiki$/', '', $wiki );
			$links[] = "$lang:$page";
		}

		return $links;
	}

	public static function provideGetSiteGroup() {
		return array(
			array( 'dewiki', 'wikipedia' ),
			array( 'enwiktionary', 'wiktionary' ),
		);
	}

	/**
	 * @dataProvider provideGetSiteGroup
	 */
	public function testGetSiteGroup( $siteId, $group ) {
		$handler = new LangLinkHandler(
			$siteId,
			array(),
			array( NS_TALK ),
			$this->mockRepo,
			\TestSites::newMockSiteStore()
		);

		$this->assertEquals( $group, $handler->getSiteGroup() );
	}

	public static function provideFilterRepoLinksByGroup() {
		return array(
			array( // #0: nothing
				array(), array(), array()
			),
			array( // #1: nothing allowed
				array(
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				),
				array(),
				array()
			),
			array( // #2: nothing there
				array(),
				array( 'wikipedia' ),
				array()
			),
			array( // #3: wikipedia only
				array(
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				),
				array( 'wikipedia' ),
				array(
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
				),
			),
		);
	}

	/**
	 * @dataProvider provideFilterRepoLinksByGroup
	 */
	public function testFilterRepoLinksByGroup( $repoLinks, $allowedGroups, $expectedLinks ) {
		$actualLinks = $this->langLinkHandler->filterRepoLinksByGroup( $repoLinks, $allowedGroups );

		$this->assertEquals( $expectedLinks, $actualLinks );
	}

	public static function provideSuppressRepoLinks() {
		return array(
			array( // #0: nothing
				array(), array(), array()
			),
			array( // #1: nothing allowed
				array(
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'dewiktionary' => 'Foo de word',
					'enwiktionary' => 'Foo en word',
				),
				array( '*' ),
				array()
			),
			array( // #2: nothing there
				array(),
				array( 'de' ),
				array()
			),
			array( // #3: no de
				array(
					'dewiki' => 'Foo de',
					'enwiki' => 'Foo en',
					'srwiki' => 'Foo sr',
					'enwiktionary' => 'Foo en word',
				),
				array( 'de' ),
				array(
					'enwiki' => 'Foo en',
					//NOTE: srwiki is removed because that's a self-link
					'enwiktionary' => 'Foo en word',
				),
			),
		);
	}

	/**
	 * @dataProvider provideSuppressRepoLinks
	 */
	public function testSuppressRepoLinks( $repoLinks, $nel, $expectedLinks ) {
		$out = new \ParserOutput();
		$out->setProperty( 'noexternallanglinks', serialize( $nel ) );

		$actualLinks = $this->langLinkHandler->suppressRepoLinks( $out, $repoLinks );

		$this->assertEquals( $expectedLinks, $actualLinks );
	}
}
