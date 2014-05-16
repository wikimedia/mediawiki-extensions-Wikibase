<?php

namespace Wikibase\Lib\Store;

use InvalidArgumentException;
use MWContentSerializationException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Internal\LegacyIdInterpreter;
use Wikibase\EntityFactory;

/**
 * A codec for use by EntityContent resp EntityHandler subclasses for the
 * serialization and deserialization of EntityContent objects.
 *
 * This class only deals with the representation of EntityContent as an
 * array structure, not with EntityContent objects. It is needed to allow
 * client side code to deserialize entity content data without the need
 * to depend on EntityContent objects, which are only available on the
 * repo.
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityContentDataCodec {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityFactory $entityFactory
	 */
	function __construct( EntityIdParser $entityIdParser, EntityFactory $entityFactory ) {
		$this->entityIdParser = $entityIdParser;
		$this->entityFactory = $entityFactory;
	}

	/**
	 * Returns the supported serialization formats as a list of strings.
	 *
	 * @return string[]
	 */
	public function getSupportedFormats() {
		return array(
			CONTENT_FORMAT_JSON,
			CONTENT_FORMAT_SERIALIZED,
		);
	}

	/**
	 * @return string CONTENT_FORMAT_JSON
	 */
	public function getDefaultFormat() {
		// Just hard-code this: there's no good reason to use anything else,
		// and changing the default serialization format would break a wiki's database.
		return CONTENT_FORMAT_JSON;
	}

	/**
	 * Returns a sanitized version of $format.
	 *
	 * @param string|null $format The requested format. If null, getDefaultFormat() will
	 * be consulted.
	 *
	 * @return string The format to actually use.
	 */
	private function sanitizeFormat( $format ) {
		return $format === null ? $this->getDefaultFormat() : $format;
	}

	/**
	 * Encodes the given array structure as a blob using the given serialization format.
	 *
	 * @param array $data A nested data array representing (part of) an EntityContent object.
	 * @param string|null $format The desired serialization format.
	 *
	 * @throws InvalidArgumentException If the format is not supported.
	 * @throws MWContentSerializationException If the array could not be encoded.
	 * @return string the blob
	 */
	private function encodeEntityContentData( array $data, $format ) {
		switch ( $this->sanitizeFormat( $format ) ) {
			case CONTENT_FORMAT_JSON:
				$blob = json_encode( $data );
				break;
			case CONTENT_FORMAT_SERIALIZED:
				$blob = serialize( $data );
				break;
			default:
				throw new InvalidArgumentException( "Unsupported encoding format: $format" );
		}

		if ( !is_string( $blob ) ) {
			throw new MWContentSerializationException( "Failed to encode as $format" );
		}

		return $blob;
	}

	/**
	 * Encodes an Entity into a blob for storage.
	 *
	 * @see Entity::toArray()
	 * @see EntityHandler::serializeContent()
	 *
	 * @param Entity $entity
	 * @param string|null $format The desired serialization format.
	 *
	 * @throws InvalidArgumentException If the format is not supported.
	 * @throws MWContentSerializationException If the array could not be encoded.
	 * @return string A blob representing the given Entity.
	 */
	public function encodeEntity( Entity $entity, $format ) {
		$data = $entity->toArray();
		return $this->encodeEntityContentData( $data, $format );
	}

	/**
	 * Encodes an EntityRedirect into a blob for storage.
	 *
	 * @see Entity::toArray()
	 * @see EntityHandler::serializeContent()
	 *
	 * @param EntityRedirect $redirect
	 * @param string|null $format The desired serialization format.
	 *
	 * @throws InvalidArgumentException If the format is not supported.
	 * @throws MWContentSerializationException If the array could not be encoded.
	 * @return string A blob representing the given Entity.
	 */
	public function encodeRedirect( EntityRedirect $redirect, $format ) {
		$data = array(
			'entity' => $redirect->getEntityId()->getSerialization(),
			'redirect' => $redirect->getTargetId()->getSerialization(),
		);

		return $this->encodeEntityContentData( $data, $format );
	}

	/**
	 * Decodes the given blob into an array structure representing an EntityContent
	 * object.
	 *
	 * @see EntityHandler::unserializeContent
	 *
	 * @param string $blob The data blob to deserialize
	 * @param string|null $format The serialization format of $blob
	 *
	 * @throws InvalidArgumentException If the format is not supported.
	 * @throws MWContentSerializationException If the blob could not be decoded.
	 * @return array An array representation of an EntityContent object
	 */
	private function decodeEntityContentData( $blob, $format ) {
		if ( !is_string( $blob ) ) {
			throw new InvalidArgumentException( '$blob must be a string' );
		}

		wfSuppressWarnings();
		switch ( $this->sanitizeFormat( $format ) ) {
			case CONTENT_FORMAT_JSON:
				$data = json_decode( $blob, true );
				break;
			case CONTENT_FORMAT_SERIALIZED:
				$data = unserialize( $blob );
				break;
			default:
				throw new InvalidArgumentException( "Unsupported decoding format: $format" );
		}
		wfRestoreWarnings();

		if ( !is_array( $data ) ) {
			throw new MWContentSerializationException( "Failed to decode as $format" );
		}

		return $data;
	}

	/**
	 * Decodes a blob loaded from storage into an Entity.
	 *
	 * @see Entity::toArray()
	 * @see EntityHandler::serializeContent()
	 *
	 * @param string $blob
	 * @param string|null $format The serialization format of $blob.
	 *
	 * @throws InvalidArgumentException If the format is not supported.
	 * @throws MWContentSerializationException If the array could not be decoded.
	 * @return Entity|null The Entity represented by $blob, or null if $blob represents a redirect.
	 */
	public function decodeEntity( $blob, $format ) {
		$data = $this->decodeEntityContentData( $blob, $format );

		if ( $this->extractEntityId( $data, 'redirect' ) ) {
			// If it's a redirect, return null.
			return null;
		}

		$entityId = $this->extractEntityId( $data, 'entity' );

		if ( !$entityId ) {
			throw new MWContentSerializationException( 'No entity ID found in serialization data!' );
		}

		$entity = $this->entityFactory->newFromArray( $entityId->getEntityType(), $data );
		return $entity;
	}

	/**
	 * Decodes a blob loaded from storage into an EntityRedirect.
	 *
	 * @see Entity::toArray()
	 * @see EntityHandler::serializeContent()
	 *
	 * @param string $blob
	 * @param string|null $format The serialization format of $blob.
	 *
	 * @throws InvalidArgumentException If the format is not supported.
	 * @throws MWContentSerializationException If the array could not be decoded.
	 * @return EntityRedirect|null The EntityRedirect represented by $blob,
	 *         or null if $blob does not represent a redirect.
	 */
	public function decodeRedirect( $blob, $format ) {
		$data = $this->decodeEntityContentData( $blob, $format );

		$targetId = $this->extractEntityId( $data, 'redirect' );

		if ( !$targetId ) {
			// If it's not a redirect, return null.
			return null;
		}

		$entityId = $this->extractEntityId( $data, 'entity' );

		if ( !$entityId ) {
			throw new MWContentSerializationException( 'No entity ID found in serialization data!' );
		}

		try {
			$redirect = new EntityRedirect( $entityId, $targetId );
			return $redirect;
		} catch ( InvalidArgumentException $ex ) {
			throw new MWContentSerializationException( $ex->getMessage(), 0, $ex );
		}
	}

	/**
	 * @param array $data An array representation of an EntityContent object.
	 * @param string $key The key in $data that contains the serialized ID.
	 *
	 * @return EntityId|null The ID of the entity (resp. redirect), or null if
	 *         $key is not set in $data.
	 */
	private function extractEntityId( array $data, $key ) {
		if ( !isset( $data[$key] ) ) {
			return null;
		}

		if ( is_array( $data[$key] ) ) {
			// Handle the old-style representation of IDs as a two element array.
			$stubbedId = $data[$key];
			return LegacyIdInterpreter::newIdFromTypeAndNumber( $stubbedId[0], $stubbedId[1] );
		}

		return $this->entityIdParser->parse( $data[$key] );
	}

}
