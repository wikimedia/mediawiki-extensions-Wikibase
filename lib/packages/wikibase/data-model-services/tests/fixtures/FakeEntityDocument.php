<?php

namespace Wikibase\DataModel\Services\Fixtures;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0+
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
	 * @return string Returns the entity type of the provided EntityId.
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

	/**
	 * @see EntityDocument::equals
	 *
	 * @since 3.3
	 *
	 * @param mixed $target
	 *
	 * @return bool Always true.
	 */
	public function equals( $target ) {
		return true;
	}

	/**
	 * @see EntityDocument::copy
	 *
	 * @since 3.3
	 *
	 * @return self
	 */
	public function copy() {
		return new self( $this->id );
	}

}
