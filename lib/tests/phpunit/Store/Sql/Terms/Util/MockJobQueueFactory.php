<?php


namespace Wikibase\Lib\Tests\Store\Sql\Terms\Util;

use IJobSpecification;
use JobQueueGroup;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Store\Sql\Terms\CleanTermsIfUnusedJob;

/**
 * @license GPL-2.0-or-later
 */
class MockJobQueueFactory {

	private $test;

	public function __construct( TestCase $test ) {

		$this->test = $test;
	}

	public function getJobQueueGroupMockExpectingTermInLangsIds( $expectedTermInLangIdsToClean = null ) {
		$jobQueueGroupMock = $this->getMockJobQueue();

		if ( $expectedTermInLangIdsToClean != null ) {
			foreach ( $expectedTermInLangIdsToClean as $index => $termInLangId ) {
				$jobQueueGroupMock->expects( $this->test->at( $index ) )->method( 'push' )->will( $this->test->returnCallback( function (
					IJobSpecification $jobSpec
				) use ( $termInLangId ) {
					$this->test->assertInstanceOf( CleanTermsIfUnusedJob::class, $jobSpec );
					$this->test->assertEquals(
						[ $termInLangId ],
						$jobSpec->getParams()[ CleanTermsIfUnusedJob::TERM_IN_LANG_IDS ]
					);
				} ) );
			}
		}

		return $jobQueueGroupMock;
	}

	public function getJobQueueMockExpectingNoCalls() {
		$jobQueueGroupMock = $this->getMockJobQueue();

		$jobQueueGroupMock->expects( $this->test->never() )->method( 'push' );
		return $jobQueueGroupMock;
	}

	public function getMockJobQueue(): JobQueueGroup {
		return $this->test->getMockBuilder( JobQueueGroup::class )
				->disableOriginalConstructor()
				->disableOriginalClone()
				->disableArgumentCloning()
				->disallowMockingUnknownTypes()
				->getMock();
	}

}
