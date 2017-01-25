<?php

namespace Wikibase\Repo\Tests\ChangeOpDeserialization;

use PHPUnit_Framework_TestCase;
use RuntimeException;
use Wikibase\Repo\Api\ApiErrorReporter;

class ChangeOpDeserializationTestHelper {

	/**
	 * @var PHPUnit_Framework_TestCase
	 */
	private $mockBuilderFactory;

	public function __construct( PHPUnit_Framework_TestCase $mockBuilderFactory ) {
		$this->mockBuilderFactory = $mockBuilderFactory;
	}

	/**
	 * Returns a mock ApiErrorReporter that throws exceptions on dieError
	 * in order to make the error code testable.
	 *
	 * @param bool $expectsError
	 *
	 * @return ApiErrorReporter
	 *
	 * @throws RuntimeException
	 */
	public function getApiErrorReporter( $expectsError = false ) {
		$errorReporter = $this->mockBuilderFactory->getMockBuilder( ApiErrorReporter::class )
			->disableOriginalConstructor()
			->getMock();

		if ( !$expectsError ) {
			$errorReporter->expects( $this->mockBuilderFactory->never() )
				->method( 'dieError' );
		} else {
			$errorReporter->expects( $this->mockBuilderFactory->once() )
				->method( 'dieError' )
				->willReturnCallback( function( $description, $errorCode ) {
					throw new RuntimeException( $errorCode );
				} );
		}

		return $errorReporter;
	}

}
