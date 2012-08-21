<?php

namespace Wikibase;

/**
 * Updates the store to reflect the deletion of an entity.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface EntityDeletionHandler {

	/**
	 * Handles deletion of an entity.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function handleDeletion( Entity $entity );

}
