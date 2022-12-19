<?php

namespace Wikibase\Client\Tests\Integration\Changes;

use HTMLCacheUpdateJob;
use Job;
use JobQueueGroup;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use RefreshLinksJob;
use Title;
use Wikibase\Client\Changes\WikiPageUpdater;
use Wikibase\Lib\Changes\EntityChange;

/**
 * @covers \Wikibase\Client\Changes\WikiPageUpdater
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikiPageUpdaterTest extends MediaWikiIntegrationTestCase {

	/**
	 * @return JobQueueGroup|MockObject
	 */
	private function getJobQueueGroupMock() {
		$jobQueueGroup = $this->createMock( JobQueueGroup::class );

		return $jobQueueGroup;
	}

	/**
	 * @param string $text
	 * @param int $id
	 *
	 * @return Title
	 */
	private function getTitleMock( $text, $id ) {
		$title = $this->createMock( Title::class );

		$title->method( 'getArticleID' )
			->willReturn( $id );

		$title->method( 'canExist' )
			->willReturn( true );

		$title->method( 'exists' )
			->willReturn( true );

		$title->method( 'getPrefixedDBkey' )
			->willReturn( $text );

		$title->method( 'getDBkey' )
			->willReturn( $text );

		$title->method( 'getText' )
			->willReturn( $text );

		$title->method( 'getNamespace' )
			->willReturn( 0 );

		$title->method( 'getNsText' )
			->willReturn( '' );

		return $title;
	}

	/**
	 * @return StatsdDataFactoryInterface
	 */
	private function getStatsdDataFactoryMock( array $expectedStats ) {
		$stats = $this->createMock( StatsdDataFactoryInterface::class );

		$expectedArgs = [];
		foreach ( $expectedStats as $updateType => $delta ) {
			$expectedArgs[] = [ 'wikibase.client.pageupdates.' . $updateType, $delta ];
		}
		$stats
			->method( 'updateCount' )
			->withConsecutive( ...$expectedArgs );

		return $stats;
	}

	public function testPurgeWebCache() {
		$titleFoo = $this->getTitleMock( 'Foo', 21 );
		$titleBar = $this->getTitleMock( 'Bar', 22 );
		$titleCuzz = $this->getTitleMock( 'Cuzz', 23 );

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$pages = [];
		$rootJobParams = [];
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->willReturnCallback( function( array $jobs ) use ( &$pages, &$rootJobParams ) {
				/** @var Job $job */
				foreach ( $jobs as $job ) {
					$this->assertInstanceOf( HTMLCacheUpdateJob::class, $job );
					$params = $job->getParams();
					$this->assertArrayHasKey( 'pages', $params, '$params["pages"]' );
					$pages += $params['pages']; // addition uses keys, array_merge does not
					$rootJobParams = $job->getRootJobParams();
				}
			} );

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			new NullLogger(),
			$this->getStatsdDataFactoryMock( [
				'WebCache.jobs' => 2, // 2 batches (batch size 2, 3 titles)
				'WebCache.titles' => 3,
			] )
		);
		$updater->setPurgeCacheBatchSize( 2 );

		$updater->purgeWebCache( [
			$titleFoo, $titleBar, $titleCuzz,
		], [
			'rootJobTimestamp' => '20202211060708',
			'rootJobSignature' => 'Kittens!',
		],
			'test~action',
			'uid:1'
		);

		$this->assertEquals( [ 21, 22, 23 ], array_keys( $pages ) );
		$this->assertEquals( [ 0, 'Foo' ], $pages[21], '$pages[21]' );
		$this->assertEquals( [ 0, 'Bar' ], $pages[22], '$pages[22]' );
		$this->assertEquals( [ 0, 'Cuzz' ], $pages[23], '$pages[23]' );

		$this->assertEquals(
			[
				'rootJobTimestamp' => '20202211060708',
				'rootJobSignature' => 'Kittens!',
			],
			$rootJobParams,
			'$rootJobParams'
		);
	}

	public function testScheduleRefreshLinks() {
		$titleFoo = $this->getTitleMock( 'Foo', 21 );
		$titleBar = $this->getTitleMock( 'Bar', 22 );
		$titleCuzz = $this->getTitleMock( 'Cuzz', 23 );

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$pages = [];
		$rootJobParams = [];
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->with( $this->isInstanceOf( RefreshLinksJob::class ) )
			->willReturnCallback( function( Job $job ) use ( &$pages, &$rootJobParams ) {
				$pages[] = $job->getTitle()->getPrefixedDBkey();
				$rootJobParams = $job->getRootJobParams();
			} );

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			new NullLogger(),
			$this->getStatsdDataFactoryMock( [
				'RefreshLinks.jobs' => 3, // no batching
				'RefreshLinks.titles' => 3,
			] )
		);

		$updater->scheduleRefreshLinks(
			[ $titleFoo, $titleBar, $titleCuzz ],
			[
				'rootJobTimestamp' => '20202211060708',
				'rootJobSignature' => 'Kittens!',
			],
			'test~action',
			'uid:1'
		);

		$this->assertSame(
			[ 'Foo', 'Bar', 'Cuzz' ],
			$pages,
			'$pages'
		);

		$this->assertEquals(
			[
				'rootJobTimestamp' => '20202211060708',
				'rootJobSignature' => 'Kittens!',
			],
			$rootJobParams,
			'$rootJobParams'
		);
	}

	public function testInjectRCRecords() {
		$titleFoo = $this->getTitleMock( 'Foo', 21 );
		$titleBar = $this->getTitleMock( 'Bar', 22 );
		$titleCuzz = $this->getTitleMock( 'Cuzz', 23 );

		$jobQueueGroup = $this->getJobQueueGroupMock();

		$pages = [];
		$rootJobParams = [];
		$jobQueueGroup->expects( $this->atLeastOnce() )
			->method( 'lazyPush' )
			->willReturnCallback(
				function( array $jobs ) use ( &$pages, &$rootJobParams ) {
					/** @var Job $job */
					foreach ( $jobs as $job ) {
						$this->assertSame( 'wikibase-InjectRCRecords', $job->getType() );
						$params = $job->getParams();
						$this->assertArrayHasKey( 'pages', $params, '$params["pages"]' );
						$pages += $params['pages']; // addition uses keys, array_merge does not
						$rootJobParams = $job->getRootJobParams();
					}
				}
			);

		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			new NullLogger(),
			$this->getStatsdDataFactoryMock( [
				// FIXME: Because of the hot fix for T177707 we expect only the first batch.
				'InjectRCRecords.jobs' => 1,
				'InjectRCRecords.titles' => 2,
				'InjectRCRecords.discardedTitles' => 1,
			] )
		);
		$updater->setRecentChangesBatchSize( 2 );

		$updater->injectRCRecords(
			[ $titleFoo, $titleBar, $titleCuzz ],
			new EntityChange(),
			[ 'rootJobTimestamp' => '20202211060708', 'rootJobSignature' => 'Kittens!' ]
		);

		// FIXME: Because of the hot fix for T177707 we expect only the first batch.
		$this->assertSame( [
			21 => [ 0, 'Foo' ],
			22 => [ 0, 'Bar' ],
		], $pages );

		$this->assertEquals(
			[
				'rootJobTimestamp' => '20202211060708',
				'rootJobSignature' => 'Kittens!',
			],
			$rootJobParams,
			'$rootJobParams'
		);
	}

}
