<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class TermSerializer implements Serializer {

	/**
	 * @param Term $object
	 *
	 * @return string[]
	 */
	public function serialize( $object ) {
		$this->assertIsSerializerFor( $object );
		return $this->getSerialized( $object );
	}

	private function assertIsSerializerFor( $object ) {
		if ( !( $object instanceof Term ) ) {
			throw new UnsupportedObjectException(
				$object,
				'TermSerializer can only serialize Term objects'
			);
		}
	}

	/**
	 * @param Term $term
	 *
	 * @return string[]
	 */
	private function getSerialized( Term $term ) {
		$result = [
			'language' => $term->getLanguageCode(),
			'value' => $term->getText(),
		];

		if ( $term instanceof TermFallback ) {
			$result['language'] = $term->getActualLanguageCode();
			$result['source'] = $term->getSourceLanguageCode();
		}

		return $result;
	}

}
