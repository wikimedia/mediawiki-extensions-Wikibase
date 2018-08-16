<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use PHPUnit\Framework\TestCase;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LatestRevisionIdResultTest extends TestCase {

	use \PHPUnit4And6Compat;

	public function testMap_NoHandlersProvided_ThrowsLogicException() {
		$someResult = LatestRevisionIdResult::nonexistentEntity();

		$this->setExpectedException( \LogicException::class );
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
		$someItemId = $this->someEntityId();

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
		$concreteRevisionResult = LatestRevisionIdResult::concreteRevision( 1 );
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

	public function testMap_ConcreteRevision_PassesRevisionIdToTheHandler() {
		$concreteRevisionResult = LatestRevisionIdResult::concreteRevision( 1 );
		$shouldNotBeCalled = function () {
			$this->fail( "Should not be called" );
		};

		$concreteRevisionResult->onRedirect( $shouldNotBeCalled )
			->onNonexistentEntity( $shouldNotBeCalled )
			->onConcreteRevision( function ( $revisionId ) {
				$this->assertSame( 1, $revisionId );
			} )
			->map();
	}

	public function testMap_Redirect_PassesRevisionIdAndEntityIdToTheHandler() {
		$givenEntityId = $this->someEntityId();
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

	public function testConcreteRevision_NotAnIntegerRevisionId_ThrowsAnException() {
		$this->setExpectedException( \Exception::class );
		LatestRevisionIdResult::concreteRevision( '1' );
	}

	public function testConcreteRevision_ZeroRevisionId_ThrowsAnException() {
		$this->setExpectedException( \Exception::class );
		LatestRevisionIdResult::concreteRevision( 0 );
	}

	public function testRedirect_NotAnIntegerRevisionId_ThrowsAnException() {
		$this->setExpectedException( \Exception::class );
		LatestRevisionIdResult::redirect( '1', $this->someEntityId() );
	}

	public function testRedirect_ZeroRevisionId_ThrowsAnException() {
		$this->setExpectedException( \Exception::class );
		LatestRevisionIdResult::redirect( 0, $this->someEntityId() );
	}

	/**
	 * @return EntityId
	 */
	protected function someEntityId() {
		return $this->createMock( EntityId::class );
	}

}
