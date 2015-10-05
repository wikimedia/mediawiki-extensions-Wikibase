<?php

namespace Wikibase\DataModel\Services\Fixtures;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityOfUnknownType implements EntityDocument {

	/**
	 * @return null
	 */
	public function getId() {
		return null;
	}

	public function getType() {
		return 'unknown-entity-type';
	}

	/**
	 * @param mixed $id Ignored.
	 */
	public function setId( $id ) {
	}

	/**
	 * @return bool Always true.
	 */
	public function isEmpty() {
		return true;
	}

}
