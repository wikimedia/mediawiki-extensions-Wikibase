<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupListSerializer implements Serializer {

	/**
	 * @var Serializer
	 */
	private $aliasGroupSerializer;

	/**
	 * @var bool
	 */
	private $useObjectsForMaps;

	/**
	 * @param Serializer $aliasGroupSerializer
	 * @param bool $useObjectsForMaps
	 */
	public function __construct( Serializer $aliasGroupSerializer, $useObjectsForMaps ) {
		$this->aliasGroupSerializer = $aliasGroupSerializer;
		$this->useObjectsForMaps = $useObjectsForMaps;
	}

	/**
	 * @param AliasGroupList $object
	 *
	 * @return array[]
	 */
	public function serialize( $object ) {
		$this->assertIsSerializerFor( $object );
		return $this->getSerialized( $object );
	}

	private function assertIsSerializerFor( $object ) {
		if ( !( $object instanceof AliasGroupList ) ) {
			throw new UnsupportedObjectException(
				$object,
				'AliasGroupListSerializer can only serialize AliasGroupList objects'
			);
		}
	}

	/**
	 * @param AliasGroupList $aliasGroupList
	 *
	 * @return array[]
	 */
	private function getSerialized( AliasGroupList $aliasGroupList ) {
		$serialization = [];

		foreach ( $aliasGroupList->getIterator() as $aliasGroup ) {
			$serialization[$aliasGroup->getLanguageCode()] =
				$this->aliasGroupSerializer->serialize( $aliasGroup );
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}

		return $serialization;
	}

}
