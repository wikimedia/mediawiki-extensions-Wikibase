<?php

namespace Wikibase;
use ApiResult, MWException;

/**
 * Base class for ApiSerializers.
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
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SerializerObject implements Serializer {

	/**
	 * The ApiResult to use during serialization.
	 *
	 * @since 0.3
	 *
	 * @var SerializationOptions
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @since 0.3
	 *
	 * @param ApiResult $apiResult
	 * @param SerializationOptions|null $options
	 */
	public function __construct( SerializationOptions $options = null ) {
		if ( $options === null ) {
			$options = new SerializationOptions();
		}

		$this->options = $options;
	}

	/**
	 * @see ApiSerializer::setOptions
	 *
	 * @since 0.3
	 *
	 * @param SerializationOptions $options
	 */
	public final function setOptions( SerializationOptions $options ) {
		$this->options = $options;
	}

	/**
	 * In case the array contains indexed values (in addition to named),
	 * give all indexed values the given tag name. This function MUST be
	 * called on every array that has numerical indexes.
	 *
	 * @since 0.3
	 *
	 * @param array $array
	 * @param string $tag
	 */
	protected function setIndexedTagName( array &$array, $tag ) {
		if ( $this->options->shouldIndexTags() ) {
			$array['_element'] = $tag;
		}
	}

	/**
	 * @see ApiSerializer::getOptions
	 *
	 * @since 0.3
	 *
	 * @return SerializationOptions
	 */
	public final function getOptions() {
		return $this->options;
	}

}
