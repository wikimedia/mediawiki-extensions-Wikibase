<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\Hooks;

use JobQueueGroup;
use LinksUpdate;
use MediaWikiIntegrationTestCase;
use ParserOutput;
use Title;
use Wikibase\Client\Hooks\DataUpdateHookHandler;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;

/**
 * @covers \Wikibase\Client\Hooks\DataUpdateHookHandler
 *
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class DataUpdateHookHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @param Title $title
	 * @param EntityUsage[]|null $expectedUsages
	 * @param bool $prune whether pruneUsagesForPage() should be used
	 * @param bool $add whether addUsagesForPage() should be used
	 * @param bool $replace whether replaceUsagesForPage() should be used
	 */
	private function newUsageUpdater(
		Title $title,
		array $expectedUsages = null,
		bool $prune = true,
		bool $add = true,
		bool $replace = false
	): UsageUpdater {
		$usageUpdater = $this->createMock( UsageUpdater::class );

		if ( $expectedUsages === null || $replace || !$add ) {
			$usageUpdater->expects( $this->never() )
				->method( 'addUsagesForPage' );
		} else {
			$usageUpdater->expects( $this->once() )
				->method( 'addUsagesForPage' )
				->with( $title->getArticleID(), $expectedUsages );
		}

		if ( $prune ) {
			$usageUpdater->expects( $this->once() )
				->method( 'pruneUsagesForPage' )
				->with( $title->getArticleID() );
		} else {
			$usageUpdater->expects( $this->never() )
				->method( 'pruneUsagesForPage' );
		}

		if ( $replace ) {
			$usageUpdater->expects( $this->once() )
				->method( 'replaceUsagesForPage' )
				->with( $title->getArticleID(), $expectedUsages );
		} else {
			$usageUpdater->expects( $this->never() )
				->method( 'replaceUsagesForPage' );
		}

		return $usageUpdater;
	}

	/**
	 * @param Title $title
	 * @param array|null $expectedUsages
	 * @param bool $useJobQueue whether we expect the job queue to be used
	 *
	 * @return JobQueueGroup
	 */
	private function newJobScheduler(
		Title $title,
		array $expectedUsages = null,
		bool $useJobQueue = false
	): JobQueueGroup {
		$jobScheduler = $this->createMock( JobQueueGroup::class );

		if ( empty( $expectedUsages ) || !$useJobQueue ) {
			$jobScheduler->expects( $this->never() )
				->method( 'push' );
		} else {
			$expectedUsageArray = array_map( function ( EntityUsage $usage ) {
				return $usage->asArray();
			}, $expectedUsages );

			$params = [
				'pageId' => $title->getArticleID(),
				'usages' => $expectedUsageArray,
				'namespace' => $title->getNamespace(),
				'title' => $title->getDBkey(),
			];

			$jobScheduler->expects( $this->once() )
				->method( 'push' )
				->with( $this->callback( function ( $job ) use ( $params, $title ) {
					$jobParams = $job->getParams();
					// Unrelated parameter used by mw core to tie together logging of jobs
					$jobParams = array_intersect_key( $jobParams, $params );

					self::assertEquals( 'wikibase-addUsagesForPage', $job->getType() );
					self::assertTrue( $job->ignoreDuplicates() );
					self::assertEquals( $params, $jobParams );
					return true;
				} ) );

		}

		return $jobScheduler;
	}

	private function newUsageLookup(
		array $currentUsages = null
	): UsageLookup {
		$usageLookup = $this->createMock( UsageLookup::class );
		$currentUsages = ( $currentUsages == null ) ? [] : $currentUsages;

		$usageLookup->method( 'getUsagesForPage' )
			->willReturn( $currentUsages );

		return $usageLookup;
	}

	/**
	 * @param Title $title
	 * @param EntityUsage[]|null $expectedUsages
	 * @param bool $prune whether pruneUsagesForPage() should be used
	 * @param bool $asyncAdd whether addUsagesForPage() should be called via the job queue
	 * @param bool $replace whether replaceUsagesForPage() should be used
	 */
	private function newDataUpdateHookHandler(
		Title $title,
		array $expectedUsages = null,
		bool $prune = true,
		bool $asyncAdd = false,
		bool $replace = false
	): DataUpdateHookHandler {
		$usageUpdater = $this->newUsageUpdater( $title, $expectedUsages, $prune, !$asyncAdd, $replace );
		$jobScheduler = $this->newJobScheduler( $title, $expectedUsages, $asyncAdd );
		$usageLookup = $this->newUsageLookup();

		return new DataUpdateHookHandler(
			$usageUpdater,
			$jobScheduler,
			$usageLookup,
			$this->newUsageAccumulatorFactory()
		);
	}

	private function newUsageAccumulatorFactory(): UsageAccumulatorFactory {
		return new UsageAccumulatorFactory(
			new EntityUsageFactory( new BasicEntityIdParser() ),
			new UsageDeduplicator( [] ),
			$this->createStub( EntityRedirectTargetLookup::class )
		);
	}

	/**
	 * @param EntityUsage[]|null $usages
	 */
	private function newParserOutput( array $usages = null ): ParserOutput {
		$parserOutput = new ParserOutput();

		if ( $usages ) {
			$acc = $this->newUsageAccumulatorFactory()->newFromParserOutput( $parserOutput );

			foreach ( $usages as $u ) {
				$acc->addUsage( $u );
			}
		}

		return $parserOutput;
	}

	private function newTitle( int $id, int $ns, string $text ): Title {
		$title = $this->createMock( Title::class );

		$title->method( 'getArticleID' )
			->willReturn( $id );

		$title->method( 'getNamespace' )
			->willReturn( $ns );

		$title->method( 'getDBkey' )
			->willReturn( $text );

		return $title;
	}

	/**
	 * @param Title $title
	 * @param EntityUsage[]|null $usages
	 */
	private function newLinksUpdate( Title $title, array $usages = null ): LinksUpdate {
		$pout = $this->newParserOutput( $usages );

		$linksUpdate = $this->createMock( LinksUpdate::class );

		$linksUpdate->method( 'getPageId' )
			->willReturn( $title->getArticleID() );

		$linksUpdate->method( 'getTitle' )
			->willReturn( $title );

		$linksUpdate->method( 'getParserOutput' )
			->willReturn( $pout );

		return $linksUpdate;
	}

	public function provideEntityUsages(): array {
		return [
			'usage' => [
				[
					'Q1#S' => new EntityUsage( new ItemId( 'Q1' ), EntityUsage::SITELINK_USAGE ),
					'Q2#T' => new EntityUsage( new ItemId( 'Q2' ), EntityUsage::TITLE_USAGE ),
					'Q2#L' => new EntityUsage( new ItemId( 'Q2' ), EntityUsage::LABEL_USAGE ),
				],
			],

			'no usage' => [
				[],
			],
		];
	}

	/**
	 * @dataProvider provideEntityUsages
	 */
	public function testLinksUpdateComplete( ?array $usages ): void {
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen' );

		$linksUpdate = $this->newLinksUpdate( $title, $usages );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandler( $title, $usages, false, false, true );
		$handler->doLinksUpdateComplete( $linksUpdate );
	}

	public function testLinksUpdateComplete_noPageId(): void {
		$title = $this->newTitle( 0, NS_MAIN, 'Oh no' );

		$linksUpdate = $this->newLinksUpdate( $title, null );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandler( $title, null, false, true, false );
		$handler->doLinksUpdateComplete( $linksUpdate );
	}

	/**
	 * @dataProvider provideEntityUsages
	 */
	public function testDoParserCacheSaveComplete( ?array $usages ): void {
		$parserOutput = $this->newParserOutput( $usages );
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen' );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandler( $title, $usages, false, true );
		$handler->onParserCacheSaveComplete( null, $parserOutput, $title, null, null );
	}

	public function testDoParserCacheSaveCompleteNoChangeEntityUsage(): void {
		$usages = [
			'Q1#S' => new EntityUsage( new ItemId( 'Q1' ), EntityUsage::SITELINK_USAGE ),
		];
		$parserOutput = $this->newParserOutput( $usages );
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen' );

		// Assertions are done by the JobScheduler mock
		$usageUpdater = $this->createMock( UsageUpdater::class );

		$jobScheduler = $this->newJobScheduler( $title, $usages, false );
		$usageLookup = $this->newUsageLookup( $usages );

		$handler = new DataUpdateHookHandler(
			$usageUpdater,
			$jobScheduler,
			$usageLookup,
			$this->newUsageAccumulatorFactory()
		);
		$handler->onParserCacheSaveComplete( null, $parserOutput, $title, null, null );
	}

	public function testDoParserCacheSaveCompletePartialUpdate(): void {
		$newUsages = [
			'Q1#S' => new EntityUsage( new ItemId( 'Q1' ), EntityUsage::SITELINK_USAGE ),
			'Q2#O' => new EntityUsage( new ItemId( 'Q2' ), EntityUsage::OTHER_USAGE ),
			'Q2#L' => new EntityUsage( new ItemId( 'Q2' ), EntityUsage::LABEL_USAGE ),
		];
		$currentUsages = [
			'Q1#S' => new EntityUsage( new ItemId( 'Q1' ), EntityUsage::SITELINK_USAGE ),
			'Q2#T' => new EntityUsage( new ItemId( 'Q2' ), EntityUsage::TITLE_USAGE ),
			'Q2#L' => new EntityUsage( new ItemId( 'Q2' ), EntityUsage::LABEL_USAGE ),
		];
		$expected = [
			'Q2#O' => new EntityUsage( new ItemId( 'Q2' ), EntityUsage::OTHER_USAGE ),
		];
		$parserOutput = $this->newParserOutput( $newUsages );
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen' );

		// Assertions are done by the JobScheduler mock
		$usageUpdater = $this->createMock( UsageUpdater::class );

		$jobScheduler = $this->newJobScheduler( $title, $expected, true );
		$usageLookup = $this->newUsageLookup( $currentUsages );

		$handler = new DataUpdateHookHandler(
			$usageUpdater,
			$jobScheduler,
			$usageLookup,
			$this->newUsageAccumulatorFactory()
		);
		$handler->onParserCacheSaveComplete( null, $parserOutput, $title, null, null );
	}

	public function testDoArticleDeleteComplete(): void {
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen' );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandler( $title, null, true, false );
		$handler->onArticleDeleteComplete( null, null, null, $title->getArticleID(), null, null, null );
	}

}
