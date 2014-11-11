<?php

namespace Wikibase\Lib\Serializers;

/**
 * Unserializer for Traversable objects
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ListUnserializer implements Unserializer {

	/**
	 * @var Unserializer
	 */
	private $elementUnserializer;

	/**
	 * @param Unserializer $elementUnserializer
	 */
	public function __construct( Unserializer $elementUnserializer ) {
		$this->elementUnserializer = $elementUnserializer;
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.5
	 *
	 * @param array $serialization
	 *
	 * @return array
	 */
	public function newFromSerialization( array $serialization ) {
		$elements = array();

		foreach ( $serialization as $serializedElement ) {
			$element = $this->elementUnserializer->newFromSerialization( $serializedElement );
			$elements[] = $element;
		}

		return $elements;
	}

}
