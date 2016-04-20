<?php

namespace Wikibase;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikimedia\Assert\Assert;

/**
 * Factory for Entity objects.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityFactory {

	/**
	 * @var callable[] Maps entity types to instantiator callbacks.
	 */
	private $instantiators;

	/**
	 * @since 0.5
	 *
	 * @param callable[] $instantiators Maps entity types to instantiator callbacks.
	 */
	public function __construct( array $instantiators ) {
		Assert::parameterElementType( 'callable', $instantiators, '$instantiators' );

		$this->instantiators = $instantiators;
	}

	/**
	 * Returns the instantiator for the given entity type.
	 *
	 * @param string $type
	 *
	 * @throws OutOfBoundsException
	 * @return string callable
	 */
	private function getEntityInstantiator( $type ) {
		if ( !isset( $this->instantiators[$type] ) ) {
			throw new OutOfBoundsException( 'Unknown entity type ' . $type );
		}

		return $this->instantiators[$type];
	}

	/**
	 * @since 0.3
	 *
	 * @param string $entityType The type of the desired new entity.
	 *
	 * @return EntityDocument
	 */
	public function newEmpty( $entityType ) {
		$instantiator = $this->getEntityInstantiator( $entityType );

		$entity = call_user_func( $instantiator );

		Assert::postcondition(
			$entity instanceof EntityDocument,
			'Instantiator callback for ' . $entityType . ' did not return an Entity.'
		);

		return $entity;
	}

}
