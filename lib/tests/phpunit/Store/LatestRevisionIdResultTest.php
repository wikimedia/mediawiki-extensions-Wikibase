<?php declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LatestRevisionIdResult;

/**
 * @covers \Wikibase\Lib\Store\LatestRevisionIdResult
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LatestRevisionIdResultTest extends TestCase {

	public function testMap_NoHandlersProvided_ThrowsLogicException() {
		$someResult = LatestRevisionIdResult::nonexistentEntity();

		$this->expectException( \LogicException::class );
		$someResult->map();
	}

	public function testOnNonexistentEntity_SomeHandlerGiven_ReturnsNewInstanceOfResult() {
		$noop = function () {
		};
		$initialResult = LatestRevisionIdResult::nonexistentEntity();

		$resultWithHandler = $initialResult->onNonexistentEntity( $noop );

		$this->assertNotSame( $initialResult, $resultWithHandler );
	}

	public function testOnConcreteRevision_SomeHandlerGiven_ReturnsNewInstanceOfResult() {
		$noop = function () {
		};
		$initialResult = LatestRevisionIdResult::nonexistentEntity();

		$resultWithHandler = $initialResult->onConcreteRevision( $noop );

		$this->assertNotSame( $initialResult, $resultWithHandler );
	}

	public function testRedirect_SomeHandlerGiven_ReturnsNewInstanceOfResult() {
		$noop = function () {
		};
		$initialResult = LatestRevisionIdResult::nonexistentEntity();

		$resultWithHandler = $initialResult->onRedirect( $noop );

		$this->assertNotSame( $initialResult, $resultWithHandler );
	}

	public function testMap_EntityDoesNotExist_ReturnsValueReturnedByNonexistentHandler() {
		$nonexistentEntity = LatestRevisionIdResult::nonexistentEntity();
		$shouldNotBeCalled = function () {
			$this->fail( "Should not be called" );
		};

		$result = $nonexistentEntity->onConcreteRevision( $shouldNotBeCalled )
			->onRedirect( $shouldNotBeCalled )
			->onNonexistentEntity( function () {
				return 'nonexistent';
			} )
			->map();

		$this->assertEquals( 'nonexistent', $result );
	}

	public function testMap_EntityRevisionIsRedirect_ReturnsValueReturnedByRedirectHandler() {
		$someItemId = $this->createMock( EntityId::class );

		$redirectResult = LatestRevisionIdResult::redirect( 1, $someItemId );
		$shouldNotBeCalled = function () {
			$this->fail( "Should not be called" );
		};

		$result = $redirectResult->onConcreteRevision( $shouldNotBeCalled )
			->onNonexistentEntity( $shouldNotBeCalled )
			->onRedirect( function () {
				return 'redirect';
			} )
			->map();

		$this->assertEquals( 'redirect', $result );
	}

	public function testMap_ConcreteRevision_ReturnsValueReturnedByConcreteRevisionHandler() {
		$concreteRevisionResult = LatestRevisionIdResult::concreteRevision( 1, '20220101001122' );
		$shouldNotBeCalled = function () {
			$this->fail( "Should not be called" );
		};

		$result = $concreteRevisionResult->onRedirect( $shouldNotBeCalled )
			->onNonexistentEntity( $shouldNotBeCalled )
			->onConcreteRevision( function () {
				return 'concrete revision';
			} )
			->map();

		$this->assertEquals( 'concrete revision', $result );
	}

	public function testMap_ConcreteRevision_PassesRevisionIdAndTimestampToTheHandler() {
		$expectedRevisionTimestamp = '20220101001122';
		$expectedRevisionId = 123;
		$concreteRevisionResult = LatestRevisionIdResult::concreteRevision( $expectedRevisionId, $expectedRevisionTimestamp );
		$shouldNotBeCalled = function () {
			$this->fail( "Should not be called" );
		};

		$concreteRevisionResult->onRedirect( $shouldNotBeCalled )
			->onNonexistentEntity( $shouldNotBeCalled )
			->onConcreteRevision( function ( $revisionId, $timestamp ) use ( $expectedRevisionId, $expectedRevisionTimestamp ) {
				$this->assertSame( $expectedRevisionId, $revisionId );
				$this->assertSame( $expectedRevisionTimestamp, $timestamp );
			} )
			->map();
	}

	public function testMap_Redirect_PassesRevisionIdAndEntityIdToTheHandler() {
		$givenEntityId = $this->createMock( EntityId::class );
		$redirectResult = LatestRevisionIdResult::redirect( 1, $givenEntityId );
		$shouldNotBeCalled = function () {
			$this->fail( "Should not be called" );
		};

		$redirectResult->onConcreteRevision( $shouldNotBeCalled )
			->onNonexistentEntity( $shouldNotBeCalled )
			->onRedirect( function ( $revisionId, $gotEntityId ) use ( $givenEntityId ) {
				$this->assertSame( 1, $revisionId );
				$this->assertSame( $givenEntityId, $gotEntityId );
			} )
			->map();
	}

	public function testConcreteRevision_ZeroRevisionId_ThrowsAnException() {
		$this->expectException( \Exception::class );
		LatestRevisionIdResult::concreteRevision( 0, '20220101001122' );
	}

	public function testRedirect_ZeroRevisionId_ThrowsAnException() {
		$this->expectException( \Exception::class );
		LatestRevisionIdResult::redirect( 0, $this->createMock( EntityId::class ) );
	}

}
