<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @since 1.5
 *
 * @author Adam Shorland
 */
class TermListSerializer implements Serializer {

	/**
	 * @var Serializer
	 */
	private $termSerializer;

	/**
	 * @var bool
	 */
	private $useObjectsForMaps;

	/**
	 * @param Serializer $termSerializer
	 * @param bool $useObjectsForMaps
	 */
	public function __construct( Serializer $termSerializer, $useObjectsForMaps ) {
		$this->termSerializer = $termSerializer;
		$this->useObjectsForMaps = $useObjectsForMaps;
	}

	/**
	 * @param TermList $object
	 *
	 * @return array
	 */
	public function serialize( $object ) {
		$this->assertIsSerializerFor( $object );
		return $this->getSerialized( $object );
	}

	private function assertIsSerializerFor( $object ) {
		if ( !( $object instanceof TermList ) ) {
			throw new UnsupportedObjectException(
				$object,
				'TermListSerializer can only serialize TermList objects'
			);
		}
	}

	/**
	 * @param TermList $termList
	 *
	 * @return array
	 */
	private function getSerialized( TermList $termList ) {
		$serialization = array();

		foreach ( $termList->getIterator() as $term ) {
			/** @var Term $term */
			$serialization[$term->getLanguageCode()] = $this->termSerializer->serialize( $term );
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}

		return $serialization;
	}

}
