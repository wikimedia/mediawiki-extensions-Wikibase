<?php

namespace Wikibase\Client\Tests\Changes;

use IJobSpecification;
use Job;
use JobQueueGroup;
use PHPUnit_Framework_MockObject_MockObject;
use RefreshLinksJob;
use Title;
use Wikibase\Client\Changes\WikiPageUpdater;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\EntityChange;
use Wikimedia\Rdbms\LBFactory;

/**
 * @covers Wikibase\Client\Changes\WikiPageUpdater
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 *
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class WikiPageUpdaterTest extends \MediaWikiTestCase {

	/**
	 * @return JobQueueGroup|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getJobQueueGroupMock() {
		$jobQueueGroup = $this->getMockBuilder( JobQueueGroup::class )
			->disableOriginalConstructor()
			->getMock();

		return $jobQueueGroup;
	}

	/**
	 * @return RecentChangeFactory
	 */
	private function getRCFactoryMock() {
		$rcFactory = $this->getMockBuilder( RecentChangeFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$rcFactory->expects( $this->any() )
			->method( 'prepareChangeAttributes' )
			->will( $this->returnValue( [] ) );

		return $rcFactory;
	}

	/**
	 * @return RecentChangesDuplicateDetector
	 */
	private function getRCDupeDetectorMock() {
		$rcDupeDetector = $this->getMockBuilder( RecentChangesDuplicateDetector::class )
			->disableOriginalConstructor()
			->getMock();

		return $rcDupeDetector;
	}

	/**
	 * @param string $text
	 * @param int $id
	 *
	 * @return Title|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getTitleMock( $text, $id = 23 ) {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( $id ) );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedDBkey' )
			->will( $this->returnValue( $text ) );

		$title->expects( $this->any() )
			->method( 'getDBkey' )
			->will( $this->returnValue( $text ) );

		$title->expects( $this->any() )
			->method( 'getText' )
			->will( $this->returnValue( $text ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );

		$title->expects( $this->any() )
			->method( 'getNsText' )
			->will( $this->returnValue( '' ) );

		return $title;
	}

	/**
	 * @param int $id
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject|EntityChange
	 */
	private function getEntityChangeMock( $id = 77 ) {
		$change = $this->getMockBuilder( EntityChange::class )
			->disableOriginalConstructor()
			->getMock();

		$change->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( $id ) );

		return $change;
	}

	/**
	 * @return LBFactory
	 */
	private function getLBFactoryMock() {
		$LBFactory = $this->getMockBuilder( LBFactory::class )
			->disableOriginalConstructor()
			->getMock();

		return $LBFactory;
	}

	public function testPurgeParserCache() {
		$updater = new WikiPageUpdater(
			$this->getJobQueueGroupMock(),
			$this->getRCFactoryMock(),
			$this->getLBFactoryMock(),
			$this->getRCDupeDetectorMock()
		);

		$title = $this->getTitleMock( 'Foo' );

		$title->expects( $this->once() )
			->method( 'invalidateCache' );

		$updater->purgeParserCache( [
			$title,
		] );
	}

	public function testPurgeWebCache() {
		$updater = new WikiPageUpdater(
			$this->getJobQueueGroupMock(),
			$this->getRCFactoryMock(),
			$this->getLBFactoryMock(),
			$this->getRCDupeDetectorMock()
		);

		$title = $this->getTitleMock( 'Foo' );
		$title->expects( $this->once() )
			->method( 'purgeSquid' );

		$updater->purgeWebCache( [
			$title,
		] );
	}

	public function testScheduleRefreshLinks() {
		$titleFoo = $this->getTitleMock( 'Foo', 21 );
		$titleBar = $this->getTitleMock( 'Bar', 22 );
		$titleCuzz = $this->getTitleMock( 'Cuzz', 23 );

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$pages = [];
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->will( $this->returnCallback( function( array $jobs ) use ( &$pages ) {
				/** @var Job $job */
				foreach ( $jobs as $job ) {
					$params = $job->getParams();
					$this->assertArrayHasKey( 'pages', $params, '$params["pages"]' );
					$pages += $params['pages']; // addition uses keys, array_merge does not
				}
			} ) );

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			$this->getRCFactoryMock(),
			$this->getLBFactoryMock(),
			$this->getRCDupeDetectorMock()
		);

		$updater->scheduleRefreshLinks( [
			$titleFoo, $titleBar, $titleCuzz,
		] );

		$this->assertEquals( [ 21, 22, 23 ], array_keys( $pages ) );
		$this->assertEquals( [ 0, 'Foo' ], $pages[21], '$pages[21]' );
		$this->assertEquals( [ 0, 'Bar' ], $pages[22], '$pages[22]' );
		$this->assertEquals( [ 0, 'Cuzz' ], $pages[23], '$pages[23]' );
	}

	public function testInjectRCRecords() {
		$titleFoo = $this->getTitleMock( 'Foo', 21 );
		$titleBar = $this->getTitleMock( 'Bar', 22 );
		$titleCuzz = $this->getTitleMock( 'Cuzz', 23 );

		$change = $this->getEntityChangeMock();

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$pages = [];
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->will( $this->returnCallback( function( IJobSpecification $job ) use ( &$pages, $change ) {
				$params = $job->getParams();

				$this->assertArrayHasKey( 'change', $params, '$params["change"]' );
				$this->assertArrayHasKey( 'pages', $params, '$params["pages"]' );

				$this->assertSame( $change->getId(), $params["change"] );

				$pages += $params['pages']; // addition uses keys, array_merge does not
			} ) );

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			$this->getRCFactoryMock(),
			$this->getLBFactoryMock(),
			$this->getRCDupeDetectorMock()
		);

		$updater->injectRCRecords( [
			$titleFoo, $titleBar, $titleCuzz,
		], $change );

		$this->assertEquals( [ 21, 22, 23 ], array_keys( $pages ) );
		$this->assertEquals( [ 0, 'Foo' ], $pages[21], '$pages[21]' );
		$this->assertEquals( [ 0, 'Bar' ], $pages[22], '$pages[22]' );
		$this->assertEquals( [ 0, 'Cuzz' ], $pages[23], '$pages[23]' );
	}

}
