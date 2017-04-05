<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
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
 * @license GPL-2.0+
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
	 * @param EntityId $entityId
	 * @param TermList $labels
	 */
	public function addLabels( EntityId $entityId, TermList $labels ) {
		foreach ( $labels->toTextArray() as $languageCode => $labelText ) {
			if ( $this->languages !== null && !isset( $this->languages[$languageCode] ) ) {
				continue;
			}

			$this->writer->about( $this->vocabulary->getEntityNamespace( $entityId ), $this->vocabulary->getEntityLName( $entityId ) )
				->say( 'rdfs', 'label' )->text( $labelText, $languageCode )
				->say( RdfVocabulary::NS_SKOS, 'prefLabel' )->text( $labelText, $languageCode )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'name' )->text( $labelText, $languageCode );

			//TODO: vocabs to use for labels should be configurable
		}
	}

	/**
	 * Adds the descriptions of the given entity to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param TermList $descriptions
	 */
	public function addDescriptions( EntityId $entityId, TermList $descriptions ) {
		foreach ( $descriptions->toTextArray() as $languageCode => $description ) {
			if ( $this->languages !== null && !isset( $this->languages[$languageCode] ) ) {
				continue;
			}

			$this->writer->about( $this->vocabulary->getEntityNamespace( $entityId ), $this->vocabulary->getEntityLName( $entityId ) )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'description' )->text( $description, $languageCode );
		}
	}

	/**
	 * Adds the aliases of the given entity to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param AliasGroupList $aliases
	 */
	public function addAliases( EntityId $entityId, AliasGroupList $aliases ) {
		/** @var AliasGroup $aliasGroup */
		foreach ( $aliases as $aliasGroup ) {
			$languageCode = $aliasGroup->getLanguageCode();
			if ( $this->languages !== null && !isset( $this->languages[$languageCode] ) ) {
				continue;
			}

			foreach ( $aliasGroup->getAliases() as $alias ) {
				$this->writer->about( $this->vocabulary->getEntityNamespace( $entityId ), $this->vocabulary->getEntityLName( $entityId ) )
					->say( RdfVocabulary::NS_SKOS, 'altLabel' )->text( $alias, $languageCode );
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
		if ( $entity instanceof LabelsProvider ) {
			$this->addLabels( $entity->getId(), $entity->getLabels() );
		}

		if ( $entity instanceof DescriptionsProvider ) {
			$this->addDescriptions( $entity->getId(), $entity->getDescriptions() );
		}

		if ( $entity instanceof AliasesProvider ) {
			$this->addAliases( $entity->getId(), $entity->getAliasGroups() );
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
		if ( $entity instanceof LabelsProvider ) {
			$this->addLabels( $entity->getId(), $entity->getLabels() );
		}

		if ( $entity instanceof DescriptionsProvider ) {
			$this->addDescriptions( $entity->getId(), $entity->getDescriptions() );
		}
	}

}
