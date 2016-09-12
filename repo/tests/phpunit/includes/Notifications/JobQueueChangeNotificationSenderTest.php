<?php

namespace Wikibase\Test;

use JobQueueGroup;
use PHPUnit_Framework_TestCase;
use Wikibase\Change;
use Wikibase\ChangeNotificationJob;
use Wikibase\Repo\Notifications\JobQueueChangeNotificationSender;

/**
 * @covers Wikibase\Repo\Notifications\JobQueueChangeNotificationSender
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseChange
 * @group WikibaseRepo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class JobQueueChangeNotificationSenderTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return JobQueueChangeNotificationSender
	 */
	private function getSender( $batchSize, $expectedChunks ) {
		$jobQueueGroup = $this->getMockBuilder( JobQueueGroup::class )
			->disableOriginalConstructor()
			->getMock();

		$jobQueueGroup->expects( $this->exactly( $expectedChunks ? 1 : 0 ) )
			->method( 'push' )
			->with( $this->isType( 'array' ) )
			->will( $this->returnCallback(
				function( array $jobs ) use ( $expectedChunks ) {
					$this->assertCount( $expectedChunks, $jobs );
					$this->assertContainsOnlyInstancesOf(
						ChangeNotificationJob::class,
						$jobs
					);
				} )
			);

		$jobQueueGroupFactory = function( $wikiId ) use ( $jobQueueGroup ) {
			$this->assertSame( 'database-name-0', $wikiId );
			return $jobQueueGroup;
		};

		return new JobQueueChangeNotificationSender(
			'repo-db',
			[ 'site-id-0' => 'database-name-0' ],
			$batchSize,
			$jobQueueGroupFactory
		);
	}

	public function sendNotificationProvider() {
		$change = $this->getMock( Change::class );

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
				3,
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
