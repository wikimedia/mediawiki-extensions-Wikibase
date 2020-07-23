<?php

namespace Wikibase\Lib\Store;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use InvalidArgumentException;
use MWContentSerializationException;
use MWExceptionHandler;
use Serializers\Exceptions\SerializationException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\LegacyIdInterpreter;
use Wikimedia\AtEase\AtEase;

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
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityContentDataCodec {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var Deserializer
	 */
	private $entityDeserializer;

	/**
	 * @var int The maximum size of a blob to allow during serialization/deserialization, in bytes.
	 */
	private $maxBlobSize;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param Serializer $entitySerializer A service capable of serializing EntityDocument objects.
	 * @param Deserializer $entityDeserializer A service capable of deserializing EntityDocument
	 *  objects.
	 * @param int $maxBlobSize The maximum size of a blob to allow during
	 *  serialization/deserialization, in bytes. Set to 0 to disable the check.
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		Serializer $entitySerializer,
		Deserializer $entityDeserializer,
		$maxBlobSize = 0
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entitySerializer = $entitySerializer;
		$this->entityDeserializer = $entityDeserializer;
		$this->maxBlobSize = $maxBlobSize;
	}

	/**
	 * Returns the supported serialization formats as a list of strings.
	 *
	 * @return string[]
	 */
	public function getSupportedFormats() {
		return [
			CONTENT_FORMAT_JSON,
			CONTENT_FORMAT_SERIALIZED,
		];
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
	 * @see EntityHandler::serializeContent()
	 *
	 * @param EntityDocument $entity
	 * @param string|null $format The desired serialization format. One of the CONTENT_FORMAT_...
	 *  constants or null for the default.
	 *
	 * @throws MWContentSerializationException
	 * @throws EntityContentTooBigException
	 * @return string
	 */
	public function encodeEntity( EntityDocument $entity, $format ) {
		try {
			$data = $this->entitySerializer->serialize( $entity );
			$blob = $this->encodeEntityContentData( $data, $format );
		} catch ( SerializationException $ex ) {
			MWExceptionHandler::logException( $ex );
			throw new MWContentSerializationException( $ex->getMessage(), 0, $ex );
		}

		if ( $this->maxBlobSize > 0 && strlen( $blob ) > $this->maxBlobSize ) {
			throw new EntityContentTooBigException();
		}

		return $blob;
	}

	/**
	 * Encodes an EntityRedirect into a blob for storage.
	 *
	 * @see EntityHandler::serializeContent()
	 *
	 * @param EntityRedirect $redirect
	 * @param string|null $format The desired serialization format. One of the CONTENT_FORMAT_...
	 *  constants or null for the default.
	 *
	 * @throws InvalidArgumentException If the format is not supported.
	 * @throws MWContentSerializationException
	 * @return string A blob representing the given Entity.
	 */
	public function encodeRedirect( EntityRedirect $redirect, $format ) {
		// TODO: Use proper Serializer
		$data = [
			'entity' => $redirect->getEntityId()->getSerialization(),
			'redirect' => $redirect->getTargetId()->getSerialization(),
		];

		return $this->encodeEntityContentData( $data, $format );
	}

	/**
	 * Decodes the given blob into an array structure representing an EntityContent
	 * object.
	 *
	 * @param string $blob The data blob to deserialize
	 * @param string|null $format The serialization format of $blob
	 *
	 * @throws InvalidArgumentException If the format is not supported.
	 * @throws MWContentSerializationException
	 * @return array An array representation of an EntityContent object
	 */
	private function decodeEntityContentData( $blob, $format ) {
		if ( !is_string( $blob ) ) {
			throw new InvalidArgumentException( '$blob must be a string' );
		}

		$format = $this->sanitizeFormat( $format );
		AtEase::suppressWarnings();
		switch ( $format ) {
			case CONTENT_FORMAT_JSON:
				$data = json_decode( $blob, true );
				break;
			case CONTENT_FORMAT_SERIALIZED:
				$data = unserialize( $blob );
				break;
			default:
				throw new InvalidArgumentException( "Unsupported decoding format: $format" );
		}
		AtEase::restoreWarnings();

		if ( !is_array( $data ) ) {
			throw new MWContentSerializationException( "Failed to decode as $format" );
		}

		return $data;
	}

	/**
	 * Decodes a blob loaded from storage into an Entity.
	 *
	 * @see EntityHandler::unserializeContent()
	 *
	 * @param string $blob
	 * @param string|null $format The serialization format of the data blob. One of the
	 *  CONTENT_FORMAT_... constants or null for the default.
	 *
	 * @throws InvalidArgumentException If the format is not supported.
	 * @throws MWContentSerializationException
	 * @return EntityDocument|null The entity represented by $blob, or null if $blob represents a
	 *  redirect.
	 */
	public function decodeEntity( $blob, $format ) {
		if ( $this->maxBlobSize > 0 && strlen( $blob ) > $this->maxBlobSize ) {
			throw new MWContentSerializationException( 'Blob too big for deserialization!' );
		}

		$data = $this->decodeEntityContentData( $blob, $format );

		if ( $this->extractEntityId( $data, 'redirect' ) ) {
			// If it's a redirect, return null.
			return null;
		}

		try {
			$entity = $this->entityDeserializer->deserialize( $data );
		} catch ( DeserializationException $ex ) {
			throw new MWContentSerializationException( $ex->getMessage(), 0, $ex );
		}

		if ( !( $entity instanceof EntityDocument ) ) {
			throw new InvalidArgumentException( 'Invalid $entityDeserializer provided' );
		}

		return $entity;
	}

	/**
	 * Decodes a blob loaded from storage into an EntityRedirect.
	 *
	 * @see EntityHandler::unserializeContent()
	 *
	 * @param string $blob
	 * @param string|null $format The serialization format of the data blob. One of the
	 *  CONTENT_FORMAT_... constants or null for the default.
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
			// TODO: Use proper Deserializer
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
	 * @throws MWContentSerializationException
	 * @return EntityId|null The ID of the entity (resp. redirect), or null if
	 *         $key is not set in $data.
	 */
	private function extractEntityId( array $data, $key ) {
		if ( !isset( $data[$key] ) ) {
			return null;
		}

		if ( is_array( $data[$key] ) ) {
			try {
				// Handle the old-style representation of IDs as a two element array. This is only
				// relevant for items and properties and must not support other entity types.
				$stubbedId = $data[$key];
				return LegacyIdInterpreter::newIdFromTypeAndNumber( $stubbedId[0], $stubbedId[1] );
			} catch ( InvalidArgumentException $ex ) {
				throw new MWContentSerializationException( $ex->getMessage(), 0, $ex );
			}
		}

		try {
			return $this->entityIdParser->parse( $data[$key] );
		} catch ( EntityIdParsingException $ex ) {
			throw new MWContentSerializationException( $ex->getMessage(), 0, $ex );
		}
	}

}
