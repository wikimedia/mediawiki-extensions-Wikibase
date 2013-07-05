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
class DescriptionSerializer extends SerializerObject {

	/**
	 * @see ApiSerializerObject::$options
	 *
	 * @since 0.4
	 *
	 * @var MultiLangSerializationOptions
	 */
	protected $options;

	/**
	 * @var MultilingualSerializer
	 */
	protected $multilingualSerializer;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param MultiLangSerializationOptions $options
	 */
	public function __construct( MultiLangSerializationOptions $options = null,
		MultilingualSerializer $multilingualSerializer = null
	) {
		if ( $options === null ) {
			$this->options = new MultiLangSerializationOptions();
		}
		if ( $multilingualSerializer === null ) {
			$this->multilingualSerializer = new MultilingualSerializer( $options );
		} else {
			$this->multilingualSerializer = $multilingualSerlializer;
		}
		parent::__construct( $options );
	}

	/**
	 * Returns a serialized array of descriptions for all data given.
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

		$value = $this->multilingualSerializer->serializeMultilingualValues( $descriptions );

		if ( !$this->options->shouldUseKeys() ) {
			$this->setIndexedTagName( $value, 'description' );
		}

		return $value;
	}

	/**
	 * Returns a serialized array of descriptions from raw description data array.
	 *
	 * Unlike getSerialized(), $descriptions is filtered first for requested languages then gets serialized with getSerialized().
	 *
	 * @since 0.4
	 *
	 * @param array $descriptions
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public final function getSerializedMultilingualValues( $descriptions ) {
		$descriptions = $this->multilingualSerializer->filterPreferredMultilingualValues( $descriptions );
		return $this->getSerialized( $descriptions );
	}
}
