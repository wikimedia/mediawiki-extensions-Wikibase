<?php

namespace Wikibase\Test\DataModel\Fixtures;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityOfUnknownType implements EntityDocument {

	public function getId() {
		return null;
	}

	public function getType() {
		return 'unknown-entity-type';
	}

}