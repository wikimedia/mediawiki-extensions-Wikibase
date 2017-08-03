<?php

namespace Wikibase\Repo\Content;

use InvalidArgumentException;
use MediaWiki\Interwiki\InterwikiLookup;
use MWException;
use OutOfBoundsException;
use Title;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\EntityContent;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Store\EntityIdLookup;
use Wikimedia\Assert\Assert;

/**
 * Factory for EntityContent objects.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityContentFactory implements EntityTitleStoreLookup, EntityIdLookup {

	/**
	 * @var string[] Entity type ID to content model ID mapping.
	 */
	private $entityContentModels;

	/**
	 * @var callable[] Entity type ID to callback mapping for creating ContentHandler objects.
	 */
	private $entityHandlerFactoryCallbacks;

	/**
	 * @var InterwikiLookup|null
	 */
	private $interwikiLookup;

	/**
	 * @var EntityHandler[] Entity type ID to entity handler mapping.
	 */
	private $entityHandlers = [];

	/**
	 * @param string[] $entityContentModels Entity type ID to content model ID mapping.
	 * @param callable[] $entityHandlerFactoryCallbacks Entity type ID to callback mapping for
	 *  creating ContentHandler objects.
	 * @param InterwikiLookup|null $interwikiLookup
	 */
	public function __construct(
		array $entityContentModels,
		array $entityHandlerFactoryCallbacks,
		InterwikiLookup $interwikiLookup = null
	) {
		Assert::parameterElementType( 'string', $entityContentModels, '$entityContentModels' );
		Assert::parameterElementType( 'callable', $entityHandlerFactoryCallbacks, '$entityHandlerFactoryCallbacks' );

		$this->entityContentModels = $entityContentModels;
		$this->entityHandlerFactoryCallbacks = $entityHandlerFactoryCallbacks;
		$this->interwikiLookup = $interwikiLookup;
	}

	/**
	 * Determines whether the given content model is designated to hold some kind of Wikibase entity.
	 *
	 * @param string $contentModel
	 *
	 * @return bool If the given content model ID is a known entity content model.
	 */
	public function isEntityContentModel( $contentModel ) {
		return in_array( $contentModel, $this->entityContentModels );
	}

	/**
	 * @return string[] A list of content model IDs used to represent Wikibase entities.
	 */
	public function getEntityContentModels() {
		return array_values( $this->entityContentModels );
	}

	/**
	 * @return string[] A list of entity type IDs used for Wikibase entities.
	 */
	public function getEntityTypes() {
		return array_keys( $this->entityContentModels );
	}

	/**
	 * Returns the Title object for the item with provided id.
	 *
	 * @param EntityId $id
	 *
	 * @throws MWException
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		if ( $id->isForeign() ) {
			// TODO: The interwiki prefix *should* be the same as the repo name,
			//        but we have no way to know or guarantee this! See T153496.
			$interwiki = $id->getRepositoryName();

			if ( $this->interwikiLookup && $this->interwikiLookup->isValidInterwiki( $interwiki ) ) {
				$pageName = 'EntityPage/' . $id->getLocalPart();

				// TODO: use a TitleFactory
				return Title::makeTitle( NS_SPECIAL, $pageName, '', $interwiki );
			}
		}

		$handler = $this->getContentHandlerForType( $id->getEntityType() );
		return $handler->getTitleForId( $id );
	}

	/**
	 * Returns the ID of the entity associated with the given page title.
	 *
	 * @note There is no guarantee that the EntityId returned by this method refers to
	 * an existing entity.
	 *
	 * @param Title $title
	 *
	 * @return EntityId|null
	 */
	public function getEntityIdForTitle( Title $title ) {
		$contentModel = $title->getContentModel();

		try {
			$handler = $this->getEntityHandlerForContentModel( $contentModel );
			return $handler->getIdForTitle( $title );
		} catch ( OutOfBoundsException $ex ) {
			// Not an entity content model
		} catch ( EntityIdParsingException $ex ) {
			// Not a valid entity page title.
		}

		return null;
	}

	/**
	 * @see EntityIdLookup::getEntityIds
	 *
	 * @note: the current implementation skips non-existing entities, but there is no guarantee
	 * that this will always be the case.
	 *
	 * @param Title[] $titles
	 *
	 * @throws StorageException
	 * @return EntityId[] Entity IDs, keyed by page IDs.
	 */
	public function getEntityIds( array $titles ) {
		$entityIds = [];

		foreach ( $titles as $title ) {
			$pageId = $title->getArticleID();

			if ( $pageId > 0 ) {
				$entityId = $this->getEntityIdForTitle( $title );

				if ( $entityId !== null ) {
					$entityIds[$pageId] = $entityId;
				}
			}
		}

		return $entityIds;
	}

	/**
	 * Determines what namespace is suitable for the given type of entities.
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return int
	 */
	public function getNamespaceForType( $entityType ) {
		$handler = $this->getContentHandlerForType( $entityType );
		return $handler->getEntityNamespace();
	}

	/**
	 * Returns the EntityHandler for the given entity type.
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return EntityHandler
	 */
	public function getContentHandlerForType( $entityType ) {
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
	public function getEntityHandlerForContentModel( $contentModel ) {
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
	 * @return int
	 */
	public function getContentModelForType( $entityType ) {
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
	public function newFromEntity( EntityDocument $entity ) {
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
	public function newFromRedirect( EntityRedirect $redirect ) {
		$handler = $this->getContentHandlerForType( $redirect->getEntityId()->getEntityType() );
		return $handler->makeEntityRedirectContent( $redirect );
	}

}
