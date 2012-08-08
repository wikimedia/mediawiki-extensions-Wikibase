<?php

namespace Wikibase;
use ORMRow;

/**
 * Class representing a single entry in the entity cache table.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CachedEntity extends ORMRow {

	/**
	 * @since 0.1
	 *
	 * @return Entity
	 */
	public function getEntity() {
		return $this->getField( 'entity_data' );
	}

}