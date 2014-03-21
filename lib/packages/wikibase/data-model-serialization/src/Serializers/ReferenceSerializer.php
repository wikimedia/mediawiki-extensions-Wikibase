<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class ReferenceSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	protected $snaksSerializer;

	/**
	 * @param Serializer $snaksSerializer
	 */
	public function __construct( Serializer $snaksSerializer ) {
		$this->snaksSerializer = $snaksSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	public function isSerializerFor( $object ) {
		return is_object( $object ) && $object instanceof Reference;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param mixed $object
	 *
	 * @return array
	 * @throws SerializationException
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'ReferenceSerializer can only serialize References objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( Reference $reference ) {
		return array(
			'hash' => $reference->getHash(),
			'snaks' => $this->snaksSerializer->serialize( $reference->getSnaks() ),
			'snaks-order' => $this->buildSnaksOrderList( $reference->getSnaks() )
		);
	}

	private function buildSnaksOrderList( SnakList $snaks ) {
		$list = array();

		foreach ( $snaks as $snak ) {
			$id = $snak->getPropertyId()->getSerialization();
			if ( !in_array( $id, $list ) ) {
				$list[] = $id;
			}
		}

		return $list;
	}
}
