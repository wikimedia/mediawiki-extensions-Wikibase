<?php

namespace Wikibase\Rdf;

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
	 * @param RdfVocabulary $vocabulary
	 * @param RdfWriter $writer
	 * @param string[]|null $languages
	 */
	public function __construct( RdfVocabulary $vocabulary, RdfWriter $writer, array $languages = null ) {
		$this->vocabulary = $vocabulary;
		$this->writer = $writer;
		$this->languages = $languages === null ? null : array_flip( $languages );
	}

	/**
	 * Adds the labels of the given entity to the RDF graph
	 *
	 * @param string $entityNamespace
	 * @param string $entityLName
	 * @param TermList $labels
	 */
	public function addLabels( $entityNamespace, $entityLName, TermList $labels ) {
		foreach ( $labels->toTextArray() as $languageCode => $labelText ) {
			if ( $this->languages !== null && !isset( $this->languages[$languageCode] ) ) {
				continue;
			}

			$this->writer->about( $entityNamespace, $entityLName )
				->say( 'rdfs', 'label' )->text( $labelText, $languageCode )
				->say( RdfVocabulary::NS_SKOS, 'prefLabel' )->text( $labelText, $languageCode )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'name' )->text( $labelText, $languageCode );

			//TODO: vocabs to use for labels should be configurable
		}
	}

	/**
	 * Adds the descriptions of the given entity to the RDF graph.
	 *
	 * @param string $entityNamespace
	 * @param string $entityLName
	 * @param TermList $descriptions
	 */
	public function addDescriptions( $entityNamespace, $entityLName, TermList $descriptions ) {
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
	public function addAliases( $entityNamespace, $entityLName, AliasGroupList $aliases ) {
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
			$this->addLabels( $entityNamespace, $entityLName, $entity->getLabels() );
		}

		if ( $entity instanceof DescriptionsProvider ) {
			$this->addDescriptions( $entityNamespace, $entityLName, $entity->getDescriptions() );
		}

		if ( $entity instanceof AliasesProvider ) {
			$this->addAliases( $entityNamespace, $entityLName, $entity->getAliasGroups() );
		}
	}

	/**
	 * Add the entity's labels and descriptions to the RDF graph.
	 *
	 * @see EntityRdfBuilder::addEntityStub
	 *
	 * @param EntityDocument $entity the entity to output.
	 */
	public function addEntityStub( EntityDocument $entity ) {
		$entityId = $entity->getId();
		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$entityRepoName = $this->vocabulary->getEntityRepositoryName( $entityId );
		$entityNamespace = $this->vocabulary->entityNamespaceNames[$entityRepoName];

		if ( $entity instanceof LabelsProvider ) {
			$this->addLabels( $entityNamespace, $entityLName, $entity->getLabels() );
		}

		if ( $entity instanceof DescriptionsProvider ) {
			$this->addDescriptions( $entityNamespace, $entityLName, $entity->getDescriptions() );
		}
	}

}
