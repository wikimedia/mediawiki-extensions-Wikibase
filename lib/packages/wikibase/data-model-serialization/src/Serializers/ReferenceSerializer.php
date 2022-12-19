<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class ReferenceSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $snaksSerializer;

	public function __construct( Serializer $snaksSerializer ) {
		$this->snaksSerializer = $snaksSerializer;
	}

	/**
	 * @see Serializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof Reference;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param Reference $object
	 *
	 * @throws SerializationException
	 * @return array
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'ReferenceSerializer can only serialize Reference objects'
			);
		}

		return $this->getSerialized( $object );
	}

	private function getSerialized( Reference $reference ) {
		return [
			'hash' => $reference->getHash(),
			'snaks' => $this->snaksSerializer->serialize( $reference->getSnaks() ),
			'snaks-order' => $this->buildSnakListOrderList( $reference->getSnaks() ),
		];
	}

	private function buildSnakListOrderList( SnakList $snaks ) {
		$list = [];

		/**
		 * @var Snak $snak
		 */
		foreach ( $snaks as $snak ) {
			$id = $snak->getPropertyId()->getSerialization();
			if ( !in_array( $id, $list ) ) {
				$list[] = $id;
			}
		}

		return $list;
	}

}
