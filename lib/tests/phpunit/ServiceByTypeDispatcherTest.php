<?php

namespace Wikibase\Lib\Tests;

use PHPUnit\Framework\TestCase;
use stdClass;
use Wikibase\Lib\ServiceByTypeDispatcher;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikimedia\Assert\AssertionException;
use Wikimedia\Assert\PostconditionException;

/**
 * @covers \Wikibase\Lib\ServiceByTypeDispatcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ServiceByTypeDispatcherTest extends TestCase {

	public function provideTestConstruction() {
		yield [ true, stdClass::class, [], new stdClass() ];
		yield [ true, stdClass::class, [ 'type' => $this->getSomeServiceReturningCallback() ], new stdClass() ];
		yield [ false, stdClass::class, [ $this->getSomeServiceReturningCallback() ], new stdClass() ];
		yield [ false, stdClass::class, [ null ], new stdClass() ];
		yield 'default not matching type' => [ false, EntityUrlLookup::class, [], new stdClass() ];
	}

	private function getSomeServiceReturningCallback( $fakeServiceToReturn = null ) {
		return function () use ( $fakeServiceToReturn ) {
			return $fakeServiceToReturn ?? new stdClass();
		};
	}

	/**
	 * @dataProvider provideTestConstruction
	 */
	public function testConstruction( $expectedSuccess, string $type, $callbacks, $defaultService ) {
		if ( !$expectedSuccess ) {
			$this->expectException( AssertionException::class );
		} else {
			$this->expectNotToPerformAssertions();
		}
		new ServiceByTypeDispatcher( $type, $callbacks, $defaultService );
	}

	public function testServiceDefault() {
		$defaultService = new stdClass();
		$dispatcher = new ServiceByTypeDispatcher( stdClass::class, [], $defaultService );
		$this->assertSame( $defaultService, $dispatcher->getServiceForType( 'foo' ) );
	}

	public function testServiceDispatches() {
		$defaultService = new stdClass();
		$typeService = new stdClass();

		$dispatcher = new ServiceByTypeDispatcher(
			stdClass::class,
			[ 'foo' => $this->getSomeServiceReturningCallback( $typeService ) ],
			$defaultService
		);

		$dispatchedService = $dispatcher->getServiceForType( 'foo' );
		$this->assertSame( $typeService, $dispatchedService );
		$this->assertNotSame( $defaultService, $dispatchedService );
	}

	public function testServiceDispatchesAndReturnsSameInstance() {
		$dispatcher = new ServiceByTypeDispatcher( stdClass::class, [ 'foo' => $this->getSomeServiceReturningCallback() ], new stdClass() );

		$dispatchedServiceOne = $dispatcher->getServiceForType( 'foo' );
		$dispatchedServiceTwo = $dispatcher->getServiceForType( 'foo' );
		$this->assertSame( $dispatchedServiceOne, $dispatchedServiceTwo );
	}

	public function testValidatesTypeAfterDispatch() {
		$dispatcher = new ServiceByTypeDispatcher(
			EntityUrlLookup::class,
			[ 'foo' => $this->getSomeServiceReturningCallback() ],
			$this->createMock( EntityUrlLookup::class )
		);

		$this->expectException( PostconditionException::class );
		$dispatcher->getServiceForType( 'foo' );
	}

	public function testPassesArgumentsToCallback() {
		$callbackArgs = [ 'some', 'additional arguments' ];
		$callback = function ( ...$args ) use ( $callbackArgs ) {
			$this->assertSame( $callbackArgs[0], $args[0] );
			$this->assertSame( $callbackArgs[1], $args[1] );
			return new stdClass();
		};
		$dispatcher = new ServiceByTypeDispatcher(
			stdClass::class,
			[ 'foo' => $callback ],
			new stdClass()
		);

		$dispatcher->getServiceForType( 'foo', $callbackArgs );
	}

}
