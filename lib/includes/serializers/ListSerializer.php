<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use Traversable;

/**
 * Serializer for Traversable objects
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ListSerializer extends SerializerObject {

	/**
	 * @var Serializer
	 */
	private $elementSerializer;

	/**
	 * @param string $elementName
	 * @param Serializer $elementSerializer
	 * @param SerializationOptions|null $options
	 */
	public function __construct(
		$elementName,
		Serializer $elementSerializer,
		SerializationOptions $options = null
	) {
		parent::__construct( $options );

		$this->elementName = $elementName;
		$this->elementSerializer = $elementSerializer;
	}

	/**
	 * @since 0.5
	 *
	 * @param mixed $objects
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getSerialized( $objects ) {
		if ( !( $objects instanceof Traversable ) ) {
			throw new InvalidArgumentException( 'ListSerializer can only serialize Traversable objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		foreach( $objects as $object ){
			$serializedObject = $this->elementSerializer->getSerialized( $object );
			$serialization[] = $serializedObject;
		}

		$this->setIndexedTagName( $serialization, $this->elementName );

		return $serialization;
	}

}
