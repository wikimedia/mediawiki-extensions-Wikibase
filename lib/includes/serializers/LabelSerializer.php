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
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LabelSerializer extends SerializerObject implements Unserializer {

	/**
	 * @var MultilingualSerializer
	 */
	private $multilingualSerializer;

	/**
	 * @since 0.4
	 *
	 * @param SerializationOptions $options
	 * @param MultilingualSerializer $multilingualSerializer
	 */
	public function __construct(
		SerializationOptions $options = null,
		MultilingualSerializer $multilingualSerializer = null
	) {
		parent::__construct( $options );

		if ( $multilingualSerializer === null ) {
			$this->multilingualSerializer = new MultilingualSerializer( $options );
		} else {
			$this->multilingualSerializer = $multilingualSerializer;
		}
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
	final public function getSerialized( $labels ) {
		if ( !is_array( $labels ) ) {
			throw new InvalidArgumentException( 'LabelSerializer can only serialize an array of labels' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$value = $this->multilingualSerializer->serializeMultilingualValues( $labels );

		if ( $this->options->shouldIndexTags() ) {
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
	final public function getSerializedMultilingualValues( $labels ) {
		$labels = $this->multilingualSerializer->filterPreferredMultilingualValues( $labels );
		return $this->getSerialized( $labels );
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.5
	 *
	 * @param array $data
	 *
	 * @throws InvalidArgumentException
	 * @return array $labels
	 */
	public function newFromSerialization( array $data ) {
		$labels = array();

		foreach( $data as $key => $label ) {
			if ( $key === '_element' ) {
				continue;
			}

			if ( is_array( $label ) && array_key_exists( 'language', $label )
				&& array_key_exists( 'value', $label )
			) {
				$lang = $label['language'];
				$labels[$lang] = $label['value'];
			} else {
				throw new InvalidArgumentException( 'label serialization is invalid' );
			}
		}

		return $labels;
	}
}
