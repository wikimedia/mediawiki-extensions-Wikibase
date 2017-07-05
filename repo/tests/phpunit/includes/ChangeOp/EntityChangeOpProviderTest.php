<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use LogicException;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\EntityChangeOpProvider;

/**
 * @covers Wikibase\Repo\ChangeOp\EntityChangeOpProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class EntityChangeOpProviderTest extends \PHPUnit_Framework_TestCase {

	public function provideInvalidDeserializerInstantiators() {
		return [
			'not a callback as a value' => [ [ 'entity-type' => 'foo' ] ],
			'not a string as a key' => [ [
				100 => function() {
				}
			] ],
		];
	}

	/**
	 * @dataProvider provideInvalidDeserializerInstantiators
	 */
	public function testGivenInvalidCallbackList_constructorThrowsException( array $deserializerInstantiators ) {
		$this->setExpectedException( InvalidArgumentException::class );

		new EntityChangeOpProvider( $deserializerInstantiators );
	}

	private function getChangeOpDeserializer() {
		$deserializer = $this->getMock( ChangeOpDeserializer::class );
		$deserializer->method( $this->anything() )
			->will( $this->returnValue( $this->getMock( ChangeOp::class ) ) );

		return $deserializer;
	}

	public function testGivenNoDeserializerCallbackForEntityType_exceptionIsThrown() {
		$deserializer = $this->getChangeOpDeserializer();

		$changeOpProvider = new EntityChangeOpProvider( [
			'entity-type' => function() use ( $deserializer ) {
				return $deserializer;
			}
		] );

		$this->setExpectedException( ChangeOpDeserializationException::class );

		$changeOpProvider->newEntityChangeOp( 'other-entity-type', [ 'some change request data' ] );
	}

	public function testGivenCallbackNotReturningChangeOpDeserializer_exceptionIsThrown() {
		$changeOpProvider = new EntityChangeOpProvider( [
			'entity-type' => function() {
				return new \stdClass();
			}
		] );

		$this->setExpectedException( LogicException::class );

		$changeOpProvider->newEntityChangeOp( 'entity-type', [ 'some change request data' ] );
	}

	public function testGivenDeserializerExistsForEntityType_newEntityChangeOpReturnsChangeOp() {
		$deserializer = $this->getChangeOpDeserializer();

		$changeOpProvider = new EntityChangeOpProvider( [
			'entity-type' => function() use ( $deserializer ) {
				return $deserializer;
			}
		] );

		$this->assertInstanceOf(
			ChangeOp::class,
			$changeOpProvider->newEntityChangeOp( 'entity-type', [ 'some change request data' ] )
		);
	}

}
