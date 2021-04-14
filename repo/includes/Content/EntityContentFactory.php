<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Content;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikimedia\Assert\Assert;

/**
 * Factory for EntityContent objects.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityContentFactory {

	/**
	 * @var string[] Entity type ID to content model ID mapping.
	 */
	private $entityContentModels;

	/**
	 * @var callable[] Entity type ID to callback mapping for creating ContentHandler objects.
	 */
	private $entityHandlerFactoryCallbacks;

	/**
	 * @var EntityHandler[] Entity type ID to entity handler mapping.
	 */
	private $entityHandlers = [];

	/**
	 * @param string[] $entityContentModels Entity type ID to content model ID mapping.
	 * @param callable[] $entityHandlerFactoryCallbacks Entity type ID to callback mapping for
	 *  creating ContentHandler objects.
	 */
	public function __construct(
		array $entityContentModels,
		array $entityHandlerFactoryCallbacks
	) {
		Assert::parameterElementType( 'string', $entityContentModels, '$entityContentModels' );
		Assert::parameterElementType( 'callable', $entityHandlerFactoryCallbacks, '$entityHandlerFactoryCallbacks' );

		$this->entityContentModels = $entityContentModels;
		$this->entityHandlerFactoryCallbacks = $entityHandlerFactoryCallbacks;
	}

	/**
	 * Determines whether the given content model is designated to hold some kind of Wikibase entity.
	 *
	 * @param string $contentModel
	 *
	 * @return bool If the given content model ID is a known entity content model.
	 */
	public function isEntityContentModel( string $contentModel ): bool {
		return in_array( $contentModel, $this->entityContentModels );
	}

	/**
	 * @return string[] A list of content model IDs used to represent Wikibase entities.
	 */
	public function getEntityContentModels(): array {
		return array_values( $this->entityContentModels );
	}

	/**
	 * @return string[] A list of entity type IDs used for Wikibase entities.
	 */
	public function getEntityTypes(): array {
		return array_keys( $this->entityContentModels );
	}

	/**
	 * Determines what namespace is suitable for the given type of entities.
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return int
	 */
	public function getNamespaceForType( string $entityType ): int {
		$handler = $this->getContentHandlerForType( $entityType );
		return $handler->getEntityNamespace();
	}

	/**
	 * Determines which slot is used to store a given type of entities.
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return string the role name of the slot
	 */
	public function getSlotRoleForType( string $entityType ): string {
		$handler = $this->getContentHandlerForType( $entityType );
		return $handler->getEntitySlotRole();
	}

	/**
	 * Returns the EntityHandler for the given entity type.
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return EntityHandler
	 */
	public function getContentHandlerForType( string $entityType ): EntityHandler {
		if ( !isset( $this->entityHandlerFactoryCallbacks[$entityType] ) ) {
			throw new OutOfBoundsException( 'No content handler defined for entity type ' . $entityType );
		}

		if ( !isset( $this->entityHandlers[$entityType] ) ) {
			$entityHandler = call_user_func( $this->entityHandlerFactoryCallbacks[$entityType] );

			Assert::postcondition(
				$entityHandler instanceof EntityHandler,
				'Callback must return an instance of EntityHandler'
			);

			$this->entityHandlers[$entityType] = $entityHandler;
		}

		return $this->entityHandlers[$entityType];
	}

	/**
	 * Returns the EntityHandler for the given model id.
	 *
	 * @param string $contentModel
	 *
	 * @throws OutOfBoundsException if no entity handler is defined for the given content model.
	 * @return EntityHandler
	 */
	public function getEntityHandlerForContentModel( string $contentModel ): EntityHandler {
		$entityTypePerModel = array_flip( $this->entityContentModels );

		if ( !isset( $entityTypePerModel[$contentModel] ) ) {
			throw new OutOfBoundsException( 'No entity handler defined for content model ' . $contentModel );
		}

		return $this->getContentHandlerForType( $entityTypePerModel[$contentModel] );
	}

	/**
	 * Determines what content model is suitable for the given type of entities.
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return string
	 */
	public function getContentModelForType( string $entityType ): string {
		if ( !isset( $this->entityContentModels[$entityType] ) ) {
			throw new OutOfBoundsException( 'No content model defined for entity type ' . $entityType );
		}

		return $this->entityContentModels[$entityType];
	}

	/**
	 * Constructs a new EntityContent from an Entity.
	 *
	 * @see EntityHandler::makeEntityContent
	 *
	 * @param EntityDocument $entity
	 *
	 * @return EntityContent
	 */
	public function newFromEntity( EntityDocument $entity ): EntityContent {
		$handler = $this->getContentHandlerForType( $entity->getType() );
		return $handler->makeEntityContent( new EntityInstanceHolder( $entity ) );
	}

	/**
	 * Constructs a new EntityContent from an EntityRedirect,
	 * or null if the respective kind of entity does not support redirects.
	 *
	 * @see EntityHandler::makeEntityRedirectContent
	 *
	 * @param EntityRedirect $redirect
	 *
	 * @return EntityContent|null
	 */
	public function newFromRedirect( EntityRedirect $redirect ): ?EntityContent {
		$handler = $this->getContentHandlerForType( $redirect->getEntityId()->getEntityType() );
		return $handler->makeEntityRedirectContent( $redirect );
	}

}
