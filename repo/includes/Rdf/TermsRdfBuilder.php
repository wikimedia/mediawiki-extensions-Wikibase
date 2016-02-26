<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\TermList;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for entity terms.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 * @author Stas Malyshev
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
	 * @param string $entityLName
	 * @param TermList $labels
	 */
	public function addLabels( $entityLName, TermList $labels ) {
		foreach ( $labels->toTextArray() as $languageCode => $labelText ) {
			if ( $this->languages !== null && !isset( $this->languages[$languageCode] ) ) {
				continue;
			}

			$this->writer->about( RdfVocabulary::NS_ENTITY, $entityLName )
				->say( 'rdfs', 'label' )->text( $labelText, $languageCode )
				->say( RdfVocabulary::NS_SKOS, 'prefLabel' )->text( $labelText, $languageCode )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'name' )->text( $labelText, $languageCode );

			//TODO: vocabs to use for labels should be configurable
		}
	}

	/**
	 * Adds the descriptions of the given entity to the RDF graph.
	 *
	 * @param string $entityLName
	 * @param TermList $descriptions
	 */
	public function addDescriptions( $entityLName, TermList $descriptions ) {
		foreach ( $descriptions->toTextArray() as $languageCode => $description ) {
			if ( $this->languages !== null && !isset( $this->languages[$languageCode] ) ) {
				continue;
			}

			$this->writer->about( RdfVocabulary::NS_ENTITY, $entityLName )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'description' )->text( $description, $languageCode );
		}
	}

	/**
	 * Adds the aliases of the given entity to the RDF graph.
	 *
	 * @param string $entityLName
	 * @param AliasGroupList $aliases
	 */
	public function addAliases( $entityLName, AliasGroupList $aliases ) {
		/** @var AliasGroup $aliasGroup */
		foreach ( $aliases as $aliasGroup ) {
			$languageCode = $aliasGroup->getLanguageCode();
			if ( $this->languages !== null && !isset( $this->languages[$languageCode] ) ) {
				continue;
			}

			foreach ( $aliasGroup->getAliases() as $alias ) {
				$this->writer->about( RdfVocabulary::NS_ENTITY, $entityLName )
					->say( RdfVocabulary::NS_SKOS, 'altLabel' )->text( $alias, $languageCode );
			}
		}
	}

	/**
	 * Add the entity's terms to the RDF graph.
	 *
	 * @param EntityDocument $entity the entity to output.
	 */
	public function addEntity( EntityDocument $entity ) {
		if ( $entity instanceof FingerprintProvider ) {
			$fingerprint = $entity->getFingerprint();

			/** @var EntityDocument $entity */
			$entityLName = $this->vocabulary->getEntityLName( $entity->getId() );

			$this->addLabels( $entityLName, $fingerprint->getLabels() );
			$this->addDescriptions( $entityLName, $fingerprint->getDescriptions() );
			$this->addAliases( $entityLName, $fingerprint->getAliasGroups() );
		}
	}

}
