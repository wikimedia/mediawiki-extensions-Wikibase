<?php

namespace Wikibase\Client\Test\Store;

use JobRunner;
use MediaWikiTestCase;
use Title;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;
use WikitextContent;

/**
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class UsageTrackingIntegrationTest extends MediaWikiTestCase {

	/**
	 * @var Title
	 */
	private $articleTitle;

	/**
	 * @var Title
	 */
	private $templateTitle;

	public function setUp() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( 'Integration test requires repo and client extension to be active on the same wiki.' );
		}

		parent::setUp();

		$ns = $this->getDefaultWikitextNS();
		$this->articleTitle = Title::makeTitle( $ns, 'UsageTrackingIntegrationTest_Article' );
		$this->templateTitle = Title::makeTitle( NS_TEMPLATE, 'UsageTrackingIntegrationTest_Template' );
	}

	private function runJobs( $type = false ) {
		$runner = new JobRunner();

		$runner->run( array(
			'type'     => $type,
			'maxJobs'  => 20,
			'maxTime'  => 20,
			'throttle' => false,
		) );
	}

	private function deletePage( Title $title ) {
		$page = WikiPage::factory( $title );
		$page->doDeleteArticle( 'TEST' );

		$this->runJobs();

		$title->resetArticleID( false );
	}

	private function updatePage( Title $title, $text ) {
		$content = new WikitextContent( $text );

		$flags = $title->exists() ? EDIT_UPDATE : EDIT_NEW;

		$page = WikiPage::factory( $title );
		$page->doEditContent( $content, 'TEST', $flags );

		$this->runJobs();

		$title->resetArticleID( false );
	}

	private function createItem( ItemId $id, $label ) {
		global $wgUser;

		$item = new Item( $id );
		$item->getFingerprint()->setLabel( 'en', $label );

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $item, 'TEST', $wgUser, EDIT_NEW );
	}

	private function createProperty( PropertyId $id, $label, $type ) {
		global $wgUser;

		$property = new Property( $id, null, $type );
		$property->getFingerprint()->setLabel( 'en', $label );

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $property, 'TEST', $wgUser, EDIT_NEW );
	}

	private function setUpEntities() {
		$this->createItem( new ItemId( 'Q11' ), 'Eleven' );
		$this->createItem( new ItemId( 'Q22' ), 'TwentyTwo' );
		$this->createItem( new ItemId( 'Q33' ), 'ThirtyThree' );

		$this->createProperty( new PropertyId( 'P1' ), 'PropOne', 'string' );
		$this->createProperty( new PropertyId( 'P2' ), 'PropTwo', 'string' );
		$this->createProperty( new PropertyId( 'P3' ), 'PropThree', 'string' );
	}

	public function testUpdateUsageOnCreation() {
		$this->setUpEntities();

		$text = "Just some text\n";
		$text .= "using a property: {{#property:P1|from=Q11}}\n";

		$this->deletePage( $this->templateTitle );
		$this->updatePage( $this->articleTitle, $text );

		$expected = array(
			new EntityUsage( new ItemId( 'Q11' ), EntityUsage::OTHER_USAGE ),
		);

		$this->assertTrackedUsages( $expected, $this->articleTitle );
	}

	/**
	 * @depends testUpdateUsageOnCreation
	 */
	public function testUpdateUsageOnEdit() {
		$text = "{{#property:P2|from=Q22}}\n";
		$this->updatePage( $this->templateTitle, $text );

		sleep(1); // make sure we don't get the same timestamp as the edit before!

		$text = "Just some text\n";
		$text .= "using a template: {{" . $this->templateTitle->getFullText() . "}}\n";
		$this->updatePage( $this->articleTitle, $text );

		$expected = array(
			new EntityUsage( new ItemId( 'Q22' ), EntityUsage::OTHER_USAGE ),
		);

		$this->assertTrackedUsages( $expected, $this->articleTitle );
	}

	/**
	 * @depends testUpdateUsageOnEdit
	 */
	public function testUpdateUsageOnTemplateChange() {
		sleep(1); // make sure we don't get the same timestamp as the edit before!

		$text = "{{#property:P3|from=Q33}}\n";
		$this->updatePage( $this->templateTitle, $text );

		$expected = array(
			new EntityUsage( new ItemId( 'Q33' ), EntityUsage::OTHER_USAGE ),
		);

		$this->assertTrackedUsages( $expected, $this->articleTitle );
	}

	/**
	 * @depends testUpdateUsageOnTemplateChange
	 */
	public function testUpdateUsageOnDelete() {
		sleep(1); // make sure we don't get the same timestamp as the edit before!

		$this->deletePage( $this->articleTitle );

		$this->assertTrackedUsages( array(), $this->articleTitle );
	}

	/**
	 * @param EntityUsage[] $expected
	 * @param Title $title
	 * @param string $msg
	 */
	private function assertTrackedUsages( array $expected, Title $title, $msg = '' ) {
		$lookup = WikibaseClient::getDefaultInstance()->getStore()->getUsageLookup();
		$actual = $lookup->getUsagesForPage( $title->getArticleId() );

		$expectedUsageStrings = $this->getUsageStrings( $expected );
		$actualUsageStrings = $this->getUsageStrings( $actual );

		$this->assertEquals( $expectedUsageStrings, $actualUsageStrings, $msg );
	}

	/**
	 * @param EntityUsage[] $usages
	 *
	 * @return string[]
	 */
	private function getUsageStrings( array $usages ) {
		$strings = array_map( function ( EntityUsage $usage ) {
			return $usage->getIdentityString();
		}, $usages );

		sort( $strings );
		return $strings;
	}
}
