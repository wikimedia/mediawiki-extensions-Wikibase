<?php

namespace Wikibase;
use MWException;

/**
 * Codec for nested array structures.
 *
 * This is a helper object that may be used by ContentHandler implementations
 * to implement serialization.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @todo //FIXME: provide a test case!
 */
class ArrayStructureCodec {

	/**
	 * @return string[] a list of format identifiers as defined by the CONTENT_FORMAT_XXX constants.
	 */
	public static function getSupportedFormats() {
		return array(
			CONTENT_FORMAT_JSON,
			CONTENT_FORMAT_SERIALIZED
		);
	}

	/**
	 * Encodes the given array structure into a blog using the
	 * given serialization format.
	 *
	 * Currently, two formats are supported: CONTENT_FORMAT_SERIALIZED, CONTENT_FORMAT_JSON.
	 *
	 * @param array  $data   The data to serialize.
	 * @param string $format The desired serialization format. Currently,
	 *                       CONTENT_FORMAT_SERIALIZED and CONTENT_FORMAT_JSON are supported.
	 *
	 * @throws \MWException
	 * @return string
	 */
	public function serializeData( array $data, $format ) {

		switch ( $format ) {
			case CONTENT_FORMAT_SERIALIZED:
				$blob = serialize( $data );
				break;
			case CONTENT_FORMAT_JSON:
				$blob = json_encode( $data );
				break;
			default:
				throw new \MWException( "Serialization format $format is not supported!" );
		}

		return $blob;

	}

	/**
	 * Decodes the given blob into a structure of nested arrays using the
	 * given serialization format.
	 *
	 * Currently, two formats are supported: CONTENT_FORMAT_SERIALIZED, CONTENT_FORMAT_JSON.
	 *
	 * @param String $blob The data to decode
	 * @param String $format The data format
	 *
	 * @return array The deserialized data structure
	 *
	 * @throws MWException if an unsupported format is requested
	 * @throws \MWContentSerializationException If serialization fails.
	 */
	public function unserializeData( $blob, $format ) {
		wfSuppressWarnings();
		switch ( $format ) {
			case CONTENT_FORMAT_SERIALIZED:
				$data = unserialize( $blob );
				break;
			case CONTENT_FORMAT_JSON:
				$data = json_decode( $blob, true );
				break;
			default:
				throw new \MWException( "Serialization format $format is not supported!" );
				break;
		}
		wfRestoreWarnings();

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

}
