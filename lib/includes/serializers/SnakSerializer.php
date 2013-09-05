<?php

namespace Wikibase\Lib\Serializers;
use MWException;
use Wikibase\Snak;
use Wikibase\SnakObject;

/**
 * Serializer for Snak objects.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.2
 *
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakSerializer extends SerializerObject implements Unserializer {

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

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		$serialization['snaktype'] = $snak->getType();

		$serialization['property'] = $snak->getPropertyId()->getPrefixedId();

		// TODO: we might want to include the data type of the property here as well

		if ( $snak->getType() === 'value' ) {
			/**
			 * @var \Wikibase\PropertyValueSnak $snak
			 */
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
			$constructorArguments[] = \DataValues\DataValueFactory::singleton()->newFromArray( $serialization['datavalue'] );
		}

		return SnakObject::newFromType( $serialization['snaktype'], $constructorArguments );
	}

}