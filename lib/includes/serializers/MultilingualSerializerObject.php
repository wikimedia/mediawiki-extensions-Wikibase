<?php

namespace Wikibase\Lib\Serializers;

/**
 * Multilingual serializer, base serializer for labels and descriptions.
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
 */
abstract class MultilingualSerializerObject extends SerializerObject {

	/**
	 * @see ApiSerializerObject::$options
	 *
	 * @since 0.4
	 *
	 * @var MultiLangSerializationOptions
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param MultiLangSerializationOptions $options
	 */
	public function __construct( MultiLangSerializationOptions $options = null ) {
		if ( $options === null ) {
			$this->options = new MultiLangSerializationOptions();
		}
		parent::__construct( $options );
	}

	/**
	 * Handle multilingual arrays for subclasses.
	 *
	 * @since 0.4
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function serializeMultilingualValues( array $data ) {

		$values = array();
		$idx = 0;

		foreach ( $data as $languageCode => $valueData ) {
			$key = $this->options->shouldUseKeys() ? $languageCode : $idx++;
			if ( is_array( $valueData ) ) {
				$value = $valueData['value'];
				$valueLanguageCode = $valueData['language'];
				$valueSourceLanguageCode = $valueData['source'];
			} else {
				// back-compat
				$value = $valueData;
				$valueLanguageCode = $languageCode;
				$valueSourceLanguageCode = $languageCode;
			}
			$valueKey = ( $value === '' ) ? 'removed' : 'value';
			$values[$key] = array(
				'language' => $valueLanguageCode,
				'source-language' => $valueSourceLanguageCode,
				$valueKey => $value,
			);
		}

		return $values;
	}
}
