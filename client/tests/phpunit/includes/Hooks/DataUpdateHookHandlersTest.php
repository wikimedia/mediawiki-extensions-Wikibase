<?php

namespace Wikibase\Client\Tests\Hooks;

use ParserOutput;
use Title;
use Wikibase\Client\Hooks\DataUpdateHookHandlers;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataModel\Entity\ItemId;
use WikiPage;

/**
 * @covers Wikibase\Client\Hooks\DataUpdateHookHandlers
 *
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DataUpdateHookHandlersTest extends \MediaWikiTestCase {

	/**
	 * @param Title $title
	 * @param EntityUsage[]|null $expectedUsages
	 * @param string|null $touched
	 *
	 * @return UsageUpdater
	 */
	private function newUsageUpdater( Title $title, array $expectedUsages = null, $touched = null ) {
		$usageUpdater = $this->getMockBuilder( 'Wikibase\Client\Store\UsageUpdater' )
			->disableOriginalConstructor()
			->getMock();

		if ( $touched === null ) {
			$touched = $title->getTouched();
		}

		if ( $expectedUsages === null ) {
			$usageUpdater->expects( $this->never() )
				->method( 'addUsagesForPage' );
		} else {
			$usageUpdater->expects( $this->once() )
				->method( 'addUsagesForPage' )
				->with( $title->getArticleID(), $expectedUsages, $touched );
		}

		$usageUpdater->expects( $this->once() )
			->method( 'pruneUsagesForPage' )
			->with( $title->getArticleID(), $touched );

		return $usageUpdater;
	}

	/**
	 * @param Title $title
	 * @param EntityUsage[]|null $expectedUsages
	 * @param string|null $touched timestamp
	 *
	 * @return DataUpdateHookHandlers
	 */
	private function newDataUpdateHookHandlers( Title $title, array $expectedUsages = null, $touched = null ) {
		$usageUpdater = $this->newUsageUpdater( $title, $expectedUsages, $touched );

		return new DataUpdateHookHandlers(
			$usageUpdater
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
	 * @param Title $title
	 * @param EntityUsage[]|null $usages
	 * @param string $touched
	 *
	 * @return WikiPage
	 */
	private function newLinksUpdate( Title $title, array $usages = null, $touched ) {
		$pout = $this->newParserOutput( $usages, $touched );

		$linksUpdate = $this->getMockBuilder( 'LinksUpdate' )
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
		$this->assertInstanceOf( 'Wikibase\Client\Hooks\DataUpdateHookHandlers', $handler );
	}

	public function provideDoArticleEditUpdates() {
		return array(
			'usage' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				array(
					'Q1#S' => new EntityUsage( new ItemId( 'Q1' ),EntityUsage::SITELINK_USAGE ),
					'Q2#T' => new EntityUsage( new ItemId( 'Q2' ),EntityUsage::TITLE_USAGE ),
					'Q2#L' => new EntityUsage( new ItemId( 'Q2' ),EntityUsage::LABEL_USAGE ),
				),
			),

			'no usage' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				array(),
			),
		);
	}

	/**
	 * @dataProvider provideDoArticleEditUpdates
	 */
	public function testDoArticleEditUpdates( Title $title, $usage ) {
		$title->resetArticleID( 23 );
		$timestamp = '20150505000000';

		$linksUpdate = $this->newLinksUpdate( $title, $usage, $timestamp );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandlers( $title, $usage, $timestamp );
		$handler->doLinksUpdateComplete( $linksUpdate );
	}

	public function testDoArticleDeleteComplete() {
		$title = Title::makeTitle( NS_MAIN, 'Oxygen' );
		$title->resetArticleID( 23 );
		$timestamp = '20150505000000';

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandlers( $title, null, $timestamp );
		$handler->doArticleDeleteComplete( $title->getNamespace(), $title->getArticleID(), $timestamp );
	}

}
