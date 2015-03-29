<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\RDF\RdfWriter;

/**
 * RDF mapping for entity terms.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
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
	 * Language filter
	 *
	 * @param $lang
	 *
	 * @return bool
	 */
	private function isLanguageIncluded( $lang ) {
		return $this->languages === null || isset( $this->languages[$lang] );
	}


	/**
	 * Adds the labels of the given entity to the RDF graph
	 *
	 * @todo: take a termList and an EntityId or lname
	 * @param Entity $entity
	 */
	public function addLabels( Entity $entity ) {
		$entityLName = $this->vocabulary->getEntityLName( $entity->getId() );

		foreach ( $entity->getLabels() as $languageCode => $labelText ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
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
	 * @param Entity $entity
	 */
	public function addDescriptions( Entity $entity ) {
		$entityLName = $this->vocabulary->getEntityLName( $entity->getId() );

		foreach ( $entity->getDescriptions() as $languageCode => $description ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			$this->writer->about( RdfVocabulary::NS_ENTITY, $entityLName )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'description' )->text( $description, $languageCode );
		}
	}

	/**
	 * Adds the aliases of the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	public function addAliases( Entity $entity ) {
		$entityLName = $this->vocabulary->getEntityLName( $entity->getId() );

		foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			foreach ( $aliases as $alias ) {
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
		if ( $entity instanceof Entity ) {
			$this->addLabels( $entity );
			$this->addDescriptions( $entity );
			$this->addAliases( $entity );
		}
	}

}
