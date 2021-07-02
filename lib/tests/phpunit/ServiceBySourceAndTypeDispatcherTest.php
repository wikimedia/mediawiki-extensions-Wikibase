<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikimedia\Assert\AssertionException;
use Wikimedia\Assert\PostconditionException;

/**
 * @covers \Wikibase\Lib\ServiceBySourceAndTypeDispatcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ServiceBySourceAndTypeDispatcherTest extends TestCase {

	public function provideTestConstruction() {
		yield [ LogicException::class,
			stdClass::class,
			[] ];
		yield [ false,
			stdClass::class,
			[ 'fooSource' => [ 'barType' => $this->getSomeServiceReturningCallback() ] ] ];
		yield [ AssertionException::class,
			stdClass::class,
			[ $this->getSomeServiceReturningCallback() ] ];
		yield [ AssertionException::class,
			stdClass::class,
			[ 'fooSource' => $this->getSomeServiceReturningCallback() ] ];
		yield [ AssertionException::class,
			stdClass::class,
			[ null ] ];
	}

	private function getSomeServiceReturningCallback( $fakeServiceToReturn = null ) {
		return function () use ( $fakeServiceToReturn ) {
			return $fakeServiceToReturn ?? new stdClass();
		};
	}

	/**
	 * @dataProvider provideTestConstruction
	 */
	public function testConstruction( $expectedException, string $type, $callbacks ) {
		if ( $expectedException ) {
			$this->expectException( $expectedException );
		} else {
			$this->expectNotToPerformAssertions();
		}
		new ServiceBySourceAndTypeDispatcher( $type, $callbacks );
	}

	public function testServiceDispatches() {
		$typeService = new stdClass();

		$dispatcher = new ServiceBySourceAndTypeDispatcher(
			stdClass::class, [ 'sooSource' => [ 'fooType' => $this->getSomeServiceReturningCallback( $typeService ) ] ]
		);

		$dispatchedService = $dispatcher->getServiceForSourceAndType( 'sooSource', 'fooType' );
		$this->assertSame( $typeService, $dispatchedService );
	}

	public function testServiceCachesInstances() {
		$dispatcher = new ServiceBySourceAndTypeDispatcher(
			stdClass::class,
			[ 'sooSource' => [ 'fooType' => $this->getSomeServiceReturningCallback() ] ]
		);

		$dispatchedServiceOne = $dispatcher->getServiceForSourceAndType( 'sooSource', 'fooType' );
		$dispatchedServiceTwo = $dispatcher->getServiceForSourceAndType( 'sooSource', 'fooType' );
		$this->assertSame( $dispatchedServiceOne, $dispatchedServiceTwo );
	}

	public function testValidatesTypeAfterDispatch() {
		$dispatcher = new ServiceBySourceAndTypeDispatcher(
			EntityUrlLookup::class,
			[ 'catSource' => [ 'fooType' => $this->getSomeServiceReturningCallback() ] ]
		);

		$this->expectException( PostconditionException::class );
		$dispatcher->getServiceForSourceAndType( 'catSource', 'fooType' );
	}

	public function testPassesArgumentsToCallback() {
		$callbackArgs = [ 'some',
			'additional arguments' ];
		$callback = function ( ...$args ) use ( $callbackArgs ) {
			$this->assertSame( $callbackArgs[0], $args[0] );
			$this->assertSame( $callbackArgs[1], $args[1] );

			return new stdClass();
		};
		$dispatcher = new ServiceBySourceAndTypeDispatcher(
			stdClass::class,
			[ 'catSource' => [ 'fooType' => $callback ] ]
		);

		$dispatcher->getServiceForSourceAndType( 'catSource', 'fooType', $callbackArgs );
	}

}
