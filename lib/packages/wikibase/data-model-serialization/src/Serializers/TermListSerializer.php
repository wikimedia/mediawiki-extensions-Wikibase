<?php

namespace Wikibase\DataModel\Serializers;

use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private
 *
 * @license GPL-2.0-or-later
 * @author Addshore
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
	 * @see Serializer::serialize
	 *
	 * @param TermList $object
	 *
	 * @return array[]
	 * @throws SerializationException
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
	 * @return array[]
	 */
	private function getSerialized( TermList $termList ) {
		$serialization = [];

		foreach ( $termList->getIterator() as $term ) {
			$serialization[$term->getLanguageCode()] = $this->termSerializer->serialize( $term );
		}

		if ( $this->useObjectsForMaps ) {
			$serialization = (object)$serialization;
		}

		return $serialization;
	}

}
