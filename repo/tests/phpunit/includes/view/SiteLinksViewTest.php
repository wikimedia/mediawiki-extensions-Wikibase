<?php

namespace Wikibase\Test;

use MediaWikiSite;
use SiteList;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\WikibaseRepo;
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

	private $specialSiteLinkGroups = array( 'special group' );

	private $oldSpecialSiteLinkGroups;

	public function setUp() {
		parent::setUp();
		$this->oldSpecialSiteLinkGroups = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'specialSiteLinkGroups' );
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'specialSiteLinkGroups', $this->specialSiteLinkGroups );
	}

	public function tearDown() {
		parent::tearDown();
		WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'specialSiteLinkGroups', $this->oldSpecialSiteLinkGroups );
	}

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
		$this->assertTag( $expectedValue, $value, $value . ' did not match ' . var_export( $expectedValue, true ) );
	}

	public function getHtmlProvider() {
		$siteStore = $this->getSiteStoreMock();
		$sectionEditLinkGenerator = $this->getSectionEditLinkGeneratorMock();

		$testCases = array();

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'test' ) );

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			$item,
			array( 'wikipedia' ),
			false,
			array(
				'tag' => 'table',
				'attributes' => array(
					'data-wb-sitelinks-group' => 'wikipedia'
				),
				'descendant' => array(
					'tag' => 'td',
					'class' => 'wb-sitelinks-link-enwiki',
					'content' => 'test'
				)
			)
		);

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			$item,
			array( 'wikipedia' ),
			true,
			array(
				'tag' => 'table',
				'attributes' => array(
					'data-wb-sitelinks-group' => 'wikipedia'
				),
				'descendant' => array(
					'tag' => 'td',
					'class' => 'wb-sitelinks-link-enwiki',
					'content' => 'test'
				)
			)
		);

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'specialwiki', 'test' ) );

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			$item,
			array( 'special group' ),
			true,
			array(
				'tag' => 'table',
				'attributes' => array(
					'data-wb-sitelinks-group' => 'special'
				),
				'child' => array(
					'tag' => 'thead',
					'child' => array(
						'tag' => 'tr'
					)
				)
			)
		);

		$item = Item::newEmpty();

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			$item,
			array( 'wikipedia', 'special group' ),
			true,
			array(
				'tag' => 'table',
				'child' => array(
					'tag' => 'thead',
					'content' => ''
				)
			)
		);

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'dewiki', 'test' ) );
		$item->addSiteLink( new SiteLink( 'enwiki', 'test2' ) );

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			$item,
			array( 'wikipedia' ),
			true,
			array(
				'tag' => 'table',
			)
		);

		return $testCases;
	}

	/**
	 * @dataProvider getEmptyHtmlProvider
	 */
	public function testGetEmptyHtml(
		$siteStore,
		$sectionEditLinkGenerator,
		$item,
		$groups,
		$editable
	) {
		$siteLinksView = new SiteLinksView( $siteStore, $sectionEditLinkGenerator );

		$value = $siteLinksView->getHtml( $item, $groups, $editable );
		$this->assertInternalType( 'string', $value );
		$this->assertEquals( '', $value );
	}

	public function getEmptyHtmlProvider() {
		$siteStore = $this->getSiteStoreMock();
		$sectionEditLinkGenerator = $this->getSectionEditLinkGeneratorMock();

		$testCases = array();

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			Item::newEmpty(),
			array(),
			true,
		);

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			Item::newEmpty(),
			array(),
			false,
		);

		$item = Item::newEmpty();
		$item->addSiteLink( new SiteLink( 'enwiki', 'test' ) );

		$testCases[] = array(
			$siteStore,
			$sectionEditLinkGenerator,
			$item,
			array(),
			false,
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

		$dummySite2 = MediaWikiSite::newFromGlobalId( 'specialwiki' );
		$dummySite2->setGroup( 'special group' );

		$dummySite3 = MediaWikiSite::newFromGlobalId( 'dewiki' );
		$dummySite3->setGroup( 'wikipedia' );

		$siteStoreMock = $this->getMockBuilder( '\SiteStore' )
			->disableOriginalConstructor()
			->getMock();

		$siteStoreMock->expects( $this->any() )
			->method( 'getSites' )
			->will( $this->returnValue( new SiteList( array( $dummySite, $dummySite2, $dummySite3 ) ) ) );

		return $siteStoreMock;
	}

}
