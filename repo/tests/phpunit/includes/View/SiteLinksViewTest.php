<?php

namespace Wikibase\Test;

use Language;
use MediaWikiSite;
use SiteList;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
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
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SiteLinksViewTest extends \PHPUnit_Framework_TestCase {

	private $settings = array(
		'specialSiteLinkGroups' => array( 'special group' ),
		'badgeItems' => array(
			'Q42' => 'wb-badge-featuredarticle',
			'Q12' => 'wb-badge-goodarticle'
		)
	);

	public function setUp() {
		parent::setUp();
		$this->switchSettings();
	}

	public function tearDown() {
		parent::tearDown();
		$this->switchSettings();
	}

	private function switchSettings() {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		foreach ( $this->settings as $name => $value ) {
			$this->settings[$name] = $settings->getSetting( $name );
			$settings->setSetting( $name, $value );
		}
	}

	/**
	 * @dataProvider getHtmlProvider
	 */
	public function testGetHtml( Item $item, array $groups, $editable, $expectedValue ) {
		$siteLinksView = $this->getSiteLinksView();

		$value = $siteLinksView->getHtml( $item->getSiteLinks(), $item->getId(), $groups, $editable );
		$this->assertInternalType( 'string', $value );
		$this->assertTag( $expectedValue, $value, $value . ' did not match ' . var_export( $expectedValue, true ) );
	}

	public function getHtmlProvider() {
		$testCases = array();

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q1' ) );
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
		$item->setId( new ItemId( 'Q1' ) );
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
		$item->setId( new ItemId( 'Q1' ) );

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
		$item->setId( new ItemId( 'Q1' ) );
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
		$item->setId( new ItemId( 'Q1' ) );
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

		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$item = Item::newEmpty();
			$item->setId( new ItemId( 'Q1' ) );
			$item->addSiteLink( new SiteLink( 'enwiki', 'en test', array( new ItemId( 'Q42' ) ) ) );
			$item->addSiteLink( new SiteLink( 'dewiki', 'de test', array( new ItemId( 'Q42' ), new ItemId( 'Q12' ) ) ) );

			$testCases[] = array(
				$item,
				array( 'wikipedia' ),
				true,
				array(
					'tag' => 'table',
					'descendant' => array(
						'tag' => 'span',
						'attributes' => array(
							'class' => 'wb-badge wb-badge-Q42 wb-badge-featuredarticle'
						)
					)
				)
			);

			$testCases[] = array(
				$item,
				array( 'wikipedia' ),
				true,
				array(
					'tag' => 'table',
					'descendant' => array(
						'tag' => 'span',
						'attributes' => array(
							'class' => 'wb-badge wb-badge-Q12 wb-badge-goodarticle'
						)
					)
				)
			);
		}

		return $testCases;
	}

	/**
	 * @dataProvider getEmptyHtmlProvider
	 */
	public function testGetEmptyHtml( Item $item, array $groups, $editable ) {
		$siteLinksView = $this->getSiteLinksView();

		$value = $siteLinksView->getHtml( $item->getSiteLinks(), $item->getId(), $groups, $editable );
		$this->assertInternalType( 'string', $value );
		$this->assertEquals( '', $value );
	}

	public function getEmptyHtmlProvider() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q1' ) );

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

		$newItem = Item::newEmpty();

		// item with no id, as happens with new items
		$testCases[] = array(
			$newItem,
			array(),
			true
		);

		return $testCases;
	}

	/**
	 * @return SiteLinksView
	 */
	private function getSiteLinksView() {
		return new SiteLinksView(
			$this->newSiteList(),
			$this->getSectionEditLinkGeneratorMock()
		);
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

	/**
	 * @return SiteList
	 */
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
