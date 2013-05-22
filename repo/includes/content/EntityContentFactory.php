<?php

namespace Wikibase;

use MWException;
use Title;
use WikiPage;
use Revision;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * Factory for EntityContent objects.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityContentFactory {

	/**
	 * @since 0.2
	 * @deprecated since 0.4
	 *
	 * @return EntityContentFactory
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = WikibaseRepo::newInstance()->getEntityContentFactory();
		}

		return $instance;
	}

	// TODO: inject this map and allow extensions to somehow extend it
	protected static $typeMap = array(
		Item::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_ITEM,
		Property::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_PROPERTY,
	);

	protected $idFormatter;
	protected $contentModelIds;

	public function __construct( EntityIdFormatter $idFormatter, array $contentModelIds ) {
		$this->idFormatter = $idFormatter;
		$this->contentModelIds = $contentModelIds;
	}

	/**
	 * Determines whether the given content model is designated to hold some kind of Wikibase entity.
	 * Shorthand for in_array( $ns, self::getEntityModels() );
	 *
	 * @since 0.2
	 *
	 * @param String $model the content model ID
	 *
	 * @return bool True iff $model is an entity content model
	 */
	public function isEntityContentModel( $model ) {
		return in_array( $model, $this->getEntityContentModels() );
	}

	/**
	 * Returns a list of content model IDs that are used to represent Wikibase entities.
	 *
	 * @since 0.2
	 *
	 * @return array An array of string content model IDs.
	 */
	public function getEntityContentModels() {
		return $this->contentModelIds;
	}

	/**
	 * Get the items corresponding to the provided language and label pair.
	 * A description can also be provided, in which case only the item with
	 * that description will be returned (as only element in the array).
	 *
	 * @since 0.2
	 *
	 * @param string $language
	 * @param string $label
	 * @param string|null $description
	 * @param string|null $entityType
	 * @param bool $fuzzySearch if false, only exact matches are returned, otherwise more relaxed search . Defaults to false.
	 *
	 * @return EntityContent[]
	 */
	public function getFromLabel( $language, $label, $description = null, $entityType = null, $fuzzySearch = false ) {
		$entityIds = StoreFactory::getStore()->getTermIndex()->getEntityIdsForLabel( $label, $language, $description, $entityType, $fuzzySearch );
		$entities = array();

		foreach ( $entityIds as $entityId ) {
			list( $type, $id ) = $entityId;
			$entity = self::getFromId( new EntityId( $type, $id ) );

			if ( $entity !== null ) {
				$entities[] = $entity;
			}
		}

		return $entities;
	}

	/**
	 * Get the entity content for the entity with the provided id
	 * if it's available to the specified audience.
	 * If the specified audience does not have the ability to view this
	 * revision, if there is no such item, null will be returned.
	 *
	 * @since 0.2
	 *
	 * @param EntityId $id
	 *
	 * @param integer $audience: one of:
	 *      Revision::FOR_PUBLIC       to be displayed to all users
	 *      Revision::FOR_THIS_USER    to be displayed to $wgUser
	 *      Revision::RAW              get the text regardless of permissions
	 *
	 * @return EntityContent|null
	 */
	public function getFromId( EntityId $id, $audience = Revision::FOR_PUBLIC ) {
		// TODO: since we already did the trouble of getting a WikiPage here,
		// we probably want to keep a copy of it in the Content object.
		return $this->getWikiPageForId( $id )->getContent( $audience );
	}

	/**
	 * Returns the Title object for the item with provided id.
	 *
	 * @since 0.3
	 *
	 * @param EntityId $id
	 *
	 * @throws MWException
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		return Title::newFromText(
			$this->idFormatter->format( $id ),
			NamespaceUtils::getEntityNamespace( self::$typeMap[$id->getEntityType()] )
		);
	}

	/**
	 * Returns the WikiPage object for the item with provided id.
	 *
	 * @since 0.3
	 *
	 * @param EntityId
	 *
	 * @return WikiPage
	 */
	public function getWikiPageForId( EntityId $id ) {
		return new WikiPage( $this->getTitleForId( $id ) );
	}

	/**
	 * Get the entity content with the provided revision id, or null if there is no such entity content.
	 *
	 * Note that this returns an old content that may not be valid anymore.
	 *
	 * @since 0.2
	 *
	 * @param integer $revisionId
	 *
	 * @return EntityContent|null
	 */
	public function getFromRevision( $revisionId ) {
		$revision = \Revision::newFromId( intval( $revisionId ) );

		if ( $revision === null ) {
			return null;
		}

		return $revision->getContent();
	}

	/**
	 * Constructs a new EntityContent from an Entity.
	 *
	 * @since 0.3
	 *
	 * @param Entity $entity
	 *
	 * @return EntityContent
	 */
	public function newFromEntity( Entity $entity ) {
		/**
		 * @var EntityHandler $handler
		 */
		$handler = \ContentHandler::getForModelID( self::$typeMap[$entity->getType()] );

		return $handler->newContentFromEntity( $entity );
	}

}
