<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikimedia\Assert\Assert;

/**
 * Turns entity change request into ChangeOp objects based on change request deserialization
 * configured for the particular entity type.
 *
 * @license GPL-2.0-or-later
 */
class EntityChangeOpProvider {

	/**
	 * @var callable[]
	 */
	private $changeOpDeserializerInstantiators;

	/**
	 * @var ChangeOpDeserializer[]
	 */
	private $changeOpDeserializers = [];

	/**
	 * @param callable[] $changeOpDeserializerInstantiators Associative array mapping entity types (strings)
	 * to callbacks instantiating ChangeOpDeserializer objects.
	 */
	public function __construct( array $changeOpDeserializerInstantiators ) {
		Assert::parameterElementType( 'callable', $changeOpDeserializerInstantiators, '$changeOpDeserializerInstantiators' );
		Assert::parameterElementType(
			'string',
			array_keys( $changeOpDeserializerInstantiators ),
			'array_keys( $changeOpDeserializerInstantiators )'
		);

		$this->changeOpDeserializerInstantiators = $changeOpDeserializerInstantiators;
	}

	/**
	 * @param string $entityType
	 * @param array $changeRequest Data of change to apply, @see @ref docs_topics_changeop-serializations for format specification
	 *
	 * @throws ChangeOpDeserializationException
	 *
	 * @return ChangeOp
	 */
	public function newEntityChangeOp( $entityType, array $changeRequest ) {
		$deserializer = $this->getDeserializerForEntityType( $entityType );

		return $deserializer->createEntityChangeOp( $changeRequest );
	}

	/**
	 * @param string $type
	 *
	 * @throws ChangeOpDeserializationException
	 *
	 * @return ChangeOpDeserializer
	 */
	private function getDeserializerForEntityType( $type ) {
		if ( !isset( $this->changeOpDeserializers[$type] ) ) {
			$this->changeOpDeserializers[$type] = $this->newDeserializerForEntityType( $type );
		}

		return $this->changeOpDeserializers[$type];
	}

	/**
	 * @param string $type
	 *
	 * @throws ChangeOpDeserializationException
	 *
	 * @return ChangeOpDeserializer
	 */
	private function newDeserializerForEntityType( $type ) {
		if ( !array_key_exists( $type, $this->changeOpDeserializerInstantiators ) ) {
			throw new ChangeOpDeserializationException(
				'Could not process change request for entity of type: ' . $type,
				'no-change-request-deserializer'
			);
		}

		$deserializer = call_user_func( $this->changeOpDeserializerInstantiators[$type] );
		Assert::postcondition(
			$deserializer instanceof ChangeOpDeserializer,
			'changeop-deserializer-callback defined for entity type: ' . $type . ' does not instantiate ChangeOpDeserializer'
		);

		return $deserializer;
	}

}
