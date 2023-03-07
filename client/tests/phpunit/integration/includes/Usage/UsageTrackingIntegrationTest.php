<?php

namespace Wikibase\Client\Tests\Integration\Usage;

use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\WikibaseSettings;
use WikitextContent;

/**
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @covers \Wikibase\Client\Store\UsageUpdater
 * @covers \Wikibase\Client\Usage\Sql\SqlUsageTracker
 */
class UsageTrackingIntegrationTest extends MediaWikiIntegrationTestCase {

	/** @var Title */
	private $articleTitle;

	/** @var Title */
	private $templateTitle;

	/** @var bool */
	private $oldAllowDataTransclusion;

	protected function setUp(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( 'Integration test requires repo and client extension to be active on the same wiki.' );
		}

		parent::setUp();
		$this->tablesUsed[] = 'page';

		// Disable caching to avoid Q33 vs Q22 mixup
		$this->setMainCache( CACHE_NONE );

		$settings = WikibaseClient::getSettings();
		$this->oldAllowDataTransclusion = $settings->getSetting( 'allowDataTransclusion' );
		$settings->setSetting( 'allowDataTransclusion', true );

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->method( 'hasEntity' )
			->willReturnCallback( static function ( EntityId $id ) {
				// statement usage is only tracked for existing properties,
				// so pretend all properties exist
				return $id instanceof NumericPropertyId;
			} );
		$this->setService( 'WikibaseClient.EntityLookup', $entityLookup );

		$ns = $this->getDefaultWikitextNS();
		$this->articleTitle = Title::makeTitle( $ns, 'UsageTrackingIntegrationTest_Article' );
		$this->templateTitle = Title::makeTitle( NS_TEMPLATE, 'UsageTrackingIntegrationTest_Template' );
	}

	protected function tearDown(): void {
		parent::tearDown();

		WikibaseClient::getSettings()->setSetting(
			'allowDataTransclusion',
			$this->oldAllowDataTransclusion
		);
	}

	private function runRefreshLinksJobs() {
		$this->runJobs( [ 'minJobs' => 0 ], [ 'type' => 'refreshLinks' ] );
	}

	private function deletePageWithPostUpdates( Title $title ) {
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$page->doDeleteArticleReal( 'TEST', $this->getTestSysop()->getUser() );

		$this->runRefreshLinksJobs();

		$title->resetArticleID( false );
	}

	private function updatePage( Title $title, $text ) {
		$content = new WikitextContent( $text );

		$flags = $title->exists() ? EDIT_UPDATE : EDIT_NEW;

		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$page->doUserEditContent(
			$content,
			$this->getTestUser()->getUser(),
			'TEST',
			$flags
		);

		$this->runRefreshLinksJobs();

		$title->resetArticleID( false );
	}

	public function testUpdateUsage() {
		// Create a new page that uses Q11.
		$text = "Just some text\n";
		$text .= "using a property: {{#property:P123|from=Q11}}\n";
		$this->updatePage( $this->articleTitle, $text );

		// Check that the usage of Q11 is tracked.
		$expected = [
			new EntityUsage( new ItemId( 'Q11' ), EntityUsage::STATEMENT_USAGE, 'P123' ),
		];
		$this->assertTrackedUsages( $expected, $this->articleTitle );

		// Create the template we'll use below.
		$text = "{{#property:P123|from=Q22}}\n";
		$this->updatePage( $this->templateTitle, $text );

		// Change page content to use the template instead of {{#property}} directly.
		$text = "Just some text\n";
		$text .= "using a template: {{" . $this->templateTitle->getPrefixedText() . "}}\n";
		$this->updatePage( $this->articleTitle, $text );

		// Check that Q22, used via the template, is now tracked.
		// Check that Q11 is no longer tracked, due to timestamp-based pruning.
		$expected = [
			new EntityUsage( new ItemId( 'Q22' ), EntityUsage::STATEMENT_USAGE, 'P123' ),
		];
		$this->assertTrackedUsages( $expected, $this->articleTitle );

		// Change the template to use Q33.
		$text = "{{#property:P123|from=Q33}}\n";
		$this->updatePage( $this->templateTitle, $text );

		// Check that Q33, now used via the template, is tracked.
		// Check that Q22 is no longer tracked, due to timestamp-based pruning.
		$expected = [
			new EntityUsage( new ItemId( 'Q33' ), EntityUsage::STATEMENT_USAGE, 'P123' ),
		];
		$this->assertTrackedUsages( $expected, $this->articleTitle );

		// Delete the page.
		$this->deletePageWithPostUpdates( $this->articleTitle );

		// Make sure tracking has been removed for all usages on the deleted page.
		$this->assertTrackedUsages( [], $this->articleTitle );
	}

	/**
	 * @param EntityUsage[] $expected
	 * @param Title $title
	 * @param string $msg
	 */
	private function assertTrackedUsages( array $expected, Title $title, $msg = '' ) {
		$lookup = WikibaseClient::getStore()->getUsageLookup();
		$actual = $lookup->getUsagesForPage( $title->getArticleID() );

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
