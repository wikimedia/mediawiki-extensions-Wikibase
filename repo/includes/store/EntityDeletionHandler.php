<?php

namespace Wikibase;

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
