<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;

/**
 * Serializer for labels.
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
class LabelSerializer extends SerializerObject {

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
	 * Returns a serialized array of labels for all data given.
	 *
	 * @since 0.4
	 *
	 * @param array $labels
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public final function getSerialized( $labels ) {
		if ( !is_array( $labels ) ) {
			throw new InvalidArgumentException( 'LabelSerializer can only serialize an array of labels' );
		}

		$value = $this->multilingualSerializer->serializeMultilingualValues( $labels );

		if ( !$this->options->shouldUseKeys() ) {
			$this->setIndexedTagName( $value, 'label' );
		}

		return $value;
	}

	/**
	 * Returns a serialized array of labels from raw label data array.
	 *
	 * Unlike getSerialized(), $labels is filtered first for requested languages then gets serialized with getSerialized().
	 *
	 * @since 0.4
	 *
	 * @param array $labels
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public final function getSerializedMultilingualValues( $labels ) {
		$labels = $this->multilingualSerializer->filterPreferredMultilingualValues( $labels );
		return $this->getSerialized( $labels );
	}
}
