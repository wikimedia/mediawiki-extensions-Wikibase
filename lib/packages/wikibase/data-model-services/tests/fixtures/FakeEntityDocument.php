<?php

namespace Wikibase\DataModel\Services\Fixtures;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FakeEntityDocument implements EntityDocument {

	/**
	 * @var EntityId
	 */
	private $id;

	/**
	 * @param EntityId $id
	 */
	public function __construct( EntityId $id ) {
		$this->id = $id;
	}

	/**
	 * @return EntityId
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->id->getEntityType();
	}

	/**
	 * @param EntityId $id
	 */
	public function setId( $id ) {
		$this->id = $id;
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		return true;
	}

}
