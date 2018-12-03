<?php

namespace Wikibase\DataModel\Services\Fixtures;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FakeEntityDocument implements EntityDocument {

	/**
	 * @var EntityId|null
	 */
	private $id;

	public function __construct( EntityId $id = null ) {
		$this->id = $id;
	}

	/**
	 * @return EntityId|null
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
	 * @return self
	 */
	public function copy() {
		return new self( $this->id );
	}

	/**
	 * @see EntityDocument::clear
	 */
	public function clear() {
	}

}
