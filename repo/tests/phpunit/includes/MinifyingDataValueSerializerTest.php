<?php

namespace Wikibase\Repo\Tests;

use DataValues\DataValue;
use PHPUnit_Framework_TestCase;
use Serializers\Exceptions\UnsupportedObjectException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\MinifyingDataValueSerializer;

/**
 * @covers Wikibase\Repo\MinifyingDataValueSerializer
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class MinifyingDataValueSerializerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidDataValueProvider
	 */
	public function testInvalidDataValues( $value ) {
		$serializer = new MinifyingDataValueSerializer();

		$this->assertFalse( $serializer->isSerializerFor( $value ) );

		$this->setExpectedException( UnsupportedObjectException::class );
		$serializer->serialize( $value );
	}

	public function invalidDataValueProvider() {
		return [
			[ null ],
			[ '' ],
			[ [] ],
			[ new ItemId( 'Q1' ) ],
		];
	}

	/**
	 * @dataProvider minificationProvider
	 */
	public function testMinification( DataValue $dataValue, array $expected ) {
		$serializer = new MinifyingDataValueSerializer();

		$this->assertTrue( $serializer->isSerializerFor( $dataValue ) );

		$serialization = $serializer->serialize( $dataValue );
		$this->assertSame( [
			'value' => $expected,
			'type' => 'wikibase-entityid',
		], $serialization );
	}

	public function minificationProvider() {
		return [
			'do not empty legacy numeric id' => [
				$this->newEntityIdValue( [
					'entity-type' => 'custom-type',
					'numeric-id' => 1,
				] ),
				[
					'entity-type' => 'custom-type',
					'numeric-id' => 1,
				]
			],
			'minimize redundant id' => [
				$this->newEntityIdValue( [
					'entity-type' => 'custom-type',
					'numeric-id' => 1,
					'id' => 'Q1',
				] ),
				[
					'id' => 'Q1',
				]
			],
			'do not touch string id' => [
				$this->newEntityIdValue( [
					'id' => 'Q1',
				] ),
				[
					'id' => 'Q1',
				]
			],
			'integration test' => [
				new EntityIdValue( new ItemId( 'Q1' ) ),
				[
					'id' => 'Q1',
				]
			],
		];
	}

	/**
	 * @param array $arrayValue
	 *
	 * @return EntityIdValue
	 */
	private function newEntityIdValue( array $arrayValue ) {
		$mock = $this->getMockBuilder( EntityIdValue::class )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->once() )
			->method( 'toArray' )
			->will( $this->returnValue( [
				'value' => $arrayValue,
				'type' => 'wikibase-entityid',
			] ) );

		return $mock;
	}

}
