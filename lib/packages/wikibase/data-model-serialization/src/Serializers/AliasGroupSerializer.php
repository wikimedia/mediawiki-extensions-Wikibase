<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupFallback;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupSerializer implements Serializer {

	/**
	 * @param AliasGroup $object
	 *
	 * @return array[]
	 */
	public function serialize( $object ) {
		$this->assertIsSerializerFor( $object );
		return $this->getSerialized( $object );
	}

	private function assertIsSerializerFor( $object ) {
		if ( !( $object instanceof AliasGroup ) ) {
			throw new UnsupportedObjectException(
				$object,
				'AliasGroupSerializer can only serialize AliasGroup objects'
			);
		}
	}

	/**
	 * @param AliasGroup $aliasGroup
	 *
	 * @return array[]
	 */
	private function getSerialized( AliasGroup $aliasGroup ) {
		$serialization = [];
		$language = $aliasGroup->getLanguageCode();

		foreach ( $aliasGroup->getAliases() as $value ) {
			$result = [
				'language' => $language,
				'value' => $value,
			];

			if ( $aliasGroup instanceof AliasGroupFallback ) {
				$result['language'] = $aliasGroup->getActualLanguageCode();
				$result['source'] = $aliasGroup->getSourceLanguageCode();
			}

			$serialization[] = $result;
		}

		return $serialization;
	}

}
