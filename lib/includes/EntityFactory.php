<?php

namespace Wikibase;
use MWException;

/**
 * Factory for Entity objects.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityFactory {

	/**
	 * Maps entity types to objects representing the corresponding entity.
	 *
	 * @since 0.2
	 *
	 * @var array
	 */
	protected static $typeMap = array(
		Item::ENTITY_TYPE => '\Wikibase\Item',
		Property::ENTITY_TYPE => '\Wikibase\Property',

		// TODO: Query::ENTITY_TYPE
		'query' => '\Wikibase\Query\QueryEntity'
	);

	/**
	 * @since 0.2
	 *
	 * @return EntityFactory
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Returns the type identifiers of the entities.
	 *
	 * @since 0.2
	 *
	 * @return array all available type identifiers
	 */
	public function getEntityTypes() {
		return array_keys( self::$typeMap );
	}

	/**
	 * Predicate if the provided string is a valid entity type identifier.
	 *
	 * @since 0.2
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function isEntityType( $type ) {
		return array_key_exists( $type, self::$typeMap );
	}

	/**
	 * Creates a new empty entity of the given type.
	 *
	 * @since 0.3
	 *
	 * @param String $entityType The type of the desired new entity.
	 *
	 * @throws MWException if the given entity type is not known.
	 * @return Entity The new Entity object.
	 */
	public function newEmpty( $entityType ) {
		return $this->newFromArray( $entityType, array() );
	}

	/**
	 * Creates a new entity of the desired type, using the given data array
	 * to initialize the entity.
	 *
	 * @param String $entityType The type of the desired new entity.
	 * @param array $data A structure of nested arrays representing the entity.
	 *
	 * @since 0.3
	 *
	 * @throws MWException if the given entity type is not known.
	 * @return Entity The new Entity object.
	 */
	public function newFromArray( $entityType, array $data ) {
		if ( !$this->isEntityType( $entityType ) ) {
			throw new MWException( "Unknown entity type: $entityType" );
		}

		$class = self::$typeMap[ $entityType ];
		$entity = new $class( $data );

		return $entity;
	}

	/**
	 * Creates a new Entity object of the desired type from the given serialized representation.
	 *
	 * @param String $entityType The type of the desired new entity.
	 * @param String $blob The serialized representation of the entity
	 * @param String|null $format The serialization format
	 *
	 * @since 0.3
	 *
	 * @throws MWException if the given entity type or serialization format is not known.
	 * @throws \MWContentSerializationException if the given blob was malformed.
	 *
	 * @return Entity The new Entity object.
	 */
	public function newFromBlob( $entityType, $blob, $format ) {
		$data = $this->unserializedData( $blob, $format );

		return $this->newFromArray( $entityType, $data );
	}

	/**
	 * Decodes the given blob into a structure of nested arrays using the
	 * given serialization format.
	 *
	 * Currently, two formats are supported: CONTENT_FORMAT_SERIALIZED, CONTENT_FORMAT_JSON.
	 *
	 * @param String $blob The data to decode
	 * @param String|null $format The data format (if null, getDefaultFormat()
	 * is used to determine it).
	 *
	 * @return array The deserialized data structure
	 *
	 * @throws MWException if an unsupported format is requested
	 * @throws \MWContentSerializationException If serialization fails.
	 */
	public function unserializedData( $blob, $format = null ) {
		if ( is_null( $format ) ) {
			$format = $this->getDefaultFormat();
		}

		switch ( $format ) {
			case CONTENT_FORMAT_SERIALIZED:
				$data = unserialize( $blob ); //FIXME: suppress notice on failed serialization!
				break;
			case CONTENT_FORMAT_JSON:
				$data = json_decode( $blob, true ); //FIXME: suppress notice on failed serialization!
				break;
			default:
				throw new MWException( "serialization format $format is not supported for Wikibase content model" );
				break;
		}

		if ( $data === false || $data === null ) {
			throw new \MWContentSerializationException( 'failed to deserialize' );
		}

		if ( is_object( $data ) ) {
			// force to array representation (at least on the top level)
			$data = get_object_vars( $data );
		}

		if ( !is_array( $data ) ) {
			throw new \MWContentSerializationException( 'failed to deserialize: not an array.' );
		}

		return $data;
	}

	/**
	 * Returns the default serialization format for entities, as defined by the
	 * 'serializationFormat' setting.
	 *
	 * @return string
	 */
	public function getDefaultFormat() {
		return Settings::get( 'serializationFormat' );
	}

}
