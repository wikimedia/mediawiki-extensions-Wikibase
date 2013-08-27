<?php

namespace Wikibase;
use MWException;

/**
 * Codec for nested array structured.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ArrayStructureCodec {

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
	 * @param String $format The data format (if null, getDefaultFormat()
	 * is used to determine it).
	 *
	 * @return array The deserialized data structure
	 *
	 * @throws MWException if an unsupported format is requested
	 * @throws \MWContentSerializationException If serialization fails.
	 */
	public function unserializeData( $blob, $format = null ) {
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
