<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikimedia\Assert\Assert;

/**
 * Turns entity change request into ChangeOp objects based on change request deserialization
 * configured for the particular entity type.
 *
 * @license GPL-2.0+
 */
class EntityChangeOpProvider {

	/**
	 * @var callable[]
	 */
	private $changeOpDeserializerCallbacks;

	/**
	 * @var ChangeOpDeserializer[]
	 */
	private $changeOpDeserializers = [];

	/**
	 * @param callable[] $changeOpDeserializerCallbacks Associative array mapping entity types (strings)
	 * to callbacks instantiating ChangeOpDeserializer objects.
	 */
	public function __construct( array $changeOpDeserializerCallbacks ) {
		Assert::parameterElementType( 'callable', $changeOpDeserializerCallbacks, '$changeOpDeserializerCallbacks' );
		Assert::parameterElementType(
			'string',
			array_keys( $changeOpDeserializerCallbacks ),
			'array_keys( $changeOpDeserializerCallbacks )'
		);

		$this->changeOpDeserializerCallbacks = $changeOpDeserializerCallbacks;
	}

	/**
	 * @param string $entityType
	 * @param array $changeRequest Data of change to apply, @see docs/change-op-serializations.wiki for format specification
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
		if ( !array_key_exists( $type, $this->changeOpDeserializerCallbacks ) ) {
			throw new ChangeOpDeserializationException(
				'Could not process change request for entity of type: ' . $type,
				'no-change-request-deserializer'
			);
		}

		$deserializer = call_user_func( $this->changeOpDeserializerCallbacks[$type] );
		if ( ! $deserializer instanceof ChangeOpDeserializer ) {
			throw new ChangeOpDeserializationException(
				'Could not process change request for entity of type: ' . $type,
				'no-change-request-deserializer'
			);
		}

		return $deserializer;
	}

}
