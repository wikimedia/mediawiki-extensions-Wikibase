<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use MWException;
use Wikibase\PropertyValueSnak;
use Wikibase\Snak;
use Wikibase\SnakObject;

/**
 * Serializer for Snak objects.
 *
 * @since 0.2
 *
 * @file
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

		$serialization = array();

		$serialization['snaktype'] = $snak->getType();

		$serialization['property'] = $snak->getPropertyId()->getPrefixedId();

		if ( $snak->getType() === 'value' ) {
			/**
			 * @var PropertyValueSnak $snak
			 */
			$serialization['datavalue'] = $snak->getDataValue()->toArray();

			$serialization['datatype'] = $this->getDataTypeOfSnak( $snak );
		}

		return $serialization;
	}

	protected function getDataTypeOfSnak( PropertyValueSnak $snak ) {
		if ( is_string( $snak->getDataTypeId() ) ) {
			return $snak->getDataTypeId();
		}

		// TODO: return fetch dataType based on prop id
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.4
	 *
	 * @param array $serialization
	 *
	 * @return Snak
	 * @throws InvalidArgumentException
	 */
	public function newFromSerialization( array $serialization ) {
		// TODO: inject id parser
		$constructorArguments = array(
			\Wikibase\EntityId::newFromPrefixedId( $serialization['property'] ),
		);

		if ( array_key_exists( 'datavalue', $serialization ) ) {
			$constructorArguments[] = \DataValues\DataValueFactory::singleton()->newFromArray( $serialization['datavalue'] );
		}

		if ( array_key_exists( 'datatype', $serialization ) ) {
			if ( !is_string( $serialization['datatype'] ) ) {
				throw new InvalidArgumentException( 'The datatype should be a string' );
			}

			$constructorArguments[] = $serialization['datatype'];
		}

		return SnakObject::newFromType( $serialization['snaktype'], $constructorArguments );
	}

}