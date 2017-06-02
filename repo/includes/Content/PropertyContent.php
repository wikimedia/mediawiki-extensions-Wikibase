<?php

namespace Wikibase;

use InvalidArgumentException;
use LogicException;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\Property;

/**
 * Content object for articles representing Wikibase properties.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyContent extends EntityContent {

	/**
	 * @var EntityHolder|null
	 */
	private $propertyHolder;

	/**
	 * Do not use to construct new stuff from outside of this class,
	 * use the static newFoobar methods.
	 *
	 * In other words: treat as protected (which it was, but now
	 * cannot be since we derive from Content).
	 *
	 * @protected
	 *
	 * @param EntityHolder|null $propertyHolder
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityHolder $propertyHolder = null ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_PROPERTY );

		if ( $propertyHolder !== null
			&& $propertyHolder->getEntityType() !== Property::ENTITY_TYPE
		) {
			throw new InvalidArgumentException( '$propertyHolder must contain a Property entity!' );
		}

		$this->propertyHolder = $propertyHolder;
	}

	/**
	 * Create a new propertyContent object for the provided property.
	 *
	 * @param Property $property
	 *
	 * @return self
	 */
	public static function newFromProperty( Property $property ) {
		return new static( new EntityInstanceHolder( $property ) );
	}

	/**
	 * @throws LogicException if the content object is empty and does not contain an entity.
	 * @return Property
	 */
	public function getProperty() {
		if ( !$this->propertyHolder ) {
			throw new LogicException( 'This content object is empty' );
		}

		return $this->propertyHolder->getEntity( Property::class );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @throws LogicException if the content object is empty and does not contain an entity.
	 * @return Property
	 */
	public function getEntity() {
		return $this->getProperty();
	}

	/**
	 * @see EntityContent::getEntityHolder
	 *
	 * @return EntityHolder|null
	 */
	public function getEntityHolder() {
		return $this->propertyHolder;
	}

	/**
	 * @see EntityContent::getEntityPageProperties
	 *
	 * Records the number of statements in the 'wb-claims' key.
	 *
	 * @return array A map from property names to property values.
	 */
	public function getEntityPageProperties() {
		$properties = parent::getEntityPageProperties();

		$properties['wb-claims'] = $this->getProperty()->getStatements()->count();

		return $properties;
	}

	/**
	 * Checks if this PropertyContent is valid for saving.
	 *
	 * Returns false if the entity does not have a DataType set.
	 *
	 * @see Content::isValid()
	 */
	public function isValid() {
		//TODO: provide a way to get the data type from the holder directly!
		return parent::isValid() && $this->getProperty()->getDataTypeId() !== '';
	}

	/**
	 * @see EntityContent::isCountable
	 *
	 * @param bool|null $hasLinks
	 *
	 * @return bool True if this is not a redirect and the property is not empty.
	 */
	public function isCountable( $hasLinks = null ) {
		return !$this->isRedirect() && !$this->getProperty()->isEmpty();
	}

	/**
	 * @see EntityContent::isEmpty
	 *
	 * @return bool True if this is not a redirect and the property is empty.
	 */
	public function isEmpty() {
		return !$this->isRedirect() && $this->getProperty()->isEmpty();
	}

}
