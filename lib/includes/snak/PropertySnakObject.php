<?php

namespace Wikibase;

/**
 * Base class for property snaks.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class PropertySnakObject extends SnakObject implements PropertySnak {

	/**
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $propertyId;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param integer $propertyId
	 */
	public function __construct( $propertyId ) {
		$this->propertyId = $propertyId;
	}

	/**
	 * @see PropertySnak::getPropertyId
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getPropertyId() {
		return $this->propertyId;
	}

	/**
	 * @see PropertySnak::setPropertyId
	 *
	 * @since 0.1
	 *
	 * @param integer $propertyId
	 */
	public function setPropertyId( $propertyId ) {
		if ( $propertyId !== $this->propertyId ) {
			$this->propertyId = $propertyId;
			$this->getSubscriptionHandler()->notifySubscribers();
		}
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( $this->propertyId );
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $serialized
	 */
	public function unserialize( $serialized ) {
		$this->propertyId = unserialize( $serialized );
	}

}