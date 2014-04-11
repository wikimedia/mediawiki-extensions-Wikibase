<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyFingerprintDeserializer implements Deserializer {

	private $serialization;

	/**
	 * @param mixed $serialization
	 *
	 * @return Item
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Term serialization should be an array' );
		}

		$this->serialization = $serialization;

		try {
			return new Fingerprint( $this->getLabels(), $this->getDescriptions(), $this->getAliases() );
		}
		catch ( InvalidArgumentException $ex ) {
			throw new DeserializationException(
				'Could not deserialize fingerprint: ' . $ex->getMessage(),
				$ex
			);
		}
	}

	private function getLabels() {
		$labels = array();

		foreach ( $this->getArrayFromKey( 'label' ) as $langCode => $text ) {
			$labels[] = new Term( $langCode, $text );
		}

		return new TermList( $labels );
	}

	private function getDescriptions() {
		$descriptions = array();

		foreach ( $this->getArrayFromKey( 'description' ) as $langCode => $text ) {
			$descriptions[] = new Term( $langCode, $text );
		}

		return new TermList( $descriptions );
	}

	private function getAliases() {
		$descriptions = array();

		foreach ( $this->getArrayFromKey( 'aliases' ) as $langCode => $texts ) {
			if ( $texts !== array() ) {
				$descriptions[] = new AliasGroup( $langCode, $texts );
			}
		}

		return new AliasGroupList( $descriptions );
	}

	private function getArrayFromKey( $key ) {
		if ( !array_key_exists( $key, $this->serialization ) ) {
			return array();
		}

		$this->assertKeyIsArray( $key );

		return $this->serialization[$key];
	}

	private function assertKeyIsArray( $key ) {
		if ( !is_array( $this->serialization[$key] ) ) {
			throw new InvalidAttributeException(
				$key,
				$this->serialization[$key],
				'The ' . $key . ' key should point to an array'
			);
		}
	}

}
