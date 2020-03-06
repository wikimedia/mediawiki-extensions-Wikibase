<?php

namespace Wikibase\Lib;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikimedia\Assert\Assert;

/**
 * Factory for new, empty Entity objects.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityFactory {

	/**
	 * @var callable[]
	 */
	private $instantiators;

	/**
	 * @param callable[] $instantiators Array mapping entity type identifiers to callbacks returning
	 *  a new, empty entity of that type.
	 */
	public function __construct( array $instantiators ) {
		Assert::parameterElementType( 'callable', $instantiators, '$instantiators' );

		$this->instantiators = $instantiators;
	}

	/**
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException
	 * @return EntityDocument
	 */
	public function newEmpty( $entityType ) {
		if ( !isset( $this->instantiators[$entityType] ) ) {
			throw new OutOfBoundsException( 'Unknown entity type ' . $entityType );
		}

		$entity = call_user_func( $this->instantiators[$entityType] );

		Assert::postcondition(
			$entity instanceof EntityDocument,
			'Instantiator callback for ' . $entityType . ' did not return an Entity.'
		);

		return $entity;
	}

}
