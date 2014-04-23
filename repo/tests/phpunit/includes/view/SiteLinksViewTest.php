<?php

namespace Wikibase\Test;

use MediaWikiSite;
use Wikibase\DataModel\Entity\Item;
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
		$expectedValue
	) {
		$siteLinksView = new SiteLinksView( $siteStore, $sectionEditLinkGenerator );

		$value = $siteLinksView->getHtml( $item, $groups, $editable );
		$this->assertInternalType( 'string', $value );
		$this->assertEquals( $expectedValue, $value );
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
			''
		);

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			Item::newEmpty(),
			array(),
			false,
			''
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

		return $sectionEditLinkGenerator;
	}

	public function getSiteStoreMock() {
		$dummySite = new MediaWikiSite();

		$siteStoreMock = $this->getMockBuilder( '\SiteStore' )
			->disableOriginalConstructor()
			->getMock();

		return $siteStoreMock;
	}

}
