<?php

namespace Wikibase\Repo\Content;

use Hooks;
use InvalidArgumentException;
use MediaWiki\Interwiki\InterwikiLookup;
use MWException;
use OutOfBoundsException;
use Title;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikimedia\Assert\Assert;

/**
 * Factory for EntityContent objects.
 *
 * @license GPL-2.0-or-later
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

	private $entitySourceDefinitions;
	private $localEntitySource;
	private $titleForIdCache;

	/**
	 * @param string[] $entityContentModels Entity type ID to content model ID mapping.
	 * @param callable[] $entityHandlerFactoryCallbacks Entity type ID to callback mapping for
	 *  creating ContentHandler objects.
	 * @param EntitySourceDefinitions $entitySourceDefinitions
	 * @param EntitySource $localEntitySource
	 * @param InterwikiLookup|null $interwikiLookup
	 */
	public function __construct(
		array $entityContentModels,
		array $entityHandlerFactoryCallbacks,
		EntitySourceDefinitions $entitySourceDefinitions,
		EntitySource $localEntitySource,
		InterwikiLookup $interwikiLookup = null
	) {
		Assert::parameterElementType( 'string', $entityContentModels, '$entityContentModels' );
		Assert::parameterElementType( 'callable', $entityHandlerFactoryCallbacks, '$entityHandlerFactoryCallbacks' );

		$this->entityContentModels = $entityContentModels;
		$this->entityHandlerFactoryCallbacks = $entityHandlerFactoryCallbacks;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
		$this->localEntitySource = $localEntitySource;
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
		if ( isset( $this->titleForIdCache[ $id->getSerialization() ] ) ) {
			return $this->titleForIdCache[ $id->getSerialization() ];
		}
		$title = $this->getTitleForFederatedId( $id );
		if ( $title ) {
			$this->titleForIdCache[ $id->getSerialization() ] = $title;
			return $title;
		}

		$handler = $this->getContentHandlerForType( $id->getEntityType() );
		$title = $handler->getTitleForId( $id );
		$this->titleForIdCache[ $id->getSerialization() ] = $title;
		return $title;
	}

	/**
	 * If the EntityId is federated, return a Title for it. Otherwise return null
	 *
	 * @param EntityId $id
	 * @return null|Title
	 */
	private function getTitleForFederatedId( EntityId $id ) {
		if ( $this->entityNotFromLocalEntitySource( $id ) ) {
			$interwiki = $this->entitySourceDefinitions->getSourceForEntityType( $id->getEntityType() )->getInterwikiPrefix();
			if ( $this->interwikiLookup && $this->interwikiLookup->isValidInterwiki( $interwiki ) ) {
				$pageName = 'EntityPage/' . $id->getSerialization();

				// TODO: use a TitleFactory
				$title = Title::makeTitle( NS_SPECIAL, $pageName, '', $interwiki );
				$this->titleForIdCache[ $id->getSerialization() ] = $title;
				return $title;
			}
		}

		return null;
	}

	/**
	 * Returns Title objects for the entities with provided ids
	 *
	 * @param EntityId[] $ids
	 *
	 * @throws MWException
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 * @return Title[]
	 */
	public function getTitlesForIds( array $ids ) {
		Assert::parameterElementType( 'Wikibase\DataModel\Entity\EntityId', $ids, '$ids' );
		$titles = [];
		$idsByType = [];
		// get whatever federated ids or cached ids we can, and batch the rest of the ids by type
		foreach ( $ids as $id ) {
			$idString = $id->getSerialization();
			if ( isset( $this->titleForIdCache[$idString] ) ) {
				$titles[$idString] = $this->titleForIdCache[$idString];
				continue;
			}
			$title = $this->getTitleForFederatedId( $id );
			if ( $title ) {
				$titles[$idString] = $title;
				continue;
			}
			$idsByType[ $id->getEntityType() ][] = $id;
		}

		foreach ( $idsByType as $entityType => $idsForType ) {
			$handler = $this->getContentHandlerForType( $entityType );
			$titlesForType = $handler->getTitlesForIds( $idsForType );
			$titles += $titlesForType;
		}

		foreach ( $titles as $idString => $title ) {
			$this->titleForIdCache[$idString] = $title;
		}

		return $titles;
	}

	private function entityNotFromLocalEntitySource( EntityId $id ) {
		$entitySource = $this->entitySourceDefinitions->getSourceForEntityType( $id->getEntityType() );
		return $entitySource->getSourceName() !== $this->localEntitySource->getSourceName();
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

		Hooks::run( 'GetEntityContentModelForTitle', [ $title, &$contentModel ] );

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
	 * @note the current implementation skips non-existing entities, but there is no guarantee
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
	 * Determines which slot is used to store a given type of entities.
	 *
	 * @param string $entityType
	 *
	 * @throws OutOfBoundsException if no content model is defined for the given entity type.
	 * @return string the role name of the slot
	 */
	public function getSlotRoleForType( $entityType ) {
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
