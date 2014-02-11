<?php

namespace Wikibase;

use MWException;
use Title;

/**
 * Represents a mapping from entity IDs to wiki page titles.
 * The mapping could be programmatic, or it could be based on database lookups.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityTitleLookup {

	/**
	 * Returns the Title for the given entity.
	 *
	 * If the entity does not exist, this method will return either null,
	 * or a Title object referring to a page that does not exist.
	 *
	 * @since 0.4
	 *
	 * @param EntityId $id
	 *
	 * @throws MWException
	 * @return Title|null
	 */
	public function getTitleForId( EntityId $id );

	/**
	 * Determines what namespace is suitable for the given type of entities.
	 *
	 * @since 0.5
	 *
	 * @param string $type the entity type to look up, as returned by Entity::getType()
	 *
	 * @return int the namespace ID for this type
	 */
	public function getNamespaceForType( $type );

}
