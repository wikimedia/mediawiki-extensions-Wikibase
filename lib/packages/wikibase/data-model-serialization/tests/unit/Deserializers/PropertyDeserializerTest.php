<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Wikibase\DataModel\Deserializers\PropertyDeserializer;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @covers Wikibase\DataModel\Deserializers\PropertyDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class PropertyDeserializerTest extends DeserializerBaseTest {

	public function buildDeserializer() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnValue( new PropertyId( 'P42' ) ) );

		$fingerprintDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$fingerprintDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnValue( new Fingerprint() ) );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'test' );

		$statementListDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$statementListDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( array(
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
			) ) )
			->will( $this->returnValue( new StatementList( array( $statement ) ) ) );

		return new PropertyDeserializer( $entityIdDeserializerMock, $fingerprintDeserializerMock, $statementListDeserializerMock );
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'type' => 'property'
				)
			),
		);
	}

	public function nonDeserializableProvider() {
		return array(
			array(
				5
			),
			array(
				array()
			),
			array(
				array(
					'type' => 'item'
				)
			),
		);
	}

	public function deserializationProvider() {
		$property = Property::newFromType( 'string' );

		$provider = array(
			array(
				$property,
				array(
					'type' => 'property',
					'datatype' => 'string'
				)
			),
		);

		$property = new Property( new PropertyId( 'P42' ), null, '' );
		$provider[] = array(
			$property,
			array(
				'type' => 'property',
				'datatype' => '',
				'id' => 'P42'
			)
		);

		$property = Property::newFromType( '' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = array(
			$property,
			array(
				'type' => 'property',
				'datatype' => '',
				'claims' => array(
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
				)
			)
		);

		$property = Property::newFromType( '' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = array(
			$property,
			array(
				'type' => 'property',
				'datatype' => '',
				'claims' => array(
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
				)
			)
		);

		return $provider;
	}

}
