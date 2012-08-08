<?php

namespace Wikibase;

/**
 * Represents the deletion of an entity.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeletion extends EntityCreation {

	/**
	 * @see Change::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return $this->getEntity()->getType() . '-remove';
	}

}