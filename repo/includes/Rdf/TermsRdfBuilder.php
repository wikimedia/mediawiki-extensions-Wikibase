<?php

namespace Wikibase\Repo\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for entity terms.
 *
 * @license GPL-2.0-or-later
 */
class TermsRdfBuilder implements EntityRdfBuilder {

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var string[]|null a list of desired languages, or null for all languages.
	 */
	private $languages;

	/**
	 * @var string[][][] Map of type to array of [ ns, local ] for each label predicate
	 */
	private $labelPredicates;

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 * @param string[][][] $labelPredicates Map of type to array of [ ns, local ] for each label predicate
	 * @param string[]|null $languages
	 */
	public function __construct(
		RdfVocabulary $vocabulary,
		RdfWriter $writer,
		array $labelPredicates = [],
		array $languages = null
	) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->languages = $languages === null ? null : array_flip( $languages );
		$this->labelPredicates = $labelPredicates;
	}

	/**
	 * Get predicates that will be used for labels.
	 * @param EntityDocument $entity
	 * @return string[][] array of [ ns, local ] for each label predicate
	 */
	private function getLabelPredicates( EntityDocument $entity ) {
		return $this->labelPredicates[$entity->getType()] ?? [
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
	 * @param TermList $labels
	 * @param string[][] $labelPredicates array of [ ns, local ] for each label predicate
	 */
	private function addLabels( $entityNamespace, $entityLName, TermList $labels, array $labelPredicates ) {
		if ( empty( $labelPredicates ) ) {
			// If we want no predicates, no need to bother with the rest.
			return;
		}
		foreach ( $labels->toTextArray() as $languageCode => $labelText ) {
			if ( $this->languages !== null && !isset( $this->languages[$languageCode] ) ) {
				continue;
			}

			$this->writer->about( $entityNamespace, $entityLName );
			foreach ( $labelPredicates as $predicate ) {
				$this->writer->say( $predicate[0], $predicate[1] )->text( $labelText, $languageCode );
			}
		}
	}

	/**
	 * Adds the descriptions of the given entity to the RDF graph.
	 *
	 * @param string $entityNamespace
	 * @param string $entityLName
	 * @param TermList $descriptions
	 */
	private function addDescriptions( $entityNamespace, $entityLName, TermList $descriptions ) {
		foreach ( $descriptions->toTextArray() as $languageCode => $description ) {
			if ( $this->languages !== null && !isset( $this->languages[$languageCode] ) ) {
				continue;
			}

			$this->writer->about( $entityNamespace, $entityLName )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'description' )->text( $description, $languageCode );
		}
	}

	/**
	 * Adds the aliases of the given entity to the RDF graph.
	 *
	 * @param string $entityNamespace
	 * @param string $entityLName
	 * @param AliasGroupList $aliases
	 */
	private function addAliases( $entityNamespace, $entityLName, AliasGroupList $aliases ) {
		/** @var AliasGroup $aliasGroup */
		foreach ( $aliases as $aliasGroup ) {
			$languageCode = $aliasGroup->getLanguageCode();
			if ( $this->languages !== null && !isset( $this->languages[$languageCode] ) ) {
				continue;
			}

			foreach ( $aliasGroup->getAliases() as $alias ) {
				$this->writer->about(
					$entityNamespace,
					$entityLName
				)->say( RdfVocabulary::NS_SKOS, 'altLabel' )->text( $alias, $languageCode );
			}
		}
	}

	/**
	 * Add the entity's labels, descriptions, and aliases to the RDF graph.
	 *
	 * @see EntityRdfBuilder::addEntity
	 *
	 * @param EntityDocument $entity the entity to output.
	 */
	public function addEntity( EntityDocument $entity ) {
		$entityId = $entity->getId();
		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$entityRepoName = $this->vocabulary->getEntityRepositoryName( $entityId );
		$entityNamespace = $this->vocabulary->entityNamespaceNames[$entityRepoName];

		if ( $entity instanceof LabelsProvider ) {
			$this->addLabels( $entityNamespace, $entityLName, $entity->getLabels(),
				$this->getLabelPredicates( $entity ) );
		}

		if ( $entity instanceof DescriptionsProvider ) {
			$this->addDescriptions( $entityNamespace, $entityLName, $entity->getDescriptions() );
		}

		if ( $entity instanceof AliasesProvider ) {
			$this->addAliases( $entityNamespace, $entityLName, $entity->getAliasGroups() );
		}
	}

}
