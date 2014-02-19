<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Serializers\EntitySerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class EntitySerializerTest extends SerializerBaseTest {

	public function buildSerializer() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'test' );

		$claimsSerializerMock = $this->getMock( '\Serializers\Serializer' );
		$claimsSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( $this->equalTo( new Claims( array( $claim ) ) ) )
			->will( $this->returnValue( array(
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
			) ) );

		$entitySerializerMock = $this->getMockForAbstractClass(
			'\Wikibase\DataModel\Serializers\EntitySerializer',
			array( $claimsSerializerMock )
		);
		$entitySerializerMock->expects( $this->any() )
			->method( 'getSpecificSerialization' )
			->will( $this->returnValue( array() ) );

		return $entitySerializerMock;
	}

	public function serializableProvider() {
		return array(
			array(
				Item::newEmpty()
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
				new PropertyNoValueSnak( 42 )
			),
		);
	}

	public function serializationProvider() {
		$provider = array(
			array(
				array(
					'type' => 'item'
				),
				Item::newEmpty()
			),
		);

		$entity = Item::newEmpty();
		$entity->setId( new ItemId( 'Q42' ) );
		$provider[] = array(
			array(
				'type' => 'item',
				'id' => 'Q42'
			),
			$entity
		);

		$entity = Item::newEmpty();
		$entity->setLabels( array(
			'en' => 'Nyan Cat',
			'fr' => 'Nyan Cat'
		) );
		$provider[] = array(
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
			),
			$entity
		);

		$entity = Item::newEmpty();
		$entity->setDescriptions( array(
			'en' => 'A Nyan Cat',
			'fr' => 'A Nyan Cat'
		) );
		$provider[] = array(
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
			),
			$entity
		);

		$entity = Item::newEmpty();
		$entity->setAliases( 'en', array( 'Cat', 'My cat' ) );
		$entity->setAliases( 'fr', array( 'Cat' ) );
		$provider[] = array(
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
			),
			$entity
		);

		$entity = Item::newEmpty();
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'test' );
		$entity->setClaims( new Claims( array( $claim ) ) );
		$provider[] = array(
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
			),
			$entity
		);

		return $provider;
	}
}
