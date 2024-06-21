<?php

namespace Wikibase\Client\Tests\Integration\Changes;

use HTMLCacheUpdateJob;
use IBufferingStatsdDataFactory;
use Job;
use JobQueueGroup;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use RefreshLinksJob;
use UDPTransport;
use Wikibase\Client\Changes\WikiPageUpdater;
use Wikibase\Lib\Changes\EntityChange;
use Wikimedia\Stats\OutputFormats;
use Wikimedia\Stats\StatsCache;
use Wikimedia\Stats\StatsFactory;

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
	 * Create a mock {@link StatsFactory} expecting certain stats.
	 *
	 * @param string[] $expectedStats The expected stats, in DogStatsD format.
	 * Each entry is a string like "metric_name:123|c", or (with labels)
	 * like "metric_name:123|c|#label1:value1,label2:value2".
	 * @param int[] $expectedStatsdCopies The expected stats, as copied to statsd.
	 * Each entry is a mapping from string $updateType to the counter $delta.
	 * This can be removed when the legacy statsd copying is removed.
	 * @return StatsFactory You must call {@link StatsFactory::flush()} on this
	 * before the test ends.
	 */
	private function getStatsFactoryMock(
		array $expectedStats,
		array $expectedStatsdCopies
	): StatsFactory {
		// based on HashBagOStuffTest::testUpdateOpStats(), pending improvement (T368740)
		$statsCache = new StatsCache();
		$emitter = OutputFormats::getNewEmitter(
			'mediawiki',
			$statsCache,
			OutputFormats::getNewFormatter( OutputFormats::DOGSTATSD )
		);

		$expectedStatsString = implode( "\n", $expectedStats );
		$expectedStatsCount = count( $expectedStats );
		$expectedStatsString .= "\nmediawiki.stats_buffered_total:$expectedStatsCount|c\n";
		$transport = $this->createMock( UDPTransport::class );
		$transport->expects( $this->once() )
			->method( 'emit' )
			->with( $expectedStatsString );
		$emitter = $emitter->withTransport( $transport );

		$statsdDataFactory = $this->createMock( IBufferingStatsdDataFactory::class );
		$expectedStatsdArgs = [];
		foreach ( $expectedStatsdCopies as $updateType => $delta ) {
			// cast $delta to float, enforced by CounterMetric::incrementBy()
			$expectedStatsdArgs[] = [ 'wikibase.client.pageupdates.' . $updateType, (float)$delta ];
		}
		$statsdDataFactory->method( 'updateCount' )
			->willReturnCallback( function ( $key, $delta ) use ( &$expectedStatsdArgs ) {
				if ( !$expectedStatsdArgs ) {
					return;
				}
				$this->assertSame( array_shift( $expectedStatsdArgs ), [ $key, $delta ] );
			} );

		$stats = new StatsFactory( $statsCache, $emitter, new NullLogger() );
		$stats->withStatsdDataFactory( $statsdDataFactory );
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

		$statsFactory = $this->getStatsFactoryMock(
			[
				'mediawiki.WikibaseClient.PageUpdates_WebCache_jobs_total:2|c',
				'mediawiki.WikibaseClient.PageUpdates_WebCache_titles_total:3|c',
			],
			[
				'WebCache.jobs' => 2, // 2 batches (batch size 2, 3 titles)
				'WebCache.titles' => 3,
			]
		);
		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			new NullLogger(),
			$statsFactory
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
		$statsFactory->flush();

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

		$statsFactory = $this->getStatsFactoryMock(
			[
				'mediawiki.WikibaseClient.PageUpdates_RefreshLinks_jobs_total:3|c',
				'mediawiki.WikibaseClient.PageUpdates_RefreshLinks_titles_total:3|c',
			],
			[
				'RefreshLinks.jobs' => 3, // no batching
				'RefreshLinks.titles' => 3,
			]
		);
		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			new NullLogger(),
			$statsFactory
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
		$statsFactory->flush();

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

		$statsFactory = $this->getStatsFactoryMock(
			[
				'mediawiki.WikibaseClient.PageUpdates_InjectRCRecords_jobs_total:1|c',
				'mediawiki.WikibaseClient.PageUpdates_InjectRCRecords_titles_total:2|c',
				'mediawiki.WikibaseClient.PageUpdates_InjectRCRecords_discardedTitles_total:1|c',
				'mediawiki.WikibaseClient.PageUpdates_InjectRCRecords_incompleteChanges_total:1|c',
			],
			[
				// FIXME: Because of the hot fix for T177707 we expect only the first batch.
				'InjectRCRecords.jobs' => 1,
				'InjectRCRecords.titles' => 2,
				'InjectRCRecords.discardedTitles' => 1,
				'InjectRCRecords.incompleteChanges' => 1,
			]
		);
		$updater = new WikiPageUpdater(
			$jobQueueGroup,
			new NullLogger(),
			$statsFactory
		);
		$updater->setRecentChangesBatchSize( 2 );

		$updater->injectRCRecords(
			[ $titleFoo, $titleBar, $titleCuzz ],
			new EntityChange(),
			[ 'rootJobTimestamp' => '20202211060708', 'rootJobSignature' => 'Kittens!' ]
		);
		$statsFactory->flush();

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
