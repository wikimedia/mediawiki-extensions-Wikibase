<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Deserializers\EntityDeserializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class EntityDeserializerTest extends DeserializerBaseTest {

	/**
	 * @return Deserializer
	 */
	public function buildDeserializer() {
		$entityIdDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'Q42' ) )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );

		$claim = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$claim->setGuid( 'test' );

		$claimsDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$claimsDeserializerMock->expects( $this->any() )
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
			->will( $this->returnValue( new Claims( array( $claim ) ) ) );

		$entityDeserializerMock = $this->getMockForAbstractClass(
			'\Wikibase\DataModel\Deserializers\EntityDeserializer',
			array( 'item', $entityIdDeserializerMock, $claimsDeserializerMock )
		);
		$entityDeserializerMock->expects( $this->any() )
			->method( 'getPartiallyDeserialized' )
			->will( $this->returnValue( Item::newEmpty() ) );

		return $entityDeserializerMock;
	}

	public function deserializableProvider() {
		return array(
			array(
				array(
					'type' => 'item'
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
					'type' => 'property'
				)
			),
		);
	}

	public function deserializationProvider() {
		$provider = array(
			array(
				Item::newEmpty(),
				array(
					'type' => 'item'
				)
			),
		);

		$entity = Item::newEmpty();
		$entity->setId( new ItemId( 'Q42' ) );
		$provider[] = array(
			$entity,
			array(
				'type' => 'item',
				'id' => 'Q42'
			)
		);


		$entity = Item::newEmpty();
		$entity->setLabels( array(
			'en' => 'Nyan Cat',
			'fr' => 'Nyan Cat'
		) );
		$provider[] = array(
			$entity,
			array(
				'type' => 'item',
				'labels' => array(
					'en' => array(
						'language' => 'en',
						'value' => 'Nyan Cat'
					),
					'fr' => array(
						'language' => 'fr',
						'value' => 'Nyan Cat'
					)
				)
			)
		);

		$entity = Item::newEmpty();
		$entity->setDescriptions( array(
			'en' => 'A Nyan Cat',
			'fr' => 'A Nyan Cat'
		) );
		$provider[] = array(
			$entity,
			array(
				'type' => 'item',
				'descriptions' => array(
					'en' => array(
						'language' => 'en',
						'value' => 'A Nyan Cat'
					),
					'fr' => array(
						'language' => 'fr',
						'value' => 'A Nyan Cat'
					)
				)
			)
		);

		$entity = Item::newEmpty();
		$entity->setAliases( 'en', array( 'Cat', 'My cat' ) );
		$entity->setAliases( 'fr', array( 'Cat' ) );
		$provider[] = array(
			$entity,
			array(
				'type' => 'item',
				'aliases' => array(
					'en' => array(
						array(
							'language' => 'en',
							'value' => 'Cat'
						),
						array(
							'language' => 'en',
							'value' => 'My cat'
						)
					),
					'fr' => array(
						array(
							'language' => 'fr',
							'value' => 'Cat'
						)
					)
				)
			)
		);

		$entity = Item::newEmpty();
		$entity->getStatements()->addNewStatement( new PropertyNoValueSnak( 42 ), null, null, 'test' );
		$provider[] = array(
			$entity,
			array(
				'type' => 'item',
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
					'type' => 'item',
					'aliases' => array(
						'en' => 'Cat'
					)
				)
			),
		);
	}

}
