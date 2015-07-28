<?php

namespace Wikibase\Client;

use RuntimeException;
use Serializers\Serializer;

/**
 * Serializer to be used as a stand-in when no serialization is supported.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ForbiddenSerializer implements Serializer {

	/**
	 * @var string
	 */
	private $message;

	public function __construct( $message ) {
		$this->message = $message;
	}

	/**
	 * Always throws an exception.
	 *
	 * @see Serializer::getSerialized()
	 *
	 * @param mixed $object
	 *
	 * @throws RuntimeException Always.
	 * @return array
	 */
	public function serialize( $object ) {
		throw new RuntimeException( $this->message );
	}

}
