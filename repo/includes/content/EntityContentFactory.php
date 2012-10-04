<?php

namespace Wikibase;
use MWException, Title, WikiPage;

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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityContentFactory {

	// TODO: move to sane place
	protected static $typeMap = array(
		Item::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_ITEM,
		Property::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_PROPERTY,
		Query::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_QUERY,
	);

	/**
	 * @since 0.2
	 *
	 * @return EntityContentFactory
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
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
	 * Configured via $wgWBSettings['entityNamespaces'].
	 *
	 * @since 0.2
	 *
	 * @return array An array of string content model IDs.
	 */
	public function getEntityContentModels() {
		$namespaces = Settings::get( 'entityNamespaces' );
		return is_array( $namespaces ) ? array_keys( $namespaces ) : array();
	}

	/**
	 * Returns the Title object for the item with provided id.
	 *
	 * @since 0.2
	 *
	 * @param string $entityType
	 * @param integer $entityId
	 *
	 * @throws MWException
	 * @return Title
	 */
	public function getTitleForId( $entityType, $entityId ) {
		$id = intval( $entityId );

		if ( $id <= 0 ) {
			throw new MWException( 'entityId must be a positive integer, not ' . var_export( $entityId , true ) );
		}

		return Title::newFromText(
			EntityFactory::singleton()->getPrefixedId( $entityType, $entityId ),
			Utils::getEntityNamespace( self::$typeMap[$entityType] )
		);
	}

	/**
	 * Returns the WikiPage object for the item with provided id.
	 *
	 * @since 0.2
	 *
	 * @param string $entityType
	 * @param integer $entityId
	 *
	 * @return WikiPage
	 */
	public function getWikiPageForId( $entityType, $entityId ) {
		return new WikiPage( $this->getTitleForId( $entityType, $entityId ) );
	}

	/**
	 * Get the entity content for the entity with the provided id
	 * if it's available to the specified audience.
	 * If the specified audience does not have the ability to view this
	 * revision, if there is no such item, null will be returned.
	 *
	 * @since 0.2
	 *
	 * @param string $entityType
	 * @param integer $entityId
	 *
	 * @param $audience Integer: one of:
	 *      Revision::FOR_PUBLIC       to be displayed to all users
	 *      Revision::FOR_THIS_USER    to be displayed to $wgUser
	 *      Revision::RAW              get the text regardless of permissions
	 *
	 * @return EntityContent|null
	 */
	public function getFromId( $entityType, $entityId, $audience = \Revision::FOR_PUBLIC ) {
		// TODO: since we already did the trouble of getting a WikiPage here,
		// we probably want to keep a copy of it in the Content object.
		return $this->getWikiPageForId( $entityType, $entityId )->getContent( $audience );
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
	 * @return array of EntityContent
	 */
	public function getFromLabel( $language, $label, $description = null, $entityType = null, $fuzzySearch = false ) {
		$entityIds = StoreFactory::getStore()->newTermCache()->getEntityIdsForLabel( $label, $language, $description, $entityType, $fuzzySearch );
		$entities = array();

		foreach ( $entityIds as $entityId ) {
			list( $type, $id ) = $entityId;
			$entity = self::getFromId( $type, $id );

			if ( $entity !== null ) {
				$entities[] = $entity;
			}
		}

		return $entities;
	}

}
