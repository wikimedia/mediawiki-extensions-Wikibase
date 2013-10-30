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
		} else {
			$this->options = $options;
		}
	}

	/**
	 * Handle multilingual arrays.
	 *
	 * @since 0.4
	 *
	 * @param array $data
	 *
	 * @return array
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
	 * @return array
	 */
	public function filterPreferredMultilingualValues( array $allData ) {
		$values = array();

		$languageFallbackChains = $this->options->getLanguageFallbackChains();

		if ( $languageFallbackChains ) {
			foreach ( $languageFallbackChains as $languageCode => $languageFallbackChain ) {
				$data = $languageFallbackChain->extractPreferredValue( $allData );
				if ( $data !== null ) {
					$values[$languageCode] = $data;
				}
			}
		} else {
			$values = $allData;
		}

		return $values;
	}
}
