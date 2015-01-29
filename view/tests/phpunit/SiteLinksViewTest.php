<?php

namespace Wikibase\View\Tests;

use PHPUnit_Framework_TestCase;
use Site;
use SiteList;
use ValueFormatters\NumberLocalizer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\SiteLinksView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\SiteLinksView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Mättig
 */
class SiteLinksViewTest extends PHPUnit_Framework_TestCase {

	public function testNoGroups() {
		$html = $this->newInstance()->getHtml( array(), null, array() );

		$this->assertSame( '', $html );
	}

	public function testEmptyGroup() {
		$html = $this->newInstance()->getHtml( array(), null, array( 'wikipedia' ) );

		$this->assertSame(
			'<h2 id="sitelinks" class="wikibase-sitelinks">(wikibase-sitelinks)</h2>'
			. '<GROUP data="wikipedia" class="">'
			. '<h3 id="sitelinks-wikipedia">(wikibase-sitelinks-wikipedia)'
			. '(parentheses: (wikibase-sitelinks-counter: 0, 0, , ))</h3>'
			. '</GROUP>',
			$html
		);
	}

	public function testWikipediaGroup() {
		$siteLinks = array(
			new SiteLink( 'enwiki', 'Title' ),
		);
		$html = $this->newInstance()->getHtml( $siteLinks, null, array( 'wikipedia' ) );

		$this->assertSame(
			'<h2 id="sitelinks" class="wikibase-sitelinks">(wikibase-sitelinks)</h2>'
			. '<GROUP data="wikipedia" class="">'
			. '<h3 id="sitelinks-wikipedia">(wikibase-sitelinks-wikipedia)'
			. '(parentheses: (wikibase-sitelinks-counter: 1, 0, , ))</h3>'
			. '<LINK id="enwiki" lang="en" title="&lt;LANG&gt;">'
			. 'enwiki: <PAGE href="#enwiki" lang="en" dir="auto">Title</PAGE>'
			. '</LINK>'
			. '</GROUP>',
			$html
		);
	}

	public function testSpecialGroup() {
		$siteLinks = array(
			new SiteLink( 'specialwiki', 'Title' ),
		);
		$html = $this->newInstance()->getHtml( $siteLinks, null, array( 'special' ) );

		$this->assertSame(
			'<h2 id="sitelinks" class="wikibase-sitelinks">(wikibase-sitelinks)</h2>'
			. '<GROUP data="special" class="">'
			. '<h3 id="sitelinks-special">(wikibase-sitelinks-special)'
			. '(parentheses: (wikibase-sitelinks-counter: 1, 0, , ))</h3>'
			. '<LINK id="specialwiki" lang="en" title="(wikibase-sitelinks-sitename-specialwiki)">'
			. 'specialwiki: <PAGE href="#specialwiki" lang="en" dir="auto">Title</PAGE>'
			. '</LINK>'
			. '</GROUP>',
			$html
		);
	}

	public function testTwoSiteLinks() {
		$siteLinks = array(
			new SiteLink( 'enwiki', 'Title' ),
			new SiteLink( 'dewiki', 'Titel' ),
		);
		$html = $this->newInstance()->getHtml( $siteLinks, null, array( 'wikipedia' ) );

		$this->assertSame( 2, substr_count( $html, '<LINK' ) );
		$this->assertContains( 'mw-collapsible', $html );
	}

	public function testBadges() {
		$featured = new ItemId( 'Q42' );
		$good = new ItemId( 'Q12' );
		$siteLinks = array(
			new SiteLink( 'enwiki', 'Title', array( $featured ) ),
			new SiteLink( 'dewiki', 'Titel', array( $featured, $good ) ),
		);
		$html = $this->newInstance()->getHtml( $siteLinks, null, array( 'wikipedia' ) );

		$this->assertSame( 3, substr_count( $html, '<BADGE' ) );
		$this->assertContains(
			'<BADGE class="Q42 wb-badge-featuredarticle" id="Q42">Featured article</BADGE>',
			$html
		);
		$this->assertContains(
			'<BADGE class="Q12 wb-badge-goodarticle" id="Q12">Q12</BADGE>',
			$html
		);
	}

	/**
	 * @return SiteLinksView
	 */
	private function newInstance() {
		$templateFactory = new TemplateFactory( [
			'wb-section-heading' => '<h2 id="$2" class="$3">$1</h2>',
			'wikibase-sitelinkgrouplistview' => '$1',
			'wikibase-listview' => '$1',
			'wikibase-sitelinkgroupview' => '<GROUP data="$5" class="$7"><h3 id="$1">$2$3</h3>$6$4</GROUP>',
			'wikibase-sitelinklistview' => '$1',
			'wikibase-sitelinkview' => '<LINK id="$1" lang="$2" title="$5">$4: $6</LINK>',
			'wikibase-sitelinkview-pagename' => '<PAGE href="$1" lang="$4" dir="$5">$2</PAGE>$3',
			'wikibase-badgeselector' => '$1',
			'wb-badge' => '<BADGE class="$1" id="$3">$2</BADGE>',
		] );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( '<LANG>' ) );

		return new SiteLinksView(
			$templateFactory,
			$this->newSiteList(),
			$this->getMock( EditSectionGenerator::class ),
			$this->newEntityIdFormatter(),
			$languageNameLookup,
			$this->newNumberLocalizer(),
			array(
				'Q42' => 'wb-badge-featuredarticle',
				'Q12' => 'wb-badge-goodarticle'
			),
			array( 'special group' ),
			new DummyLocalizedTextProvider()
		);
	}

	private function newNumberLocalizer() {
		$numberLocalizer = $this->getMock( NumberLocalizer::class );
		$numberLocalizer->expects( $this->any() )
			->method( 'localizeNumber' )
			->will( $this->returnCallback( 'strval' ) );
		return $numberLocalizer;
	}

	/**
	 * @return SiteList
	 */
	private function newSiteList() {
		$enWiki = new Site();
		$enWiki->setGlobalId( 'enwiki' );
		$enWiki->setLinkPath( '#enwiki' );
		$enWiki->setLanguageCode( 'en' );
		$enWiki->setGroup( 'wikipedia' );

		$specialWiki = new Site();
		$specialWiki->setGlobalId( 'specialwiki' );
		$specialWiki->setLinkPath( '#specialwiki' );
		$specialWiki->setLanguageCode( 'en' );
		$specialWiki->setGroup( 'special group' );

		$deWiki = new Site();
		$deWiki->setGlobalId( 'dewiki' );
		$deWiki->setLinkPath( '#dewiki' );
		$deWiki->setLanguageCode( 'de' );
		$deWiki->setGroup( 'wikipedia' );

		return new SiteList( array( $enWiki, $specialWiki, $deWiki ) );
	}

	/**
	 * @return EntityIdFormatter
	 */
	private function newEntityIdFormatter() {
		$formatter = $this->getMock( EntityIdFormatter::class );

		$formatter->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				if ( $id->getSerialization() === 'Q42' ) {
					return 'Featured article';
				}

				return $id->getSerialization();
			} ) );

		return $formatter;
	}

}
