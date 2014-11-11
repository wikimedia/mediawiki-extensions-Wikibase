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
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DescriptionSerializer extends SerializerObject implements Unserializer {

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
	 * Returns a serialized array of descriptions for all data given.
	 *
	 * @since 0.4
	 *
	 * @param array $descriptions
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	final public function getSerialized( $descriptions ) {
		if ( !is_array( $descriptions ) ) {
			throw new InvalidArgumentException( 'DescriptionSerializer can only serialize an array of descriptions' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$value = $this->multilingualSerializer->serializeMultilingualValues( $descriptions );

		if ( $this->options->shouldIndexTags() ) {
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
	final public function getSerializedMultilingualValues( $descriptions ) {
		$descriptions = $this->multilingualSerializer->filterPreferredMultilingualValues( $descriptions );
		return $this->getSerialized( $descriptions );
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.4
	 *
	 * @param array $data
	 *
	 * @throws InvalidArgumentException
	 * @return array
	 */
	public function newFromSerialization( array $data ) {
		$descriptions = array();

		foreach( $data as $key => $description ) {
			if ( $key === '_element' ) {
				continue;
			}

			if ( !is_array( $description ) || !array_key_exists( 'language', $description )
				|| !array_key_exists( 'value', $description ) ) {
				throw new InvalidArgumentException( 'Description serialization is invalid.' );
			}

			$lang = $description['language'];
			$descriptions[$lang] = $description['value'];
		}

		return $descriptions;
	}

}
