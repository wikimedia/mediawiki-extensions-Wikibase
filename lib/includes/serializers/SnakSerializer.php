<?php

namespace Wikibase\Lib\Serializers;
use MWException;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Snak;
use Wikibase\SnakObject;

/**
 * Serializer for Snak objects.
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
	 * @param SerializationOptions $options Options, with a PropertyDataTypeLookup set.
	 *
	 * @throws \InvalidArgumentException if no PropertyDataTypeLookup was found set in $options.
	 */
	public function __construct( SerializationOptions $options ) {
		parent::__construct( $options );

		$this->dataTypeLookup = $this->getOptions()->getDataTypeLookup();

		if ( $this->dataTypeLookup === null ) {
			throw new \InvalidArgumentException( 'SerializationOptions $options must have a PropertyDataTypeLookup set!' );
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
	 * @throws MWException
	 */
	public function getSerialized( $snak ) {
		if ( !( $snak instanceof Snak ) ) {
			throw new MWException( 'SnakSerializer can only serialize Snak objects' );
		}

		$serialization = array();

		$serialization['snaktype'] = $snak->getType();

		$serialization['property'] = $snak->getPropertyId()->getPrefixedId();

		// TODO: we might want to include the data type of the property here as well

		if ( $snak->getType() === 'value' ) {
			/**
			 * @var \Wikibase\PropertyValueSnak $snak
			 */
			$serialization['datavalue'] = $snak->getDataValue()->toArray();

			$serialization['datatype'] = $this->dataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
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
			$constructorArguments[] = \DataValues\DataValueFactory::singleton()->newFromArray( $serialization['datavalue'] );
		}

		return SnakObject::newFromType( $serialization['snaktype'], $constructorArguments );
	}

}