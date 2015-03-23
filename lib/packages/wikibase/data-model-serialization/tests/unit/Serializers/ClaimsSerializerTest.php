<?php

namespace Tests\Wikibase\DataModel\Serializers;

use stdClass;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Serializers\ClaimsSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Serializers\ClaimsSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ClaimsSerializerTest extends SerializerBaseTest {

	protected function buildSerializer() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$claimSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$claimSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( $statement ) )
			->will( $this->returnValue( array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'normal'
			) ) );

		return new ClaimsSerializer( $claimSerializerMock, false );
	}

	public function serializableProvider() {
		$claim = new Statement( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'test' );

		return array(
			array(
				new Claims()
			),
			array(
				new Claims( array(
					$claim
				) )
			),
		);
	}

	public function nonSerializableProvider() {
		return array(
			array(
				5
			),
			array(
				array()
			),
			array(
				new Statement( new PropertyNoValueSnak( 42 ) )
			),
		);
	}

	public function serializationProvider() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		return array(
			array(
				array(),
				new Claims()
			),
			array(
				array(
					'P42' => array(
						array(
							'mainsnak' => array(
								'snaktype' => 'novalue',
								'property' => 'P42'
							),
							'type' => 'statement',
							'rank' => 'normal'
						)
					)
				),
				new Claims( array(
					$statement
				) )
			),
		);
	}

	public function testClaimsSerializerWithOptionObjectsForMaps() {
		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );
		$claimSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$claimSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( $statement ) )
			->will( $this->returnValue( array(
				'mockedsuff' => array(),
				'type' => 'statement',
			) ) );
		$serializer = new ClaimsSerializer( $claimSerializerMock, true );

		$claims = new Claims( array( $statement ) );

		$serial = new stdClass();
		$serial->P42 = array( array(
			'mockedsuff' => array(),
			'type' => 'statement',
		) );
		$this->assertEquals( $serial, $serializer->serialize( $claims ) );
	}

}
