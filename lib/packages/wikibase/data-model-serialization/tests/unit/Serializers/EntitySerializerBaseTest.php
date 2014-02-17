<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Serializers\EntitySerializer
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
abstract class EntitySerializerBaseTest extends SerializerBaseTest {

	protected function getClaimsSerializerMock() {
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

		return $claimsSerializerMock;
	}

	/**
	 * @return Entity
	 */
	protected abstract function buildEmptyEntity();

	/**
	 * @return array
	 */
	protected abstract function buildEmptyEntitySerialization();

	public function serializationProvider() {
		$provider = array(
			array(
				$this->buildEmptyEntitySerialization(),
				$this->buildEmptyEntity()
			)
		);

		$entity = $this->buildEmptyEntity();
		$entity->setId( new PropertyId( 'P42' ) );
		$provider[] = array(
			array(
				'id' => 'P42'
			) + $this->buildEmptyEntitySerialization(),
			$entity
		);

		$entity = $this->buildEmptyEntity();
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'test' );
		$entity->setClaims( new Claims( array( $claim ) ) );
		$provider[] = array(
			array(
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
			) + $this->buildEmptyEntitySerialization(),
			$entity
		);

		return $provider;
	}
}
