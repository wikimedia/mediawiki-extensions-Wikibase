<?php

namespace Wikibase;

/**
 * Content object for articles representing Wikibase properties.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup Content
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContent extends EntityContent {

	/**
	 * @since 0.1
	 * @var Property
	 */
	protected $property;

	/**
	 * Constructor.
	 * Do not use to construct new stuff from outside of this class, use the static newFoobar methods.
	 * In other words: treat as protected (which it was, but now cannot be since we derive from Content).
	 * @protected
	 *
	 * @since 0.1
	 *
	 * @param Property $property
	 */
	public function __construct( Property $property ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_ITEM );

		$this->property = $property;
	}

	/**
	 * Create a new propertyContent object for the provided property.
	 *
	 * @since 0.1
	 *
	 * @param Property $property
	 *
	 * @return PropertyContent
	 */
	public static function newFromProperty( Property $property ) {
		return new static( $property );
	}

	/**
	 * Create a new PropertyContent object from the provided Property data.
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return PropertyContent
	 */
	public static function newFromArray( array $data ) {
		return new static( new PropertyObject( $data ) );
	}

	/**
	 * Gets the property that makes up this property content.
	 *
	 * @since 0.1
	 *
	 * @return Property
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * Sets the property that makes up this property content.
	 *
	 * @since 0.1
	 *
	 * @param Property $property
	 */
	public function setProperty( Property $property ) {
		$this->property = $property;
	}

	/**
	 * Returns a new empty PropertyContent.
	 *
	 * @since 0.1
	 *
	 * @return PropertyContent
	 */
	public static function newEmpty() {
		return new static( PropertyObject::newEmpty() );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @since 0.1
	 *
	 * @return Property
	 */
	public function getEntity() {
		return $this->property;
	}

}
