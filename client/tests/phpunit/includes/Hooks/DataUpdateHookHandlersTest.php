<?php

namespace Wikibase\Client\Tests\Hooks;

use JobQueueGroup;
use LinksUpdate;
use ParserOutput;
use Title;
use Wikibase\Client\Hooks\DataUpdateHookHandlers;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Hooks\DataUpdateHookHandlers
 *
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class DataUpdateHookHandlersTest extends \MediaWikiTestCase {

	/**
	 * @param Title $title
	 * @param EntityUsage[]|null $expectedUsages
	 * @param bool $prune whether pruneUsagesForPage() should be used
	 * @param bool $add whether addUsagesForPage() should be used
	 * @param bool $replace whether replaceUsagesForPage() should be used
	 *
	 * @return UsageUpdater
	 */
	private function newUsageUpdater(
		Title $title,
		array $expectedUsages = null,
		$prune = true,
		$add = true,
		$replace = false
	) {
		$usageUpdater = $this->getMockBuilder( UsageUpdater::class )
			->disableOriginalConstructor()
			->getMock();

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
	 * @param string|null $touched
	 * @param bool $useJobQueue whether we expect the job queue to be used
	 *
	 * @return JobQueueGroup
	 */
	private function newJobScheduler(
		Title $title,
		array $expectedUsages = null,
		$useJobQueue = false
	) {
		$jobScheduler = $this->getMockBuilder( JobQueueGroup::class )
			->disableOriginalConstructor()
			->getMock();

		if ( empty( $expectedUsages ) || !$useJobQueue ) {
			$jobScheduler->expects( $this->never() )
				->method( 'lazyPush' );
		} else {
			$expectedUsageArray = array_map( function ( EntityUsage $usage ) {
				return $usage->asArray();
			}, $expectedUsages );

			$params = array(
				'jobsByWiki' => array(
					wfWikiID() => array(
						array(
							'type' => 'wikibase-addUsagesForPage',
							'params' => array(
								'pageId' => $title->getArticleID(),
								'usages' => $expectedUsageArray
							),
							'opts' => array(
								'removeDuplicates' => true
							),
							'title' => array(
								'ns' => NS_MAIN,
								'key' => 'Oxygen'
							)
						)
					)
				)
			);

			$jobScheduler->expects( $this->once() )
				->method( 'lazyPush' )
				->with( $this->callback( function ( $job ) use ( $params ) {
					self::assertEquals( 'enqueue', $job->getType() );
					self::assertEquals( $params, $job->getParams() );
					return true;
				} ) );

		}

		return $jobScheduler;
	}

	/**
	 * @param Title $title
	 * @param EntityUsage[]|null $expectedUsages
	 * @param bool $prune whether pruneUsagesForPage() should be used
	 * @param bool $asyncAdd whether addUsagesForPage() should be called via the job queue
	 * @param bool $replace whether replaceUsagesForPage() should be used
	 *
	 * @return DataUpdateHookHandlers
	 */
	private function newDataUpdateHookHandlers(
		Title $title,
		array $expectedUsages = null,
		$prune = true,
		$asyncAdd = false,
		$replace = false
	) {
		$usageUpdater = $this->newUsageUpdater( $title, $expectedUsages, $prune, !$asyncAdd, $replace );
		$jobScheduler = $this->newJobScheduler( $title, $expectedUsages, $asyncAdd );

		return new DataUpdateHookHandlers(
			$usageUpdater,
			$jobScheduler
		);
	}

	/**
	 * @param array[]|null $usages
	 *
	 * @return ParserOutput
	 */
	private function newParserOutput( array $usages = null ) {
		$output = new ParserOutput();

		if ( $usages ) {
			$acc = new ParserOutputUsageAccumulator( $output );

			foreach ( $usages as $u ) {
				$acc->addUsage( $u );
			}
		}

		return $output;
	}

	/**
	 * @param int $id
	 * @param int $ns
	 * @param string $text
	 *
	 * @return Title
	 */
	private function newTitle( $id, $ns, $text ) {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( $id ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $ns ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( $text ) );

		return $title;
	}

	/**
	 * @param Title $title
	 * @param EntityUsage[]|null $usages
	 *
	 * @return LinksUpdate
	 */
	private function newLinksUpdate( Title $title, array $usages = null ) {
		$pout = $this->newParserOutput( $usages );

		$linksUpdate = $this->getMockBuilder( LinksUpdate::class )
			->disableOriginalConstructor()
			->getMock();

		$linksUpdate->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$linksUpdate->expects( $this->any() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $pout ) );

		return $linksUpdate;
	}

	public function testNewFromGlobalState() {
		$handler = DataUpdateHookHandlers::newFromGlobalState();
		$this->assertInstanceOf( DataUpdateHookHandlers::class, $handler );
	}

	public function provideEntityUsages() {
		return array(
			'usage' => array(
				array(
					'Q1#S' => new EntityUsage( new ItemId( 'Q1' ), EntityUsage::SITELINK_USAGE ),
					'Q2#T' => new EntityUsage( new ItemId( 'Q2' ), EntityUsage::TITLE_USAGE ),
					'Q2#L' => new EntityUsage( new ItemId( 'Q2' ), EntityUsage::LABEL_USAGE ),
				),
			),

			'no usage' => array(
				array(),
			),
		);
	}

	/**
	 * @dataProvider provideEntityUsages
	 */
	public function testLinksUpdateComplete( $usage ) {
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen' );

		$linksUpdate = $this->newLinksUpdate( $title, $usage );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandlers( $title, $usage, false, false, true );
		$handler->doLinksUpdateComplete( $linksUpdate );
	}

	/**
	 * @dataProvider provideEntityUsages
	 */
	public function testDoParserCacheSaveComplete( $usage ) {
		$parserOutput = $this->newParserOutput( $usage );
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen' );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandlers( $title, $usage, false, true );
		$handler->doParserCacheSaveComplete( $parserOutput, $title );
	}

	public function testDoArticleDeleteComplete() {
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen' );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandlers( $title, null, true, false );
		$handler->doArticleDeleteComplete( $title->getNamespace(), $title->getArticleID() );
	}

}
