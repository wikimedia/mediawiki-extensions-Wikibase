<?php

namespace Wikibase\Repo\Tests\Notifications;

use JobQueueGroup;
use JobSpecification;
use Psr\Log\NullLogger;
use Wikibase\Lib\Changes\Change;
use Wikibase\Repo\Notifications\JobQueueChangeNotificationSender;

/**
 * @covers \Wikibase\Repo\Notifications\JobQueueChangeNotificationSender
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class JobQueueChangeNotificationSenderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return JobQueueChangeNotificationSender
	 */
	private function getSender( $batchSize, $expectedChunks ) {
		$jobQueueGroup = $this->createMock( JobQueueGroup::class );

		$jobQueueGroup->expects( $this->exactly( $expectedChunks ? 1 : 0 ) )
			->method( 'lazyPush' )
			->with( $this->isType( 'array' ) )
			->willReturnCallback(
				function( array $jobs ) use ( $expectedChunks ) {
					$this->assertCount( $expectedChunks, $jobs );
					$this->assertContainsOnlyInstancesOf(
						JobSpecification::class,
						$jobs
					);

					foreach ( $jobs as $job ) {
						$params = $job->getParams();

						$this->assertContainsOnly( 'int', $params['changeIds'] );
					}
				}
			);

		$jobQueueGroupFactory = function( $wikiId ) use ( $jobQueueGroup ) {
			$this->assertSame( 'database-name-0', $wikiId );
			return $jobQueueGroup;
		};

		return new JobQueueChangeNotificationSender(
			new NullLogger(),
			[ 'site-id-0' => 'database-name-0' ],
			$batchSize,
			$jobQueueGroupFactory
		);
	}

	public function sendNotificationProvider() {
		$change = $this->createMock( Change::class );
		$change->method( 'getId' )
			->willReturn( 4 );

		return [
			'no changes' => [
				100,
				[]
			],
			'one batch' => [
				100,
				[ $change, $change, $change ]
			],
			'three batches' => [
				2,
				[ $change, $change, $change, $change, $change ]
			]
		];
	}

	/**
	 * @dataProvider sendNotificationProvider
	 */
	public function testSendNotification( $batchSize, $changes ) {
		$expectedChunks = intval( ceil( count( $changes ) / $batchSize ) );

		$sender = $this->getSender( $batchSize, $expectedChunks );
		$sender->sendNotification( 'site-id-0', $changes );
	}

}
