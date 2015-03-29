<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
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
class TermsRdfBuilder {

	const NS_ONTOLOGY = 'wikibase'; // wikibase ontology (shared)
	const NS_ENTITY = 'entity'; // concept uris
	const NS_SKOS = 'skos'; // SKOS vocabulary
	const NS_SCHEMA_ORG = 'schema'; // schema.org vocabulary

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var string[]|null a list of desired languages, or null for all languages.
	 */
	private $languages;

	/**
	 * @param RdfWriter $writer
	 * @param string[]|null $languages
	 */
	public function __construct( RdfWriter $writer, array $languages = null ) {
		$this->writer = $writer;
		$this->languages = $languages === null ? null : array_flip( $languages );
	}

	/**
	 * Returns a local name for the given entity using the given prefix.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	private function getEntityLName( EntityId $entityId ) {
		return ucfirst( $entityId->getSerialization() );
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
		$entityLName = $this->getEntityLName( $entity->getId() );

		foreach ( $entity->getLabels() as $languageCode => $labelText ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			$this->writer->about( self::NS_ENTITY, $entityLName )
				->say( 'rdfs', 'label' )->text( $labelText, $languageCode )
				->say( self::NS_SKOS, 'prefLabel' )->text( $labelText, $languageCode )
				->say( self::NS_SCHEMA_ORG, 'name' )->text( $labelText, $languageCode );

			//TODO: vocabs to use for labels should be configurable
		}
	}

	/**
	 * Adds the descriptions of the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	public function addDescriptions( Entity $entity ) {
		$entityLName = $this->getEntityLName( $entity->getId() );

		foreach ( $entity->getDescriptions() as $languageCode => $description ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			$this->writer->about( self::NS_ENTITY, $entityLName )
				->say( self::NS_SCHEMA_ORG, 'description' )->text( $description, $languageCode );
		}
	}

	/**
	 * Adds the aliases of the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	public function addAliases( Entity $entity ) {
		$entityLName = $this->getEntityLName( $entity->getId() );

		foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			foreach ( $aliases as $alias ) {
				$this->writer->about( self::NS_ENTITY, $entityLName )
					->say( self::NS_SKOS, 'altLabel' )->text( $alias, $languageCode );
			}
		}
	}

}
