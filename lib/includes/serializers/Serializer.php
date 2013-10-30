<?php

namespace Wikibase\Lib\Serializers;

/**
 * Interface for objects that can transform variables of a certain type into an array
 * of primitive value or nested arrays. This output can then be fed to a serialization
 * function such as json_encode() or serialize(). The format used is suitable for
 * exposure to the outside world, so can be used in APIs, be put into pages as
 * JSON blobs to be used by widgets or by an exporter. The formats are not optimized
 * for conciseness and can contain a lot of redundant info, and are thus often not
 * ideal for serialization for internal storage.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Serializer {

	/**
	 * Turns the provided object to API output and returns this serialization.
	 *
	 * @since 0.3
	 *
	 * @param mixed $object
	 *
	 * @return array
	 */
	public function getSerialized( $object );

	/**
	 * Sets the options to use during serialization.
	 *
	 * TODO: use PDO like options system as done in ValueParsers
	 *
	 * @since 0.3
	 *
	 * @param SerializationOptions $options
	 */
	public function setOptions( SerializationOptions $options );

	/**
	 * Returns the ApiResult to use during serialization.
	 * Modification of options via this getter is allowed.
	 *
	 * @since 0.3
	 *
	 * @return SerializationOptions
	 */
	public function getOptions();

}

