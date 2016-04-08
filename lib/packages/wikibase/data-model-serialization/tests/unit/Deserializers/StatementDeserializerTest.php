<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\StatementDeserializer;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Deserializers\StatementDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class StatementDeserializerTest extends DispatchableDeserializerTest {

	protected function buildDeserializer() {
		$snakDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$snakDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
					'snaktype' => 'novalue',
					'property' => 'P42'
			) ) )
			->will( $this->returnValue( new PropertyNoValueSnak( 42 ) ) );

		$snaksDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$snaksDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'P42' => array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					)
				)
			) ) )
			->will( $this->returnValue( new SnakList( array(
				new PropertyNoValueSnak( 42 )
			) ) ) );

		$referencesDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$referencesDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array() ) )
			->will( $this->returnValue( new ReferenceList() ) );

		return new StatementDeserializer( $snakDeserializerMock, $snaksDeserializerMock, $referencesDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'mainsnak' => array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					),
					'type' => 'claim'
				)
			),
			array(
				array(
					'mainsnak' => array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					),
					'type' => 'statement',
					'rank' => 'normal'
				)
			),
		);
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				42
			),
			array(
				array(
					'id' => 'P10'
				)
			),
			array(
				array(
					'type' => '42'
				)
			),
		);
	}

	public function deserializationProvider() {
		$serializations = array();

		$serializations[] = array(
			new Statement( new PropertyNoValueSnak( 42 ) ),
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'claim'
			)
		);

		$serializations[] = array(
			new Statement( new PropertyNoValueSnak( 42 ) ),
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement'
			)
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'q42' );
		$serializations[] = array(
			$statement,
			array(
				'id' => 'q42',
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'claim'
			)
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_PREFERRED );
		$serializations[] = array(
			$statement,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'preferred'
			)
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_NORMAL );
		$serializations[] = array(
			$statement,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'normal'
			)
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_DEPRECATED );
		$serializations[] = array(
			$statement,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'deprecated'
			)
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( array() ) );
		$serializations[] = array(
			$statement,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'normal'
			)
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( array(
			new PropertyNoValueSnak( 42 )
		) ) );
		$serializations[] = array(
			$statement,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'qualifiers' => array(
					'P42' => array(
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				),
				'type' => 'statement',
				'rank' => 'normal'
			)
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setReferences( new ReferenceList() );
		$serializations[] = array(
			$statement,
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => "P42"
				),
				'references' => array(),
				'type' => 'statement',
				'rank' => 'normal'
			)
		);

		return $serializations;
	}

	/**
	 * @dataProvider invalidDeserializationProvider
	 */
	public function testInvalidSerialization( $serialization ) {
		$this->setExpectedException( '\Deserializers\Exceptions\DeserializationException' );
		$this->buildDeserializer()->deserialize( $serialization );
	}

	public function invalidDeserializationProvider() {
		return array(
			array(
				array(
					'type' => 'claim'
				)
			),
			array(
				array(
					'id' => 42,
					'mainsnak' => array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					),
					'type' => 'claim'
				)
			),
			array(
				array(
					'mainsnak' => array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					),
					'type' => 'statement',
					'rank' => 'nyan-cat'
				)
			),
		);
	}

	public function testQualifiersOrderDeserialization() {
		$snakDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$snakDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
				'snaktype' => 'novalue',
				'property' => 'P42'
			) ) )
			->will( $this->returnValue( new PropertyNoValueSnak( 42 ) ) );

		$snaksDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$snaksDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
					'P24' => array(
						array(
							'snaktype' => 'novalue',
							'property' => 'P24'
						)
					),
					'P42' => array(
						array(
							'snaktype' => 'somevalue',
							'property' => 'P42'
						),
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				)
			) )
			->will( $this->returnValue( new SnakList( array(
				new PropertyNoValueSnak( 24 ),
				new PropertySomeValueSnak( 42 ),
				new PropertyNoValueSnak( 42 )
			) ) ) );

		$referencesDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$statementDeserializer = new StatementDeserializer( $snakDeserializerMock, $snaksDeserializerMock, $referencesDeserializerMock );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( array(
			new PropertySomeValueSnak( 42 ),
			new PropertyNoValueSnak( 42 ),
			new PropertyNoValueSnak( 24 )
		) ) );

		$serialization = array(
			'mainsnak' => array(
				'snaktype' => 'novalue',
				'property' => 'P42'
			),
			'qualifiers' => array(
				'P24' => array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P24'
					)
				),
				'P42' => array(
					array(
						'snaktype' => 'somevalue',
						'property' => 'P42'
					),
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					)
				)
			),
			'qualifiers-order' => array(
				'P42',
				'P24'
			),
			'type' => 'claim'
		);

		$this->assertEquals( $statement->getHash(), $statementDeserializer->deserialize( $serialization )->getHash() );
	}

	public function testQualifiersOrderDeserializationWithTypeError() {
		$serialization = array(
			'mainsnak' => array(
				'snaktype' => 'novalue',
				'property' => 'P42'
			),
			'qualifiers' => array(
				'P42' => array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					)
				)
			),
			'qualifiers-order' => 'stringInsteadOfArray',
			'type' => 'claim'
		);

		$deserializer = $this->buildDeserializer();

		$this->setExpectedException( 'Deserializers\Exceptions\InvalidAttributeException' );
		$deserializer->deserialize( $serialization );
	}

}
