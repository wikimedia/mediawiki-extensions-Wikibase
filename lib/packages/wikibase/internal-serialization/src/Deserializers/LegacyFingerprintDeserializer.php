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
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LegacyFingerprintDeserializer implements Deserializer {

	/**
	 * @param array $serialization
	 *
	 * @return Item
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Term serialization should be an array' );
		}

		try {
			return new Fingerprint(
				$this->getLabels( $serialization ),
				$this->getDescriptions( $serialization ),
				$this->getAliases( $serialization )
			);
		} catch ( InvalidArgumentException $ex ) {
			throw new DeserializationException(
				'Could not deserialize fingerprint: ' . $ex->getMessage(),
				$ex
			);
		}
	}

	private function getLabels( array $serialization ) {
		$labels = [];

		foreach ( $this->getArrayFromKey( 'label', $serialization ) as $langCode => $text ) {
			$labels[] = new Term( $langCode, $text );
		}

		return new TermList( $labels );
	}

	private function getDescriptions( array $serialization ) {
		$descriptions = [];

		foreach ( $this->getArrayFromKey( 'description', $serialization ) as $langCode => $text ) {
			$descriptions[] = new Term( $langCode, $text );
		}

		return new TermList( $descriptions );
	}

	private function getAliases( array $serialization ) {
		$descriptions = [];

		foreach ( $this->getArrayFromKey( 'aliases', $serialization ) as $langCode => $texts ) {
			if ( $texts !== [] ) {
				$descriptions[] = new AliasGroup( $langCode, $texts );
			}
		}

		return new AliasGroupList( $descriptions );
	}

	private function getArrayFromKey( $key, array $serialization ) {
		if ( !array_key_exists( $key, $serialization ) ) {
			return [];
		}

		if ( !is_array( $serialization[$key] ) ) {
			throw new InvalidAttributeException(
				$key,
				$serialization[$key],
				'The ' . $key . ' key should point to an array'
			);
		}

		return $serialization[$key];
	}

}
