<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Serializers\StatementSerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class StatementSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		$snakSerializerFake = $this->getMock( '\Serializers\Serializer' );
		$snakSerializerFake->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array(
				'snaktype' => 'novalue',
				'property' => "P42"
			) ) );

		$snaksSerializerFake = $this->getMock( '\Serializers\Serializer' );
		$snaksSerializerFake->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array(
				'P42' => array(
					array(
						'snaktype' => 'novalue',
						'property' => 'P42'
					)
				)
			) ) );

		$referencesSerializerFake = $this->getMock( '\Serializers\Serializer' );
		$referencesSerializerFake->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array(
				array(
					'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
					'snaks' => array()
				)
			) ) );

		return new StatementSerializer( $snakSerializerFake, $snaksSerializerFake, $referencesSerializerFake );
	}

	public function serializableProvider() {
		return array(
			array(
				new Statement( new PropertyNoValueSnak( 42 ) )
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
				new ItemId( 'Q42' )
			),
		);
	}

	public function serializationProvider() {
		$serializations = array();

		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'normal'
			),
			new Statement( new PropertyNoValueSnak( 42 ) )
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'q42' );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'id' => 'q42',
				'rank' => 'normal'
			),
			$statement
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_PREFERRED );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'preferred'
			),
			$statement
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setRank( Statement::RANK_DEPRECATED );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'type' => 'statement',
				'rank' => 'deprecated'
			),
			$statement
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( array() ) );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => "P42"
				),
				'type' => 'statement',
				'rank' => 'normal'
			),
			$statement
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( array(
			new PropertyNoValueSnak( 42 )
		) ) );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => "P42"
				),
				'type' => 'statement',
				'qualifiers' => array(
					'P42' => array(
						array(
							'snaktype' => 'novalue',
							'property' => 'P42'
						)
					)
				),
				'qualifiers-order' => array(
					'P42'
				),
				'rank' => 'normal'
			),
			$statement
		);

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setReferences( new ReferenceList( array(
			new Reference( array( new PropertyNoValueSnak( 1 ) ) )
		) ) );
		$serializations[] = array(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => "P42"
				),
				'type' => 'statement',
				'rank' => 'normal',
				'references' => array(
					array(
						'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
						'snaks' => array()
					)
				),
			),
			$statement
		);

		return $serializations;
	}

	public function testQualifiersOrderSerialization() {
		$snakSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snakSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array(
				'snaktype' => 'novalue',
				'property' => 'P42'
			) ) );

		$snaksSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$snaksSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->will( $this->returnValue( array() ) );

		$referencesSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$statementSerializer = new StatementSerializer( $snakSerializerMock, $snaksSerializerMock, $referencesSerializerMock );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setQualifiers( new SnakList( array(
			new PropertyNoValueSnak( 42 ),
			new PropertySomeValueSnak( 24 ),
			new PropertyNoValueSnak( 24 )
		) ) );
		$this->assertEquals(
			array(
				'mainsnak' => array(
					'snaktype' => 'novalue',
					'property' => 'P42'
				),
				'qualifiers' => array(),
				'qualifiers-order' => array(
					'P42',
					'P24'
				),
				'type' => 'statement',
				'rank' => 'normal'
			),
			$statementSerializer->serialize( $statement )
		);
	}

}
