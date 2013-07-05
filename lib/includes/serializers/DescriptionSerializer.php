<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;

/**
 * Serializer for descriptions.
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
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class DescriptionSerializer extends MultilingualSerializerObject {

	/**
	 * Returns a serialized array of descriptions.
	 *
	 * @since 0.4
	 *
	 * @param array $descriptions
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public final function getSerialized( $descriptions ) {
		if ( !is_array( $descriptions ) ) {
			throw new InvalidArgumentException( 'DescriptionSerializer can only serialize an array of descriptions' );
		}

		$value = $this->serializeMultilingualValues( $descriptions );
		if ( !$this->options->shouldUseKeys() ) {
			$this->setIndexedTagName( $value, 'description' );
		}

		return $value;
	}
}
