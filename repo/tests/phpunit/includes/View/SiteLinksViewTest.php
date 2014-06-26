<?php

namespace Wikibase\Test;

use MediaWikiSite;
use SiteList;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\View\SiteLinksView;

/**
 * @covers Wikibase\Repo\View\SiteLinksView
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
	public function testGetHtml( Item $item, array $groups, $editable, $expectedValue ) {
		$siteLinksView = new SiteLinksView( $this->newSiteList(), $this->getSectionEditLinkGeneratorMock() );

		$value = $siteLinksView->getHtml( $item->getSiteLinks(), $item->getId(), $groups, $editable );
		$this->assertInternalType( 'string', $value );
		$this->assertTag( $expectedValue, $value, $value . ' did not match ' . var_export( $expectedValue, true ) );
	}

	public function getHtmlProvider() {
		$testCases = array();

		$item = Item::newEmpty();
		$item->setId( EntityId::newFromPrefixedId( 'Q1' ) );
		$item->addSiteLink( new SiteLink( 'enwiki', 'test' ) );

		$testCases[] = array(
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
		$item->setId( EntityId::newFromPrefixedId( 'Q1' ) );
		$item->addSiteLink( new SiteLink( 'specialwiki', 'test' ) );

		$testCases[] = array(
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
		$item->setId( EntityId::newFromPrefixedId( 'Q1' ) );

		$testCases[] = array(
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
		$item->setId( EntityId::newFromPrefixedId( 'Q1' ) );
		$item->addSiteLink( new SiteLink( 'dewiki', 'test' ) );
		$item->addSiteLink( new SiteLink( 'enwiki', 'test2' ) );

		$testCases[] = array(
			$item,
			array( 'wikipedia' ),
			true,
			array(
				'tag' => 'table',
				'child' => array(
					'tag' => 'tfoot',
					'descendant' => array(
						'class' => 'wikibase-toolbarbutton-disabled'
					)
				)
			)
		);

		$item = Item::newEmpty();
		$item->setId( EntityId::newFromPrefixedId( 'Q1' ) );
		$item->addSiteLink( new SiteLink( 'dewiki', 'test' ) );
		$item->addSiteLink( new SiteLink( 'nonexistingwiki', 'test2' ) );

		$testCases[] = array(
			$item,
			array( 'wikipedia' ),
			true,
			array(
				'tag' => 'table',
				'child' => array(
					'tag' => 'tfoot',
					'descendant' => array(
						'class' => 'wikibase-toolbarbutton-enabled'
					)
				)
			)
		);

		return $testCases;
	}

	/**
	 * @dataProvider getEmptyHtmlProvider
	 */
	public function testGetEmptyHtml( Item $item, array $groups, $editable ) {
		$siteLinksView = new SiteLinksView( $this->newSiteList(), $this->getSectionEditLinkGeneratorMock() );

		$value = $siteLinksView->getHtml( $item->getSiteLinks(), $item->getId(), $groups, $editable );
		$this->assertInternalType( 'string', $value );
		$this->assertEquals( '', $value );
	}

	public function getEmptyHtmlProvider() {
		$item = Item::newEmpty();
		$item->setId( EntityId::newFromPrefixedId( 'Q1' ) );

		$testCases = array();

		$testCases[] = array(
			$item,
			array(),
			true,
		);

		$testCases[] = array(
			$item,
			array(),
			false,
		);

		$item->addSiteLink( new SiteLink( 'enwiki', 'test' ) );

		$testCases[] = array(
			$item,
			array(),
			false,
		);

		return $testCases;
	}

	/**
	 * @return SectionEditLinkGenerator
	 */
	private function getSectionEditLinkGeneratorMock() {
		$sectionEditLinkGenerator = $this->getMockBuilder( 'Wikibase\Repo\View\SectionEditLinkGenerator' )
			->disableOriginalConstructor()
			->getMock();

		$sectionEditLinkGenerator->expects( $this->any() )
			->method( 'getEditUrl' )
			->will( $this->returnValue( 'editUrl' ) );

		$sectionEditLinkGenerator->expects( $this->any() )
			->method( 'getHtmlForEditSection' )
			->will( $this->returnCallback( function ( $url, $msg, $tag, $enabled ) {
				if( $enabled ) {
					return '<a class="wikibase-toolbarbutton-enabled">Edit link</a>';
				} else {
					return '<a class="wikibase-toolbarbutton-disabled">Disabled edit link</a>';
				}
			} ) );

		return $sectionEditLinkGenerator;
	}

	private function newSiteList() {
		$dummySite = MediaWikiSite::newFromGlobalId( 'enwiki' );
		$dummySite->setGroup( 'wikipedia' );

		$dummySite2 = MediaWikiSite::newFromGlobalId( 'specialwiki' );
		$dummySite2->setGroup( 'special group' );

		$dummySite3 = MediaWikiSite::newFromGlobalId( 'dewiki' );
		$dummySite3->setGroup( 'wikipedia' );

		return new SiteList( array( $dummySite, $dummySite2, $dummySite3 ) );
	}

}
