<?php

namespace Wikibase\Client\Tests\Hooks;

use ParserOutput;
use Title;
use Wikibase\Client\Hooks\DataUpdateHookHandlers;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\NamespaceChecker;
use Wikibase\Settings;
use Wikibase\SettingsArray;
use WikiPage;

/**
 * @covers Wikibase\Client\Hooks\DataUpdateHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group WikibaseHooks
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DataUpdateHookHandlersTest extends \MediaWikiTestCase {

	/**
	 * @param array $settings
	 *
	 * @return Settings
	 */
	private function newSettings( array $settings ) {
		$defaults = array(
			'namespaces' => array( NS_MAIN, NS_CATEGORY ),
			'siteGlobalid' => 'enwiki',
		);

		return new SettingsArray( array_merge( $defaults, $settings ) );
	}

	/**
	 * @param Title $title
	 * @param array[]|null $expectedUsages
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
				->method( 'resetUsagesForPage' );
		} else {
			$expectedEntityUsageList = $this->makeEntityUsageList( $expectedUsages );
			$usageUpdater->expects( $this->once() )
				->method( 'resetUsagesForPage' )
				->with( $title->getArticleID(), $expectedEntityUsageList, $touched );
		}

		return $usageUpdater;
	}

	/**
	 * @param array[] $expectedUsages
	 *
	 * @return EntityUsage[]
	 */
	private function makeEntityUsageList( array $expectedUsages ) {
		$entityUsageList = array();

		/** @var EntityId[] $entityIds */
		foreach ( $expectedUsages as $aspect => $entityIds ) {
			foreach ( $entityIds as $id ) {
				$key = $id->getSerialization() . '#' . $aspect;
				$entityUsageList[$key] = new EntityUsage( $id, $aspect );
			}
		}

		return $entityUsageList;
	}

	/**
	 * @param Title $title
	 * @param array[]|null $expectedUsages
	 * @param string|null $touched timestamp
	 *
	 * @return DataUpdateHookHandlers
	 */
	private function newDataUpdateHookHandlers( Title $title, array $expectedUsages = null, $touched = null ) {
		$settings = $this->newSettings( array() );

		$namespaces = $settings->getSetting( 'namespaces' );
		$namespaceChecker = new NamespaceChecker( array(), $namespaces );

		$usageUpdater = $this->newUsageUpdater( $title, $expectedUsages, $touched );

		return new DataUpdateHookHandlers(
			$namespaceChecker,
			$usageUpdater
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

			foreach ( $usages as $aspect => $entityIds ) {
				foreach ( $entityIds as $id ) {
					$acc->addUsage( new EntityUsage( $id, $aspect ) );
				}
			}
		}

		return $output;
	}

	/**
	 * @param Title $title
	 *
	 * @return WikiPage
	 */
	private function newWikiPage( Title $title ) {
		$page = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$page->expects( $this->any() )
			->method( 'getTouched' )
			->will( $this->returnValue( $title->getTouched() ) );

		return $page;
	}

	/**
	 * @param array[]|null $usages
	 *
	 * @return object
	 */
	private function newEditInfo( array $usages = null ) {
		$output = $this->newParserOutput( $usages );

		$editInfo = new \stdClass();
		$editInfo->output = $output;

		return $editInfo;
	}

	public function testNewFromGlobalState() {
		$handler = DataUpdateHookHandlers::newFromGlobalState();
		$this->assertInstanceOf( 'Wikibase\Client\Hooks\DataUpdateHookHandlers', $handler );
	}

	public function provideDoArticleEditUpdates() {
		return array(
			'usage' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				array( EntityUsage::SITELINK_USAGE => array( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ) ),
			),

			'no usage' => array(
				Title::makeTitle( NS_MAIN, 'Oxygen' ),
				array(),
			),

			'ignored-namespace' => array(
				Title::makeTitle( NS_USER, 'Foo' ),
				null,
			),
		);
	}

	/**
	 * @dataProvider provideDoArticleEditUpdates
	 * @param Title $title
	 * @param array[]|null $usage
	 */
	public function testDoArticleEditUpdates( Title $title, $usage ) {
		$title->resetArticleID( 23 );

		$page = $this->newWikiPage( $title );
		$editInfo = $this->newEditInfo( $usage );

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandlers( $title, $usage );
		$handler->doArticleEditUpdates( $page, $editInfo, true );
	}

	public function testDoArticleDeleteComplete() {
		$title = Title::makeTitle( NS_MAIN, 'Oxygen' );
		$title->resetArticleID( 23 );

		$timestamp = '20150505000000';

		// Assertions are done by the UsageUpdater mock
		$handler = $this->newDataUpdateHookHandlers( $title, array(), $timestamp );
		$handler->doArticleDeleteComplete( $title->getNamespace(), $title->getArticleID(), $timestamp );
	}

}
