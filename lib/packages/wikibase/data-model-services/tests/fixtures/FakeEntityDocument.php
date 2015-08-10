<?php

namespace Wikibase\DataModel\Services\Fixtures;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FakeEntityDocument implements EntityDocument {

	private $id;

	public function __construct( EntityId $id ) {
		$this->id = $id;
	}

	public function getId() {
		return $this->id;
	}

	public function getType() {
		return $this->id->getEntityType();
	}

	public function setId( $id ) {
		$this->id = $id;
	}

}
