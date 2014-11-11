<?php

namespace Wikibase\Lib\Serializers;

/**
 * Base class for ApiSerializers.
 *
 * TODO: change to PDO like options as done in ValueParsers
 *
 * @since 0.3
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
	 * @since 0.3
	 *
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

	/**
	 * @since 0.5
	 * @param array $arr
	 * @return bool Is the array an
	 */
	protected final function isAssociative( $arr ) {
		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}

}
