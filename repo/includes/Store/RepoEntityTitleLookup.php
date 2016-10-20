<?php

namespace Wikibase\Repo\Store;

use OutOfBoundsException;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * A Title lookup for use in a repository context, assuming that the resulting Title objects
 * represent entity pages, e.g. the Property P1 will be resolved to the "Property" namespace and the
 * page "Property:P1".
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
interface RepoEntityTitleLookup extends EntityTitleLookup {

	/**
	 * Determines what namespace is suitable for the given type of entities.
	 *
	 * @since 0.5
	 *
	 * @param string $entityType the entity type to look up, as returned by Entity::getType()
	 *
	 * @throws OutOfBoundsException
	 * @return int the namespace ID for this type
	 */
	public function getNamespaceForType( $entityType );

}
