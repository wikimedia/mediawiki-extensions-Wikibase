<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Rdf;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Term\TermTypes;
use Wikimedia\Purtle\RdfWriter;

/**
 * @license GPL-2.0-or-later
 */
class ItemStubRdfBuilder implements PrefetchingEntityStubRdfBuilder {

	private $termLookup;
	private $vocabulary;
	private $writer;
	private $languageCodes;
	private $labelPredicates;
	/** @var EntityId[] using serialization as key to avoid duplicates */
	private $idsToPrefetch = [];

	/**
	 * ItemStubRdfBuilder constructor.
	 *
	 * @param PrefetchingTermLookup $termLookup
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 * @param string[][][] $labelPredicates Map of type to array of [ ns, local ] for each label predicate
	 * @param string[] $languageCodes
	 */
	public function __construct(
		PrefetchingTermLookup $termLookup,
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		array $labelPredicates,
		array $languageCodes
	) {
		$this->termLookup = $termLookup;
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->labelPredicates = $labelPredicates;
		$this->languageCodes = $languageCodes;
	}

	public function addEntityStub( EntityId $id ) {
		$this->prefetchEntityStubData();

		$descriptions = $this->termLookup->getDescriptions(
			$id,
			$this->languageCodes
		);
		$labels = $this->termLookup->getLabels(
			$id,
			$this->languageCodes
		);

		$entityLName = $this->vocabulary->getEntityLName( $id );
		$entityRepoName = $this->vocabulary->getEntityRepositoryName( $id );
		$entityNamespace = $this->vocabulary->entityNamespaceNames[$entityRepoName];

		$this->addLabels(
			$entityNamespace,
			$entityLName,
			$labels,
			$this->getLabelPredicates( Item::ENTITY_TYPE )
		);
		$this->addDescriptions(
			$entityNamespace,
			$entityLName,
			$descriptions
		);
	}

	/**
	 * Adds the descriptions of the given entity to the RDF graph.
	 *
	 * @param string $entityNamespace
	 * @param string $entityLName
	 * @param string[] $descriptions
	 */
	private function addDescriptions( $entityNamespace, $entityLName, array $descriptions ) {
		foreach ( $descriptions as $languageCode => $description ) {
			$this->writer->about( $entityNamespace, $entityLName )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'description' )->text( $description, $languageCode );
		}
	}

	/**
	 * Get predicates that will be used for labels.
	 * @param string $entityType
	 * @return string[][] array of [ ns, local ] for each label predicate
	 */
	private function getLabelPredicates( string $entityType ) {
		return $this->labelPredicates[$entityType] ?? [
				[ 'rdfs', 'label' ],
				[ RdfVocabulary::NS_SKOS, 'prefLabel' ],
				[ RdfVocabulary::NS_SCHEMA_ORG, 'name' ],
			];
	}

	/**
	 * Adds the labels of the given entity to the RDF graph
	 *
	 * @param string $entityNamespace
	 * @param string $entityLName
	 * @param array $labels
	 * @param string[][] $labelPredicates array of [ ns, local ] for each label predicate
	 */
	private function addLabels( $entityNamespace, $entityLName, array $labels, array $labelPredicates ) {
		if ( empty( $labelPredicates ) ) {
			// If we want no predicates, no need to bother with the rest.
			return;
		}
		foreach ( $labels as $languageCode => $labelText ) {
			$this->writer->about( $entityNamespace, $entityLName );
			foreach ( $labelPredicates as $predicate ) {
				$this->writer->say( $predicate[0], $predicate[1] )->text( $labelText, $languageCode );
			}
		}
	}

	public function markForPrefetchingEntityStub( EntityId $id ): void {
		$this->idsToPrefetch[$id->getSerialization()] = $id;
	}

	private function prefetchEntityStubData(): void {
		if ( $this->idsToPrefetch === [] ) {
			return;
		}

		$this->termLookup->prefetchTerms(
			array_values( $this->idsToPrefetch ),
			[
				TermTypes::TYPE_DESCRIPTION,
				TermTypes::TYPE_LABEL,
			],
			$this->languageCodes
		);

		$this->idsToPrefetch = [];
	}
}
