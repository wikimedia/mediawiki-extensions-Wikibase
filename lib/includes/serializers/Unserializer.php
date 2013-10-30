<?php

namespace Wikibase\Lib\Serializers;

/**
 * Interface for service objects doing unserialization.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Unserializer {

	/**
	 * Constructs the original object from the provided serialization.
	 *
	 * @since 0.4
	 *
	 * @param array $serialization
	 *
	 * @return mixed
	 */
	public function newFromSerialization( array $serialization );

}
