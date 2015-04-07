<?php

namespace Wikibase\Lib\Serializers;

/**
 * Multilingual serializer, for serializer of labels and descriptions.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 */
class MultilingualSerializer {

	/**
	 * @var SerializationOptions
	 */
	private $options;

	/**
	 * @since 0.4
	 *
	 * @param SerializationOptions $options
	 */
	public function __construct( SerializationOptions $options = null ) {
		if ( $options === null ) {
			$options = new SerializationOptions();
		}

		$this->options = $options;
	}

	/**
	 * Handle multilingual arrays.
	 *
	 * @since 0.4
	 *
	 * @param string[]|array[] $data
	 *
	 * @return array[]
	 */
	public function serializeMultilingualValues( array $data ) {
		$values = array();
		$idx = 0;

		foreach ( $data as $languageCode => $valueData ) {
			$key = $this->options->shouldIndexTags() ? $idx++ : $languageCode;
			if ( is_array( $valueData ) ) {
				$value = $valueData['value'];
				$valueLanguageCode = $valueData['language'];
				$valueSourceLanguageCode = $valueData['source'];
			} else {
				// back-compat
				$value = $valueData;
				$valueLanguageCode = $languageCode;
				$valueSourceLanguageCode = null;
			}
			$valueKey = ( $value === '' ) ? 'removed' : 'value';
			$values[$key] = array(
				'language' => $valueLanguageCode,
				$valueKey => $value,
			);
			if ( $valueSourceLanguageCode !== null ) {
				$values[$key]['source-language'] = $valueSourceLanguageCode;
			}
			if ( $this->options->shouldIndexTags() && $languageCode !== $valueLanguageCode ) {
				// To have $languageCode kept somewhere
				$values[$key]['for-language'] = $languageCode;
			}
		}

		return $values;
	}

	/**
	 * Used in (Label|Description)Serializer::getSerializedMultilingualValues(), to filter multilingual labels and descriptions
	 *
	 * @param array $allData
	 *
	 * @return array[]
	 */
	public function filterPreferredMultilingualValues( array $allData ) {
		$languageFallbackChains = $this->options->getLanguageFallbackChains();

		if ( $languageFallbackChains === null || empty( $allData ) ) {
			return $allData;
		}

		$values = array();

		foreach ( $languageFallbackChains as $languageCode => $languageFallbackChain ) {
			$data = $languageFallbackChain->extractPreferredValue( $allData );
			if ( $data !== null ) {
				$values[$languageCode] = $data;
			}
		}

		return $values;
	}

}
