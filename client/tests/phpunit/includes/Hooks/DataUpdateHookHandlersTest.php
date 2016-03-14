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
 */
class DataUpdateHookHandlersTest extends \MediaWikiTestCase {

	/**
	 * @param Title $title
	 * @param EntityUsage[]|null $expectedUsages
	 * @param string|null $touched
	 * @param bool $prune whether pruneUsagesForPage() should be used
	 * @param bool $add whether addUsagesForPage() should be used
	 *
	 * @return UsageUpdater
	 */
	private function newUsageUpdater(
		Title $title,
		array $expectedUsages = null,
		$touched = null,
		$prune = true,
		$add = true
	) {
		$usageUpdater = $this->getMockBuilder( UsageUpdater::class )
			->disableOriginalConstructor()
			->getMock();

		if ( $touched === null ) {
			$touched = $title->getTouched();
		}

		// NOTE: doLinksUpdateComplete currently uses wfTimestampNow() as the touch date,
		// instead of $title->getTouched(), since that proved to be unreliable. Once that is
		// fixed, this test should check for the exact timestamp, instead of accepting any
		// greater timestamp.
		$touchedMatcher = $this->greaterThanOrEqual( $touched );

		if ( $expectedUsages === null || !$add ) {
			$usageUpdater->expects( $this->never() )
				->method( 'addUsagesForPage' );
		} else {
			$usageUpdater->expects( $this->once() )
				->method( 'addUsagesForPage' )
				->with( $title->getArticleID(), $expectedUsages, $touchedMatcher );
		}

		if ( $prune ) {
			$usageUpdater->expects( $this->once() )
				->method( 'pruneUsagesForPage' )
				->with( $title->getArticleID(), $touchedMatcher );
		} else {
			$usageUpdater->expects( $this->never() )
				->method( 'pruneUsagesForPage' );
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
		$touched = null,
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
								'usages' => $expectedUsageArray,
								'touched' => $touched
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
	 * @param string|null $touched timestamp
	 * @param bool $prune whether pruneUsagesForPage() should be used
	 * @param bool $asyncAdd whether addUsagesForPage() should be called via the job queue
	 *
	 * @return DataUpdateHookHandlers
	 */
	private function newDataUpdateHookHandlers(
		Title $title,
		array $expectedUsages = null,
		$touched = null,
		$prune = true,
		$asyncAdd = false
	) {
		$usageUpdater = $this->newUsageUpdater( $title, $expectedUsages, $touched, $prune, !$asyncAdd );
		$jobScheduler = $this->newJobScheduler( $title, $expectedUsages, $touched, $asyncAdd );

		return new DataUpdateHookHandlers(
			$usageUpdater,
			$jobScheduler
		);
	}

	/**
	 * @param array[]|null $usages
	 * @param string $timestamp
	 *
	 * @return ParserOutput
	 */
	private function newParserOutput( array $usages = null, $timestamp ) {
		$output = new ParserOutput();

		$output->setTimestamp( $timestamp );

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
	 * @param string $touched
	 *
	 * @return Title
	 */
	private function newTitle( $id, $ns, $text, $touched ) {
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

		$title->expects( $this->any() )
			->method( 'getTouched' )
			->will( $this->returnValue( $touched ) );

		return $title;
	}

	/**
	 * @param Title $title
	 * @param EntityUsage[]|null $usages
	 * @param string $touched
	 *
	 * @return LinksUpdate
	 */
	private function newLinksUpdate( Title $title, array $usages = null, $touched ) {
		$pout = $this->newParserOutput( $usages, $touched );

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

	public function provideLinksUpdateComplete() {
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
	 * @dataProvider provideLinksUpdateComplete
	 */
	public function testLinksUpdateComplete( $usage ) {
		$timestamp = '20150505000000';
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen', $timestamp );

		$linksUpdate = $this->newLinksUpdate( $title, $usage, $timestamp );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandlers( $title, $usage, $timestamp, true, false );
		$handler->doLinksUpdateComplete( $linksUpdate );
	}

	/**
	 * @dataProvider provideLinksUpdateComplete
	 */
	public function testDoParserCacheSaveComplete( $usage ) {
		$timestamp = '20150505000000';

		$parserOutput = $this->newParserOutput( $usage, $timestamp );
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen', $timestamp );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandlers( $title, $usage, $timestamp, false, true );
		$handler->doParserCacheSaveComplete( $parserOutput, $title );
	}

	public function testDoArticleDeleteComplete() {
		$timestamp = '20150505000000';
		$title = $this->newTitle( 23, NS_MAIN, 'Oxygen', $timestamp );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandlers( $title, null, $timestamp, true, false );
		$handler->doArticleDeleteComplete( $title->getNamespace(), $title->getArticleID(), $timestamp );
	}

}
