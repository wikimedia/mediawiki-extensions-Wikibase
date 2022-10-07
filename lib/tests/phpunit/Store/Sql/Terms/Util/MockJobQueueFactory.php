<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Store\Sql\Terms\Util;

use IJobSpecification;
use JobQueueGroup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Store\Sql\Terms\CleanTermsIfUnusedJob;

/**
 * @license GPL-2.0-or-later
 */
class MockJobQueueFactory {

	/** @var TestCase */
	private $test;

	public function __construct( TestCase $test ) {
		$this->test = $test;
	}

	public function getJobQueueGroupMockExpectingTermInLangsIds(
		array $expectedTermInLangIdsToClean = null
	): JobQueueGroup {
		$jobQueueGroupMock = $this->getMockJobQueue();

		if ( $expectedTermInLangIdsToClean != null ) {
			$returnCallbacks = [];
			foreach ( $expectedTermInLangIdsToClean as $index => $termInLangId ) {
				$returnCallbacks[] = new ReturnCallback( function ( IJobSpecification $jobSpec ) use ( $termInLangId ) {
					$this->test->assertInstanceOf( CleanTermsIfUnusedJob::class, $jobSpec );
					$this->test->assertEquals(
						[ $termInLangId ],
						$jobSpec->getParams()[ CleanTermsIfUnusedJob::TERM_IN_LANG_IDS ]
					);
				} );
			}
			$jobQueueGroupMock
				->method( 'push' )
				->willReturnOnConsecutiveCalls( ...$returnCallbacks );
		}

		return $jobQueueGroupMock;
	}

	public function getJobQueueMockExpectingNoCalls(): JobQueueGroup {
		$jobQueueGroupMock = $this->getMockJobQueue();

		$jobQueueGroupMock->expects( $this->test->never() )->method( 'push' );
		return $jobQueueGroupMock;
	}

	private function getMockJobQueue(): MockObject {
		return $this->test->getMockBuilder( JobQueueGroup::class )
				->disableOriginalConstructor()
				->disableOriginalClone()
				->disableArgumentCloning()
				->disallowMockingUnknownTypes()
				->getMock();
	}

}
