<?php

namespace Wikibase\Test;

use ValueFormatters\FormatterOptions;
use DataValues\StringValue;
use Wikibase\Claim;
use Wikibase\Entity;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyValueSnak;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\Serializers\EntitySerializationOptions;

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
		return new $class( new EntitySerializationOptions() );
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

		$options = new EntitySerializationOptions();
		$options->setProps( array( 'aliases' ) );

		$entity0 = $entity->copy();
		$entity0->setAliases( 'en', array( 'foo', 'bar' ) );
		$entity0->setAliases( 'de', array( 'baz', 'bah' ) );

		$validArgs[] = array(
			$entity0,
			array(
				'id' => $this->getFormattedIdForEntity( $entity0 ),
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

		$options = new EntitySerializationOptions();
		$options->setProps( array( 'descriptions', 'labels' ) );

		$entity1 = $entity->copy();
		$entity1->setLabel( 'en', 'foo' );
		$entity1->setLabel( 'de', 'bar' );
		$entity1->setDescription( 'en', 'baz' );
		$entity1->setDescription( 'de', 'bah' );

		$validArgs[] = array(
			$entity1,
			array(
				'id' => $this->getFormattedIdForEntity( $entity1 ),
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
		$options->setProps( array( 'descriptions', 'labels', 'claims', 'aliases' ) );

		$claim = new Claim(
			new PropertyValueSnak(
				new PropertyId( 'P42' ),
				new StringValue( 'foobar!' )
			)
		);

		$claimGuidGenerator = new ClaimGuidGenerator( $entity2->getId() );
		$claim->setGuid( $claimGuidGenerator->newGuid() );

		$entity2->setLabel( 'en', 'foo' );
		$entity2->addClaim( $claim );

		$validArgs[] = array(
			$entity2,
			array(
				'id' => $this->getFormattedIdForEntity( $entity2 ),
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

	protected function getFormattedIdForEntity( Entity $entity ) {
		return $this->getIdFormatter()->format( $entity->getId() );
	}

	protected function getIdFormatter() {
		$formatterOptions = new FormatterOptions();
		return new EntityIdFormatter( $formatterOptions );
	}

}
