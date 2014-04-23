<?php

namespace Wikibase\Test;

use MediaWikiSite;
use SiteList;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\View\SiteLinksView;

/**
 * @covers Wikibase\SiteLinksView
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
class SiteLinksViewTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getHtmlProvider
	 */
	public function testGetHtml(
		$siteStore,
		$sectionEditLinkGenerator,
		$item,
		$groups,
		$editable,
		$expectedValueRegExp
	) {
		$siteLinksView = new SiteLinksView( $siteStore, $sectionEditLinkGenerator );

		$value = $siteLinksView->getHtml( $item, $groups, $editable );
		$this->assertInternalType( 'string', $value );
		$this->assertRegExp( $expectedValueRegExp, $value );
	}

	public function getHtmlProvider() {
		$siteStore = $this->getSiteStoreMock();
		$sectionEditLinkGenerator = $this->getSectionEditLinkGeneratorMock();

		$testCases = array();

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			Item::newEmpty(),
			array(),
			true,
			'/^$/'
		);

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			Item::newEmpty(),
			array(),
			false,
			'/^$/'
		);

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'test' ) );

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			$item,
			array(),
			false,
			'/^$/'
		);

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			$item,
			array( 'wikipedia' ),
			false,
			'/wb-sitelinks-link-enwiki.*test/'
		);

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			$item,
			array( 'wikipedia' ),
			true,
			'/wb-sitelinks-link-enwiki.*test/'
		);

		return $testCases;
	}

	/**
	 * @return SectionEditLinkGenerator
	 */
	protected function getSectionEditLinkGeneratorMock() {
		$sectionEditLinkGenerator = $this->getMockBuilder( 'Wikibase\View\SectionEditLinkGenerator' )
			->disableOriginalConstructor()
			->getMock();

		$sectionEditLinkGenerator->expects( $this->any() )
			->method( 'getEditUrl' )
			->will( $this->returnValue( 'editUrl' ) );

		return $sectionEditLinkGenerator;
	}

	public function getSiteStoreMock() {
		$dummySite = MediaWikiSite::newFromGlobalId( 'enwiki' );
		$dummySite->setGroup( 'wikipedia' );

		$siteStoreMock = $this->getMockBuilder( '\SiteStore' )
			->disableOriginalConstructor()
			->getMock();

		$siteStoreMock->expects( $this->any() )
			->method( 'getSites' )
			->will( $this->returnValue( new SiteList( array( $dummySite ) ) ) );

		return $siteStoreMock;
	}

}
