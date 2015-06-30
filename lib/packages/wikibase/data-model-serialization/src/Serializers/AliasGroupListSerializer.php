<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * Package private
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupListSerializer implements Serializer {

	/**
	 * @var bool
	 */
	private $useObjectsForMaps;

	/**
	 * @param bool $useObjectsForMaps
	 */
	public function __construct( $useObjectsForMaps ) {
		$this->useObjectsForMaps = $useObjectsForMaps;
	}

	/**
	 * @param AliasGroupList $object
	 *
	 * @return array
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
	 * @return array
	 */
	private function getSerialized( AliasGroupList $aliasGroupList ) {
		$serialization = array();

		foreach ( $aliasGroupList->getIterator() as $aliasGroup ) {
			$serialization[$aliasGroup->getLanguageCode()] = $this->serializeAliasGroup( $aliasGroup );
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}

		return $serialization;
	}

	/**
	 * @param AliasGroup $aliasGroup
	 *
	 * @return array
	 */
	private function serializeAliasGroup( AliasGroup $aliasGroup ) {
		$serialization = array();
		$language = $aliasGroup->getLanguageCode();

		foreach ( $aliasGroup->getAliases() as $value ) {
			$result = array(
				'language' => $language,
				'value' => $value
			);

			if ( $aliasGroup instanceof AliasGroupFallback ) {
				$result['language'] = $aliasGroup->getActualLanguageCode();
				$result['source'] = $aliasGroup->getSourceLanguageCode();
			}

			$serialization[] = $result;
		}

		return $serialization;
	}

}
