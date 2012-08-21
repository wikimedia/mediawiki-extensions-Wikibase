<?php

namespace Wikibase;

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