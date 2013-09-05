<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;

/**
 * Serializer for descriptions.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.4
 *
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
			$this->multilingualSerializer = $multilingualSerializer;
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

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

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
