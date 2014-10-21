<?php

namespace Wikibase\Lib\Serializers;

use DataValues\DataValueFactory;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\PropertyNotFoundException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

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
		$propertyId = $snak->getPropertyId();

		if( $this->options->hasOption( SerializationOptions::OPT_SERIALIZE_SNAKS_WITH_HASH )
			&& $this->options->getOption( SerializationOptions::OPT_SERIALIZE_SNAKS_WITH_HASH )
			&& method_exists( $snak, 'getHash' )
		) {
			$serialization['hash'] = $snak->getHash();
		}

		$serialization['snaktype'] = $snak->getType();
		$serialization['property'] = $propertyId->getSerialization();

		if ( $snak instanceof PropertyValueSnak ) {
			if ( $this->dataTypeLookup !== null ) {
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
	 * @throws InvalidArgumentException
	 * @return Snak
	 */
	public function newFromSerialization( array $serialization ) {
		if ( !isset( $serialization['property'] ) ) {
			throw new InvalidArgumentException( "No property id given" );
		}

		$propertyId = new PropertyId( $serialization['property'] );

		if ( !$propertyId ) {
			throw new InvalidArgumentException( "Invalid property ID: " . $serialization['property'] );
		}

		$constructorArguments = array(
			$propertyId,
		);

		if ( array_key_exists( 'datavalue', $serialization ) ) {
			$constructorArguments[] = DataValueFactory::singleton()->tryNewFromArray( $serialization['datavalue'] );
		}

		return $this->newSnakFromType( $serialization['snaktype'], $constructorArguments );
	}

	private function newSnakFromType( $snakType, array $constructorArguments ) {
		if ( $constructorArguments === array() || ( $snakType === 'value' ) && count( $constructorArguments ) < 2 ) {
			throw new InvalidArgumentException( __METHOD__ . ' got an array with to few constructor arguments' );
		}

		$snakJar = array(
			'value' => 'Wikibase\DataModel\Snak\PropertyValueSnak',
			'novalue' => 'Wikibase\DataModel\Snak\PropertyNoValueSnak',
			'somevalue' => 'Wikibase\DataModel\Snak\PropertySomeValueSnak',
		);

		if ( !array_key_exists( $snakType, $snakJar ) ) {
			throw new InvalidArgumentException( 'Cannot construct a snak from array with unknown snak type "' . $snakType . '"' );
		}

		$snakClass = $snakJar[$snakType];

		if ( $snakType === 'value' ) {
			return new $snakClass(
				$constructorArguments[0],
				$constructorArguments[1]
			);
		}
		else {
			return new $snakClass( $constructorArguments[0] );
		}
	}

}
