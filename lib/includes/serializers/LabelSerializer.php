<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;

/**
 * Serializer for labels.
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
			$this->multilingualSerializer = $multilingualSerializer;
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

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

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
