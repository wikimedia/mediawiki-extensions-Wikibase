<?php

namespace Wikibase;

/**
 * Updates the store to reflect the update to an entity.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface EntityUpdateHandler {

	/**
	 * Handles an update to an entity.
	 *
	 * @since 0.1
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function handleUpdate( Entity $entity );

}