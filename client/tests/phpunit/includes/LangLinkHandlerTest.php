<?php

namespace Wikibase\Test;
use \Wikibase\LangLinkHandler;
use \Wikibase\SiteLink;

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
 *        ^--- uses DB indirectly; may be removed if Sites is mocked out
 *             and Title is made not to use the database.
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
		array( // matching item
			'id' => 1,
			'label' => array( 'en' => 'Foo' ),
			'links' => array(
				'testwiki' => 'Foo',
				'dewiki' => 'Foo_de',
				'enwiki' => 'Foo_en',
			)
		),
		array( // matches, but not in a namespace with external langlinks enabled
			'id' => 2,
			'label' => array( 'en' => 'Talk:Foo' ),
			'links' => array(
				'testwiki' => 'Talk:Foo',
				'dewiki' => 'Talk:Foo_de',
				'enwiki' => 'Talk:Foo_en',
			)
		)
	);

	public function setUp() {
		parent::setUp();

		static $hasSites = false;

		if ( !$hasSites ) {
			$hasSites = true;
			//TODO: use mock SiteList instead!
			\TestSites::insertIntoDb();
		}

		$this->mockRepo = new MockRepository();

		foreach ( self::$itemData as $data ) {
			$item = new \Wikibase\Item( $data );
			$this->mockRepo->putEntity( $item );
		}

		$this->langLinkHandler = new \Wikibase\LangLinkHandler(
			'testwiki',
			array( NS_MAIN ),
			$this->mockRepo,
			\SiteSQLStore::newInstance()
		);
	}

	public static function provideGetEntityLinks() {
		return array(
			array( // #0
				'Xoo', // page
				array() // expected links
			),
			array( // #1
				'Foo', // page
				array( // expected links
					'testwiki' => 'Foo',
					'dewiki' => 'Foo_de',
					'enwiki' => 'Foo_en',
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

		$links = $this->langLinkHandler->getEntityLinks( $title );
		$links = SiteLink::siteLinksToArray( $links );

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

		LangLinkHandler::setNoExternalLangLinks( $out, $noexternallanglinks );

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

		$nel = LangLinkHandler::getNoExternalLangLinks( $out );

		$this->assertEquals( $noexternallanglinks, $nel );
	}

	public static function provideUseRepoLinks() {
		return array(
			array( // #0
				'Foo',
				array(),
				true
			),
			array( // #1
				'Foo',
				array( '*' ),
				false
			),
			array( // #2
				'Foo',
				array( 'de' ),
				true
			),
			array( // #3
				'Talk:Foo',
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
		}

		$out = $this->makeParserOutput( array(), $noexternallanglinks );

		$useRepoLinks = $this->langLinkHandler->useRepoLinks( $title, $out );

		$this->assertEquals( $expected, $useRepoLinks, "use repository links" );
	}

	public static function provideGetEffectiveRepoLinks() {
		return array(
			array( // #0: local overrides remote
				'Foo', // title
				array( // langlinks
					'de' => 'Xoo_de',
					'nl' => 'Foo_nl',
				),
				array( // noexternallinks
				),
				array( // expectedLinks
					'enwiki' => 'Foo_en',
				)
			),
			array( // #1: namespace not covered
				'Talk:Foo', // title
				array( // langlinks
					'de' => 'Talk:Foo_de',
					'nl' => 'Talk:Foo_nl',
				),
				array( // noexternallinks
				),
				array( // expectedLinks
				)
			),
			array( // #2: disabled
				'Foo', // title
				array( // langlinks
					'de' => 'Foo_de',
					'nl' => 'Foo_nl',
				),
				array( // noexternallinks
					'*'
				),
				array( // expectedLinks
				)
			),
			array( // #3: suppressed
				'Foo', // title
				array( // langlinks
					'de' => 'Foo_de',
					'nl' => 'Foo_nl',
				),
				array( // noexternallinks
					'en'
				),
				array( // expectedLinks
				)
			),
			array( // #4: suppressed redundantly
				'Foo', // title
				array( // langlinks
					'de' => 'Foo_de',
					'nl' => 'Foo_nl',
				),
				array( // noexternallinks
					'de'
				),
				array( // expectedLinks
					'enwiki' => 'Foo_en',
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
}
