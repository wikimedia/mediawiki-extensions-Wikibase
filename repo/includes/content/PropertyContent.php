<?php

namespace Wikibase;

use InvalidArgumentException;
use Language;
use Wikibase\Content\EntityHolder;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\FingerprintView;

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
	 * @var EntityHolder
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
	 * @param EntityHolder $propertyHolder
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityHolder $propertyHolder ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_PROPERTY );

		if ( $propertyHolder->getEntityType() !== Property::ENTITY_TYPE ) {
			throw new InvalidArgumentException( '$propertyHolder must contain a Property entity!' );
		}

		$this->propertyHolder = $propertyHolder;
	}

	/**
	 * Create a new propertyContent object for the provided property.
	 *
	 * @param Property $property
	 *
	 * @return PropertyContent
	 */
	public static function newFromProperty( Property $property ) {
		return new static( new EntityInstanceHolder( $property ) );
	}

	/**
	 * Gets the property that makes up this property content.
	 *
	 * @return Property
	 */
	public function getProperty() {
		return $this->propertyHolder->getEntity( 'Wikibase\DataModel\Entity\Property' );
	}

	/**
	 * Returns a new empty PropertyContent.
	 *
	 * @return PropertyContent
	 */
	public static function newEmpty() {
		return new static( new EntityInstanceHolder( Property::newFromType( 'string' ) ) );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @return Property
	 */
	public function getEntity() {
		return $this->getProperty();
	}

	/**
	 * @see EntityContent::getEntityHolder
	 *
	 * @return EntityHolder
	 */
	public function getEntityHolder() {
		return $this->propertyHolder;
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

		//TODO: provide a way to get the data type from the holder directly!
		if ( is_null( $this->getEntity()->getDataTypeId() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @see getEntityView()
	 *
	 * @param FingerprintView $fingerprintView
	 * @param ClaimsView $claimsView
	 * @param Language $language
	 *
	 * @return PropertyView
	 */
	protected function newEntityView(
		FingerprintView $fingerprintView,
		ClaimsView $claimsView,
		Language $language
	) {
		return new PropertyView( $fingerprintView, $claimsView, $language );
	}

}
