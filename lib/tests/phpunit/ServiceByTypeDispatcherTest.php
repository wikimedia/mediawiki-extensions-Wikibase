<?php

namespace Wikibase\Lib\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Lib\ServiceByTypeDispatcher;
use Wikimedia\Assert\AssertionException;

/**
 * @covers \Wikibase\Lib\ServiceByTypeDispatcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ServiceByTypeDispatcherTest extends TestCase {

	public function provideTestConstruction() {
		yield[ true, [], (object)[] ];
		yield[ true, [ 'type' => $this->getSomeServiceReturningCallback() ], (object)[] ];
		yield[ false, [ $this->getSomeServiceReturningCallback() ], (object)[] ];
		yield[ false, [ null ], (object)[] ];
	}

	private function getSomeServiceReturningCallback( $fakeServiceToReturn = null ) {
		return function() use ( $fakeServiceToReturn ){
			return $fakeServiceToReturn ? $fakeServiceToReturn : (object)[];
		};
	}

	/**
	 * @dataProvider provideTestConstruction
	 */
	public function testConstruction( $expectedSuccess, $callbacks, $defaultService ) {
		if ( !$expectedSuccess ) {
			$this->expectException( AssertionException::class );
		} else {
			$this->expectNotToPerformAssertions();
		}
		new ServiceByTypeDispatcher( $callbacks, $defaultService );
	}

	public function testServiceDefault() {
		$defaultService = (object)[];
		$dispatcher = new ServiceByTypeDispatcher( [], $defaultService );
		$this->assertSame( $defaultService, $dispatcher->getServiceForType( 'foo' ) );
	}

	public function testServiceDispatches() {
		$defaultService = (object)[];
		$typeService = (object)[];

		$dispatcher = new ServiceByTypeDispatcher( [ 'foo' => $this->getSomeServiceReturningCallback( $typeService ) ], $defaultService );

		$dispatchedService = $dispatcher->getServiceForType( 'foo' );
		$this->assertSame( $typeService, $dispatchedService );
		$this->assertNotSame( $defaultService, $dispatchedService );
	}

	public function testServiceDispatchesAndReturnsSameInstance() {
		$dispatcher = new ServiceByTypeDispatcher( [ 'foo' => $this->getSomeServiceReturningCallback() ], (object)[] );

		$dispatchedServiceOne = $dispatcher->getServiceForType( 'foo' );
		$dispatchedServiceTwo = $dispatcher->getServiceForType( 'foo' );
		$this->assertSame( $dispatchedServiceOne, $dispatchedServiceTwo );
	}

}
