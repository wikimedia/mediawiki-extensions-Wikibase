<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use DataValues\DataValueFactory;
use MWException;
use Wikibase\Lib\PropertyDataTypeLookup;
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
	 * @const Options key for a PropertyDataTypeLookup service.
	 * If provided, the lookup is used to include the property's
	 * data type in the serialization of PropertyValueSnaks.
	 */
	const OPT_DATA_TYPE_LOOKUP = 'DataTypeLookup';

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $dataTypeLookup;

	/**
	 * @param SerializationOptions $options Options. @see SnakSerializer::OPT_DATA_TYPE_LOOKUP.
	 * @param PropertyDataTypeLookup $dataTypeLookup A lookup service for determining the data type
	 *        of PropertyValueSnaks. If not set, the OPT_DATA_TYPE_LOOKUP option will be checked
	 *        for a PropertyDataTypeLookup.
	 *
	 * @throws \InvalidArgumentException if no PropertyDataTypeLookup was found set in $options.
	 */
	public function __construct( SerializationOptions $options = null, PropertyDataTypeLookup $dataTypeLookup = null ) {
		parent::__construct( $options );

		if ( $dataTypeLookup === null ) {
			$dataTypeLookup = $this->getOptions()->getOption( self::OPT_DATA_TYPE_LOOKUP );
		}

		$this->dataTypeLookup = $dataTypeLookup;

		if ( $this->dataTypeLookup === null ) {
			//TODO: make this use wfDebugLog
			wfWarn( __CLASS__ . '::' . __FUNCTION__ . ': No data type lookup service provided, serialization will not include data types!' );
		}
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

		$serialization['snaktype'] = $snak->getType();

		$serialization['property'] = $snak->getPropertyId()->getPrefixedId();

		if ( $snak instanceof PropertyValueSnak ) {
			if ( $this->dataTypeLookup !== null ) {
				$serialization['datatype'] = $this->dataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
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
		// TODO: inject id parser
		$constructorArguments = array(
			\Wikibase\EntityId::newFromPrefixedId( $serialization['property'] ),
		);

		if ( array_key_exists( 'datavalue', $serialization ) ) {
			$constructorArguments[] = DataValueFactory::singleton()->newFromArray( $serialization['datavalue'] );
		}

		return SnakObject::newFromType( $serialization['snaktype'], $constructorArguments );
	}

}
