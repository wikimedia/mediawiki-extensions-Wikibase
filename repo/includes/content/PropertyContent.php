<?php

namespace Wikibase;

use Content;

/**
 * Content object for articles representing Wikibase properties.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContent extends EntityContent {

	/**
	 * @var Property
	 */
	private $property;

	/**
	 * Do not use to construct new stuff from outside of this class,
	 * use the static newFoobar methods.
	 *
	 * In other words: treat as protected (which it was, but now
	 * cannot be since we derive from Content).
	 *
	 * @protected
	 *
	 * @param Property $property
	 */
	public function __construct( Property $property ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_PROPERTY );
		$this->property = $property;
	}

	/**
	 * Create a new propertyContent object for the provided property.
	 *
	 * @param Property $property
	 *
	 * @return PropertyContent
	 */
	public static function newFromProperty( Property $property ) {
		return new static( $property );
	}

	/**
	 * Gets the property that makes up this property content.
	 *
	 * @return Property
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * Sets the property that makes up this property content.
	 *
	 * @param Property $property
	 */
	public function setProperty( Property $property ) {
		$this->property = $property;
	}

	/**
	 * Returns a new empty PropertyContent.
	 *
	 * @return PropertyContent
	 */
	public static function newEmpty() {
		return new static( Property::newFromType( 'string' ) );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @return Property
	 */
	public function getEntity() {
		return $this->property;
	}

	/**
	 * Checks if this PropertyContent is valid for saving.
	 *
	 * Returns false if the entity does not have a DataType set.
	 *
	 * @see Content::isValid()
	 */
	public function isValid() {
		if ( !parent::isValid() ) {
			return false;
		}

		if ( is_null( $this->getEntity()->getDataTypeId() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @see getEntityViewClass
	 *
	 * @return string
	 */
	protected function getEntityViewClass() {
		return 'Wikibase\PropertyView';
	}

}
