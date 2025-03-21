<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;

/**
 * Trait for code reuse between DatabaseItemTermStoreWriterTest and DatabasePropertyTermStoreWriterTest
 *
 * @author Addshore
 * @author Marius Hoch
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
trait DatabaseTermStoreWriterTestGetTermsTrait {

	private function getTerms( Int32EntityId $entityId, string $termsTable, string $termInLangField, string $idField ): Fingerprint {
		$termsDb = $this->getTermsDomainDb();
		$termInLangIdsResolver = new DatabaseTermInLangIdsResolver( $termsDb );

		$termInLangIds = $this->getDb()->newSelectQueryBuilder()
			->select( $termInLangField )
			->from( $termsTable )
			->where( [ $idField => $entityId->getNumericId() ] )
			->caller( __METHOD__ )->fetchFieldValues();

		return $this->resolveTermIdsResultToFingerprint(
			$termInLangIdsResolver->resolveTermInLangIds( $termInLangIds )
		);
	}

	private function getTermsForItem( ItemId $itemId ): Fingerprint {
		return $this->getTerms( $itemId, 'wbt_item_terms', 'wbit_term_in_lang_id', 'wbit_item_id' );
	}

	private function getTermsForProperty( PropertyId $propertyId ): Fingerprint {
		return $this->getTerms( $propertyId, 'wbt_property_terms', 'wbpt_term_in_lang_id', 'wbpt_property_id' );
	}

	/**
	 * @param array $result Result from TermIdsResolver::resolveTermIds
	 * @return Fingerprint
	 */
	private function resolveTermIdsResultToFingerprint( array $result ) {
		$labels = $result['label'] ?? [];
		$descriptions = $result['description'] ?? [];
		$aliases = $result['alias'] ?? [];

		return new Fingerprint(
			new TermList( array_map(
				function ( $language, $labels ) {
					return new Term( $language, $labels[0] );
				},
				array_keys( $labels ), $labels
			) ),
			new TermList( array_map(
				function ( $language, $descriptions ) {
					return new Term( $language, $descriptions[0] );
				},
				array_keys( $descriptions ), $descriptions
			) ),
			new AliasGroupList( array_map(
				function ( $language, $aliases ) {
					return new AliasGroup( $language, $aliases );
				},
				array_keys( $aliases ), $aliases
			) )
		);
	}

}
