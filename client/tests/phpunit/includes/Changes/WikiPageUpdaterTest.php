<?php

namespace Wikibase\Client\Tests\Changes;

use Job;
use JobQueueGroup;
use RecentChange;
use RefreshLinksJob;
use Title;
use Wikibase\Client\Changes\WikiPageUpdater;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector;
use Wikibase\EntityChange;

/**
 * @covers Wikibase\Client\Changes\WikiPageUpdater
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 * @group ChangeHandlerTest
 *
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class WikiPageUpdaterTest extends \MediaWikiTestCase {

	/**
	 * @return JobQueueGroup
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
			->will( $this->returnValue( array() ) );

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
	 *
	 * @return Title
	 */
	private function getTitleMock( $text ) {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 23 ) );

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedDBkey' )
			->will( $this->returnValue( $text ) );

		return $title;
	}

	/**
	 * @return EntityChange
	 */
	private function getEntityChangeMock() {
		$change = $this->getMockBuilder( EntityChange::class )
			->disableOriginalConstructor()
			->getMock();

		return $change;
	}

	/**
	 * @return RecentChange
	 */
	private function getRecentChangeMock() {
		$change = $this->getMockBuilder( RecentChange::class )
			->disableOriginalConstructor()
			->getMock();

		return $change;
	}

	public function testPurgeParserCache() {
		$updater = new WikiPageUpdater(
			$this->getJobQueueGroupMock(),
			$this->getRCFactoryMock(),
			$this->getRCDupeDetectorMock()
		);

		$title = $this->getTitleMock( 'Foo' );

		$title->expects( $this->once() )
			->method( 'invalidateCache' );

		$updater->purgeParserCache( array(
			$title
		) );
	}

	public function testPurgeWebCache() {
		$updater = new WikiPageUpdater(
			$this->getJobQueueGroupMock(),
			$this->getRCFactoryMock(),
			$this->getRCDupeDetectorMock()
		);

		$title = $this->getTitleMock( 'Foo' );
		$title->expects( $this->once() )
			->method( 'purgeSquid' );

		$updater->purgeWebCache( array(
			$title
		) );
	}

	public function testScheduleRefreshLinks() {
		$title = $this->getTitleMock( 'Foo' );

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$jobMatcher = function( RefreshLinksJob $job ) {
			$this->assertSame( 'Foo', $job->getTitle()->getPrefixedDBkey() );

			$expectedSignature = Job::newRootJobParams( 'Foo' );
			$actualSignature = $job->getRootJobParams();
			$this->assertSame(
				$expectedSignature['rootJobSignature'],
				$actualSignature['rootJobSignature']
			);

			return true;
		};

		$jobQueueGroup->expects( $this->any() )
			->method( 'push' )
			->with( $this->callback( $jobMatcher ) );

		$jobQueueGroup->expects( $this->any() )
			->method( 'deduplicateRootJob' )
			->with( $this->callback( $jobMatcher ) );

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			$this->getRCFactoryMock(),
			$this->getRCDupeDetectorMock()
		);

		$updater->scheduleRefreshLinks( array(
			$title
		) );
	}

	public function testInjectRCRecords() {
		$title = $this->getTitleMock( 'Foo' );
		$change = $this->getEntityChangeMock();
		$rc = $this->getRecentChangeMock();

		$rcFactory = $this->getRCFactoryMock();

		$rcFactory->expects( $this->once() )
			->method( 'newRecentChange' )
			->with( $change, $title, array() )
			->will( $this->returnValue( $rc ) );

		$rcDupeDetector = $this->getRCDupeDetectorMock();

		$rcDupeDetector->expects( $this->once() )
			->method( 'changeExists' )
			->with( $rc );

		$updater = new WikiPageUpdater(
			$this->getJobQueueGroupMock(),
			$rcFactory,
			$rcDupeDetector
		);

		$updater->injectRCRecords( array(
			$title
		), $change );
	}

}
