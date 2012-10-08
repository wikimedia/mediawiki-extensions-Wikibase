<?php

namespace Wikibase;

/**
 * Class representing an "instance of" snak.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#InstanceOfSnak
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InstanceOfSnak extends SnakObject {

	/**
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $itemId;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param integer $itemId
	 */
	public function __construct( $itemId ) {
		$this->itemId = $itemId;
	}

	/**
	 * Returns the item id of the class.
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getClassItemId() {
		return $this->itemId;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->itemId );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$this->itemId = unserialize( $serialized );
	}

	/**
	 * @see Snak::getPropertyId
	 *
	 * @since 0.2
	 *
	 * @return integer
	 */
	public function getPropertyId() {
		return -1;
	}

	/**
	 * @see Snak::getType
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getType() {
		return 'instance';
	}

}
