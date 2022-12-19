<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use LogicException;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\EntityChangeOpProvider;

/**
 * @covers \Wikibase\Repo\ChangeOp\EntityChangeOpProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityChangeOpProviderTest extends \PHPUnit\Framework\TestCase {

	public function provideInvalidDeserializerInstantiators() {
		return [
			'not a callback as a value' => [ [ 'entity-type' => 'foo' ] ],
			'not a string as a key' => [ [
				100 => function() {
				},
			] ],
		];
	}

	/**
	 * @dataProvider provideInvalidDeserializerInstantiators
	 */
	public function testGivenInvalidCallbackList_constructorThrowsException( array $deserializerInstantiators ) {
		$this->expectException( InvalidArgumentException::class );

		new EntityChangeOpProvider( $deserializerInstantiators );
	}

	private function getChangeOpDeserializer() {
		$deserializer = $this->createMock( ChangeOpDeserializer::class );
		$deserializer->method( $this->anything() )
			->willReturn( $this->createMock( ChangeOp::class ) );

		return $deserializer;
	}

	public function testGivenNoDeserializerCallbackForEntityType_exceptionIsThrown() {
		$deserializer = $this->getChangeOpDeserializer();

		$changeOpProvider = new EntityChangeOpProvider( [
			'entity-type' => function() use ( $deserializer ) {
				return $deserializer;
			},
		] );

		$this->expectException( ChangeOpDeserializationException::class );

		$changeOpProvider->newEntityChangeOp( 'other-entity-type', [ 'some change request data' ] );
	}

	public function testGivenCallbackNotReturningChangeOpDeserializer_exceptionIsThrown() {
		$changeOpProvider = new EntityChangeOpProvider( [
			'entity-type' => function() {
				return (object)[];
			},
		] );

		$this->expectException( LogicException::class );

		$changeOpProvider->newEntityChangeOp( 'entity-type', [ 'some change request data' ] );
	}

	public function testGivenDeserializerExistsForEntityType_newEntityChangeOpReturnsChangeOp() {
		$deserializer = $this->getChangeOpDeserializer();

		$changeOpProvider = new EntityChangeOpProvider( [
			'entity-type' => function() use ( $deserializer ) {
				return $deserializer;
			},
		] );

		$this->assertInstanceOf(
			ChangeOp::class,
			$changeOpProvider->newEntityChangeOp( 'entity-type', [ 'some change request data' ] )
		);
	}

}
