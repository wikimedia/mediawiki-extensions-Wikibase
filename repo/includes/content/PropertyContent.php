<?php

namespace Wikibase;

use IContextSource;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;

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
	 * @since 0.1
	 * @var Property
	 */
	protected $property;

	/**
	 * Do not use to construct new stuff from outside of this class,
	 * use the static newFoobar methods.
	 *
	 * In other words: treat as protected (which it was, but now
	 * cannot be since we derive from Content).
	 *
	 * @protected
	 *
	 * @since 0.1
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
		return new static( new Property( $data ) );
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
		return new static( Property::newFromType( 'string' ) );
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
	 * Instantiates an EntityView.
	 *
	 * @see getEntityView()
	 *
	 * @param IContextSource $context
	 * @param SnakFormatter $snakFormatter
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdParser $idParser
	 * @param SerializationOptions $options
	 *
	 * @return EntityView
	 */
	protected function newEntityView(
		IContextSource $context,
		SnakFormatter $snakFormatter,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilder $entityInfoBuilder,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $idParser,
		SerializationOptions $options
	) {
		$configBuilder = new ParserOutputJsConfigBuilder(
			$entityInfoBuilder,
			$idParser,
			$entityTitleLookup,
			new ReferencedEntitiesFinder(),
			$context->getLanguage()->getCode()
		);

		return new PropertyView(
			$context,
			$snakFormatter,
			$dataTypeLookup,
			$entityInfoBuilder,
			$entityTitleLookup,
			$options,
			$configBuilder
		);
	}
}
