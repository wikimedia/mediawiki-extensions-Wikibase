<?php

namespace Wikibase\Lib\Serializers;

use DataValues\DataValueFactory;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyNotFoundException;
use Wikibase\PropertyValueSnak;
use Wikibase\Snak;
use Wikibase\SnakObject;

/**
 * Serializer for Snak objects.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SnakSerializer extends SerializerObject implements Unserializer {

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $dataTypeLookup;

	/**
	 * @param PropertyDataTypeLookup $dataTypeLookup A lookup service for determining the data type
	 *        of PropertyValueSnaks. If not set, the OPT_DATA_TYPE_LOOKUP option will be checked
	 *        for a PropertyDataTypeLookup.
	 *
	 * @param SerializationOptions $options Options. @see SnakSerializer::OPT_DATA_TYPE_LOOKUP.
	 *
	 * @todo: require $dataTypeLookup
	 *
	 * @throws \InvalidArgumentException if no PropertyDataTypeLookup was found set in $options.
	 */
	public function __construct( PropertyDataTypeLookup $dataTypeLookup = null, SerializationOptions $options = null ) {
		parent::__construct( $options );

		$this->dataTypeLookup = $dataTypeLookup;
	}

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param mixed $snak
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getSerialized( $snak ) {
		if ( !( $snak instanceof Snak ) ) {
			throw new InvalidArgumentException( 'SnakSerializer can only serialize Snak objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		if( $this->options->hasOption( SerializationOptions::OPT_SERIALIZE_SNAKS_WITH_HASH )
			&& $this->options->getOption( SerializationOptions::OPT_SERIALIZE_SNAKS_WITH_HASH )
			&& method_exists( $snak, 'getHash' )
		) {
			$serialization['hash'] = $snak->getHash();
		}

		$serialization['snaktype'] = $snak->getType();

		$serialization['property'] = $snak->getPropertyId()->getPrefixedId();

		if ( $snak instanceof PropertyValueSnak ) {
			if ( $this->dataTypeLookup !== null ) {
				$propertyId = $snak->getPropertyId();
				try {
					$serialization['datatype'] = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
				} catch ( PropertyNotFoundException $ex ) {
					wfDebugLog( __CLASS__, __FUNCTION__ . ': Property not found: ' . $propertyId->getSerialization() );
					//XXX: shall we set $serialization['datatype'] = 'bad' ??
				}
			}

			$serialization['datavalue'] = $snak->getDataValue()->toArray();
		}

		return $serialization;
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.4
	 *
	 * @param array $serialization
	 *
	 * @return Snak
	 */
	public function newFromSerialization( array $serialization ) {
		$propertyId = new PropertyId( $serialization['property'] );

		if ( !$propertyId ) {
			throw new InvalidArgumentException( "Invalid property ID: " . $serialization['property'] );
		}

		$constructorArguments = array(
			$propertyId,
		);

		if ( array_key_exists( 'datavalue', $serialization ) ) {
			$constructorArguments[] = DataValueFactory::singleton()->newFromArray( $serialization['datavalue'] );
		}

		return SnakObject::newFromType( $serialization['snaktype'], $constructorArguments );
	}

}
