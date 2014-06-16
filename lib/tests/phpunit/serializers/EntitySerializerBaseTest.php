<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Entity;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Lib\Serializers\EntitySerializer
 *
 * @since 0.2
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntitySerializerBaseTest extends SerializerBaseTest {

	/**
	 * @since 0.2
	 *
	 * @return Entity
	 */
	abstract protected function getEntityInstance();

	protected function getInstance() {
		$class = $this->getClass();
		return new $class( new SerializationOptions() );
	}

	/**
	 * Returns arguments for entity agnostic arguments that can be returned
	 * by validProvider after making sure the provided serialization contains
	 * anything the entity implementing class requires.
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	protected function semiValidProvider() {
		$entity = $this->getEntityInstance();

		$validArgs = array();

		$options = new SerializationOptions();
		$options->setOption( EntitySerializer::OPT_PARTS, array( 'aliases' ) );

		$entity0 = $entity->copy();
		$entity0->setAliases( 'en', array( 'foo', 'bar' ) );
		$entity0->setAliases( 'de', array( 'baz', 'bah' ) );

		$validArgs[] = array(
			$entity0,
			array(
				'id' => $entity0->getId()->getSerialization(),
				'type' => $entity0->getType(),
				'aliases' => array(
					'en' => array(
						array(
							'value' => 'foo',
							'language' => 'en',
						),
						array(
							'value' => 'bar',
							'language' => 'en',
						),
					),
					'de' => array(
						array(
							'value' => 'baz',
							'language' => 'de',
						),
						array(
							'value' => 'bah',
							'language' => 'de',
						),
					),
				)
			),
			$options
		);

		$options = new SerializationOptions();
		$options->setOption( EntitySerializer::OPT_PARTS, array( 'descriptions', 'labels' ) );

		$entity1 = $entity->copy();
		$entity1->setLabel( 'en', 'foo' );
		$entity1->setLabel( 'de', 'bar' );
		$entity1->setDescription( 'en', 'baz' );
		$entity1->setDescription( 'de', 'bah' );

		$validArgs[] = array(
			$entity1,
			array(
				'id' => $entity1->getId()->getSerialization(),
				'type' => $entity1->getType(),
				'labels' => array(
					'en' => array(
						'value' => 'foo',
						'language' => 'en',
					),
					'de' => array(
						'value' => 'bar',
						'language' => 'de',
					),
				),
				'descriptions' => array(
					'en' => array(
						'value' => 'baz',
						'language' => 'en',
					),
					'de' => array(
						'value' => 'bah',
						'language' => 'de',
					),
				),
			),
			$options
		);

		$entity2 = $this->getEntityInstance();

		$options->setOption(
			EntitySerializer::OPT_PARTS,
			array( 'descriptions', 'labels', 'claims', 'aliases' )
		);

		$claim = new Claim(
			new PropertyValueSnak(
				new PropertyId( 'P42' ),
				new StringValue( 'foobar!' )
			)
		);

		$guidGenerator = new ClaimGuidGenerator();
		$claim->setGuid( $guidGenerator->newGuid( $entity2->getId() ) );

		$entity2->setLabel( 'en', 'foo' );
		$entity2->addClaim( $claim );

		$validArgs[] = array(
			$entity2,
			array(
				'id' => $entity2->getId()->getSerialization(),
				'type' => $entity2->getType(),
				'labels' => array(
					'en' => array(
						'language' => 'en',
						'value' => 'foo',
					)
				),
				'claims' => array(
					'P42' => array(
						array(
							'id' => $claim->getGuid(),
							'mainsnak' => array(
								'snaktype' => 'value',
								'property' => 'P42',
								'datavalue' => array(
									'value' => 'foobar!',
									'type' => 'string'
								)
							),
							'type' => 'claim'
						)
					)
				)
			),
			$options
		);

		return $validArgs;
	}

}
