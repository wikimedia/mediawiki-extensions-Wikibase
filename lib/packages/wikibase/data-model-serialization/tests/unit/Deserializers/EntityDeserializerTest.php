<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\Fingerprint;

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

		$fingerprintDeserializerMock = $this->getMock( '\Deserializers\Deserializer' );
		$fingerprintDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->will( $this->returnValue( new Fingerprint() ) );
		

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
			array( 'item', $entityIdDeserializerMock, $fingerprintDeserializerMock, $claimsDeserializerMock )
		);
		$entityDeserializerMock->expects( $this->any() )
			->method( 'getPartiallyDeserialized' )
			->will( $this->returnValue( new Item() ) );

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
				new Item(),
				array(
					'type' => 'item'
				)
			),
		);

		$entity = new Item( new ItemId( 'Q42' ) );
		$provider[] = array(
			$entity,
			array(
				'type' => 'item',
				'id' => 'Q42'
			)
		);

		$entity = new Item();
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

}
