<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\PrefetchingTermLookup;

/**
 * A PrefetchingTermLookup providing dummy TermLookup functionality, i.e. always returning a fake label/description,
 * and also a Spy on TermBuffer, i.e. provides access to "prefetched" terms stored in the buffer after prefetchTerms
 * method is called.
 *
 * @license GPL-2.0-or-later
 */
class FakePrefetchingTermLookup implements PrefetchingTermLookup {

	private $buffer;

	public function prefetchTerms( array $entityIds, array $termTypes = null, array $languageCodes = null ) {
		if ( $termTypes === null ) {
			$termTypes = [ 'label', 'description' ];
		}
		if ( $languageCodes === null ) {
			$languageCodes = [ 'de', 'en' ];
		}
		foreach ( $entityIds as $id ) {
			foreach ( $termTypes as $type ) {
				foreach ( $languageCodes as $lang ) {
					$this->buffer[$id->getSerialization()][$type][$lang] = $this->generateFakeTerm( $id, $type, $lang );
				}
			}
		}
	}

	private function generateFakeTerm( EntityId $id, $type, $lang ) {
		return $id->getSerialization() . ' ' . $lang . ' ' . $type;
	}

	public function getPrefetchedTerms() {
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

	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$id = $entityId->getSerialization();
		return $this->buffer[$id][$termType][$languageCode] ?? null;
	}

	public function getLabel( EntityId $entityId, $languageCode ) {
		return $this->generateFakeTerm( $entityId, 'label', $languageCode );
	}

	public function getLabels( EntityId $entityId, array $languageCodes ) {
		$labels = [];

		foreach ( $languageCodes as $lang ) {
			$labels[$lang] = $this->generateFakeTerm( $entityId, 'label', $lang );
		}
		return $labels;
	}

	public function getDescription( EntityId $entityId, $languageCode ) {
		return $this->generateFakeTerm( $entityId, 'description', $languageCode );
	}

	public function getDescriptions( EntityId $entityId, array $languageCodes ) {
		$descriptions = [];

		foreach ( $languageCodes as $lang ) {
			$descriptions[$lang] = $this->generateFakeTerm( $entityId, 'description', $lang );
		}
		return $descriptions;
	}

}
