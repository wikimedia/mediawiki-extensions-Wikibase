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
class AliasGroupListSerializer extends MapSerializer implements Serializer {

	/**
	 * @var AliasGroupSerializer
	 */
	private $aliasGroupSerializer;

	/**
	 * @param Serializer $aliasGroupSerializer
	 */
	public function __construct( Serializer $aliasGroupSerializer, bool $useObjectsForEmptyMaps ) {
		parent::__construct( $useObjectsForEmptyMaps );
		$this->aliasGroupSerializer = $aliasGroupSerializer;
	}

	/**
	 * @param AliasGroupList $object
	 * @return array|::stdClass
	 */
	public function serialize( $object ) {
		$this->assertIsSerializerFor( $object );
		return $this->serializeMap( $this->generateSerializedArrayRepresentation( $object ) );
	}

	private function assertIsSerializerFor( $object ) {
		if ( !( $object instanceof AliasGroupList ) ) {
			throw new UnsupportedObjectException(
				$object,
				'AliasGroupListSerializer can only serialize AliasGroupList objects'
			);
		}
	}

	protected function generateSerializedArrayRepresentation( AliasGroupList $dataToSerialize ): array {
		$serialization = [];

		foreach ( $dataToSerialize->getIterator() as $aliasGroup ) {
			$serialization[$aliasGroup->getLanguageCode()] =
				$this->aliasGroupSerializer->serialize( $aliasGroup );
		}

		return $serialization;
	}
}
