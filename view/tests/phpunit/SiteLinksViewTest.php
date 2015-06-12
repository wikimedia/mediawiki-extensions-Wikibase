<?php

namespace Wikibase\Test;

use MediaWikiSite;
use MediaWikiTestCase;
use SiteList;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\SiteLinksView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\SiteLinksView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SiteLinksViewTest extends MediaWikiTestCase {

	/**
	 * @dataProvider getHtmlProvider
	 */
	public function testGetHtml( Item $item, array $groups, $expectedValue ) {
		$siteLinksView = $this->getSiteLinksView();

		$value = $siteLinksView->getHtml( $item->getSiteLinks(), $item->getId(), $groups );
		$this->assertInternalType( 'string', $value );
		$this->assertTag( $expectedValue, $value, $value . ' did not match ' . var_export( $expectedValue, true ) );

		$this->assertContains( '<h2 class="wb-section-heading section-heading wikibase-sitelinks" dir="auto"><span id="sitelinks"', $value, 'Html should contain section heading' );
	}

	public function getHtmlProvider() {
		$testCases = array();

		$item = new Item( new ItemId( 'Q1' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'test' );

		$testCases[] = array(
			$item,
			array( 'wikipedia' ),
			array(
				'tag' => 'div',
				'attributes' => array(
					'data-wb-sitelinks-group' => 'wikipedia'
				),
				'descendant' => array(
					'tag' => 'span',
					'class' => 'wikibase-sitelinkview-link-enwiki',
					'content' => 'test'
				)
			)
		);

		$item = new Item( new ItemId( 'Q1' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'specialwiki', 'test' );

		$testCases[] = array(
			$item,
			array( 'special' ),
			array(
				'tag' => 'div',
				'attributes' => array(
					'data-wb-sitelinks-group' => 'special'
				),
			)
		);

		$item = new Item( new ItemId( 'Q1' ) );
		$item->addSiteLink( new SiteLink( 'enwiki', 'en test', array( new ItemId( 'Q42' ) ) ) );
		$item->addSiteLink( new SiteLink( 'dewiki', 'de test', array( new ItemId( 'Q42' ), new ItemId( 'Q12' ) ) ) );

		$testCases[] = array(
			$item,
			array( 'wikipedia' ),
			array(
				'tag' => 'div',
				'descendant' => array(
					'tag' => 'span',
					'attributes' => array(
						'class' => 'wb-badge wb-badge-Q42 wb-badge-featuredarticle',
						'title' => 'Featured article'
					)
				)
			)
		);

		$testCases[] = array(
			$item,
			array( 'wikipedia' ),
			array(
				'tag' => 'div',
				'descendant' => array(
					'tag' => 'span',
					'attributes' => array(
						'class' => 'wb-badge wb-badge-Q12 wb-badge-goodarticle',
						'title' => 'Q12'
					)
				)
			)
		);

		return $testCases;
	}

	/**
	 * @dataProvider getEmptyHtmlProvider
	 */
	public function testGetEmptyHtml( Item $item, array $groups ) {
		$siteLinksView = $this->getSiteLinksView();

		$value = $siteLinksView->getHtml( $item->getSiteLinks(), $item->getId(), $groups );
		$this->assertInternalType( 'string', $value );
		$this->assertEquals( '', $value );
	}

	public function getEmptyHtmlProvider() {
		$item = new Item( new ItemId( 'Q1' ) );

		$testCases = array();

		$testCases[] = array(
			$item,
			array(),
		);

		/** @var Item $item */
		$item = $item->copy();
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'test' );

		$testCases[] = array(
			$item,
			array()
		);

		$newItem = new Item();

		// item with no id, as happens with new items
		$testCases[] = array(
			$newItem,
			array()
		);

		return $testCases;
	}

	/**
	 * @return SiteLinksView
	 */
	private function getSiteLinksView() {
		$templateFactory = TemplateFactory::getDefaultInstance();

		return new SiteLinksView(
			$templateFactory,
			$this->newSiteList(),
			$this->getEditSectionGeneratorMock(),
			$this->getEntityIdFormatterMock(),
			new LanguageNameLookup(),
			array(
				'Q42' => 'wb-badge-featuredarticle',
				'Q12' => 'wb-badge-goodarticle'
			),
			array( 'special group' )
		);
	}

	/**
	 * @return EditSectionGenerator
	 */
	private function getEditSectionGeneratorMock() {
		$editSectionGenerator = $this->getMock( 'Wikibase\View\EditSectionGenerator' );

		return $editSectionGenerator;
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

	/**
	 * @return EntityIdFormatter
	 */
	private function getEntityIdFormatterMock() {
		$entityIdFormatter = $this->getMock( 'Wikibase\Lib\EntityIdFormatter' );

		$entityIdFormatter->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				if ( $id->getSerialization() === 'Q42' ) {
					return 'Featured article';
				}

				return $id->getSerialization();
			} ) );

		return $entityIdFormatter;
	}

}
