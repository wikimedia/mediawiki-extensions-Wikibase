<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermTypes;

/**
 * A PrefetchingTermLookup providing dummy TermLookup functionality, i.e. always returning a fake label/description,
 * and optional aliases, and also a Spy on TermBuffer
 * i.e. provides access to "prefetched" terms stored in the buffer after prefetchTerms method is called.
 *
 * @license GPL-2.0-or-later
 */
class FakePrefetchingTermLookup implements PrefetchingTermLookup {

	/** @var (string|string[])[][] */
	private $buffer;

	/**
	 * @param array $entityIds
	 * @param array $termTypes
	 * @param array $languageCodes
	 */
	public function prefetchTerms( array $entityIds, array $termTypes, array $languageCodes ) {
		$this->bufferFakeTermsForEntities( $entityIds, $termTypes, $languageCodes );
	}

	private function bufferFakeTermsForEntities( array $entityIds, array $termTypes, array $languageCodes ) {
		foreach ( $entityIds as $id ) {
			foreach ( $termTypes as $type ) {
				foreach ( $languageCodes as $lang ) {
					if ( $type !== TermTypes::TYPE_ALIAS ) {
						$this->bufferNonAliasTerm( $id, $type, $lang );
					} else {
						$this->bufferAliasTerms( $id, $type, $lang );
					}
				}
			}
		}
	}

	private function bufferNonAliasTerm( EntityId $id, string $type, string $lang ) {
		$this->buffer[$id->getSerialization()][$type][$lang] = $this->generateFakeTerm( $id, $type, $lang );
	}

	private function bufferAliasTerms( EntityId $id, string $type, string $lang ) {
		$this->buffer[$id->getSerialization()][$type][$lang] = [];
		$this->buffer[$id->getSerialization()][$type][$lang][] = $this->generateFakeTerm( $id, $type, $lang, 1 );
		$this->buffer[$id->getSerialization()][$type][$lang][] = $this->generateFakeTerm( $id, $type, $lang, 2 );
	}

	/**
	 * @param EntityId $id
	 * @param string $type
	 * @param string $lang
	 * @param int $count Used for aliases
	 * @return string
	 */
	private function generateFakeTerm( EntityId $id, $type, $lang, $count = 0 ) {
		$suffix = $count ? ' ' . $count : '';

		return $id->getSerialization() . ' ' . $lang . ' ' . $type . $suffix;
	}

	public function getPrefetchedTerms(): array {
		$terms = [];

		foreach ( $this->buffer as $entityTerms ) {
			foreach ( $entityTerms as $termsByLang ) {
				foreach ( $termsByLang as $term ) {
					$terms[] = $term;
				}
			}
		}

		return $terms;
	}

	/** @inheritDoc */
	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$id = $entityId->getSerialization();
		return $this->buffer[$id][$termType][$languageCode] ?? null;
	}

	/** @inheritDoc */
	public function getLabel( EntityId $entityId, $languageCode ) {
		return $this->generateFakeTerm( $entityId, TermTypes::TYPE_LABEL, $languageCode );
	}

	/** @inheritDoc */
	public function getLabels( EntityId $entityId, array $languageCodes ) {
		$labels = [];

		foreach ( $languageCodes as $lang ) {
			$labels[$lang] = $this->generateFakeTerm( $entityId, TermTypes::TYPE_LABEL, $lang );
		}
		return $labels;
	}

	/** @inheritDoc */
	public function getDescription( EntityId $entityId, $languageCode ) {
		return $this->generateFakeTerm( $entityId, TermTypes::TYPE_DESCRIPTION, $languageCode );
	}

	/** @inheritDoc */
	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		$descriptions = [];

		foreach ( $languageCodes as $lang ) {
			$descriptions[$lang] = $this->generateFakeTerm( $entityId, TermTypes::TYPE_DESCRIPTION, $lang );
		}
		return $descriptions;
	}

	/** @inheritDoc */
	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		$id = $entityId->getSerialization();
		if ( array_key_exists( $id, $this->buffer ) ) {
			if ( array_key_exists( TermTypes::TYPE_ALIAS, $this->buffer[$id] ) ) {
				if ( array_key_exists( $languageCode, $this->buffer[$id][TermTypes::TYPE_ALIAS] ) ) {
					return $this->buffer[$id][TermTypes::TYPE_ALIAS][$languageCode];
				}
			}
		}

		return [];
	}

}
