<?php

namespace Wikibase;

use DataValues\DataValue;
use EasyRdf_Graph;
use EasyRdf_Literal;
use EasyRdf_Namespace;
use EasyRdf_Resource;
use SiteList;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\Lib\Store\EntityLookup;

/**
 * RDF mapping for wikibase data model.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 */
class RdfBuilder {

	const ONTOLOGY_BASE_URI = 'http://www.wikidata.org/ontology#'; //XXX: Denny made me put the "www" there...

	const NS_ONTOLOGY =  'wikibase'; // wikibase ontology (shared)
	const NS_ENTITY =    'entity';   // concept uris
	const NS_DATA =      'data';     // document uris
	const NS_VALUE =     'v'; // statement -> value
	const NS_QUALIFIER = 'q'; // statement -> qualifier
	const NS_STATEMENT = 's'; // entity -> statement

	const NS_SKOS = 'skos'; // SKOS vocabulary
	const NS_SCHEMA_ORG = 'schema'; // schema.org vocabulary
	const NS_CC = 'cc';

	const SKOS_URI = 'http://www.w3.org/2004/02/skos/core#';
	const SCHEMA_ORG_URI = 'http://schema.org/';
	const CC_URI = 'http://creativecommons.org/ns#';

	const WIKIBASE_STATEMENT_QNAME = 'wikibase:Statement';

	/**
	 * @var SiteList
	 */
	private $sites;

	/**
	 * Map of qnames to namespace URIs
	 *
	 * @var array
	 */
	private $namespaces = array();

	/**
	 * A list of entities mentioned/touched to or by this builder.
	 * The prefixed entity IDs are used as keys in the array, the values 'true'
	 * is used to indicate that the entity has been resolved, 'false' indicates
	 * that the entity was mentioned but not resolved (defined).
	 *
	 * @var array
	 */
	private $entitiesResolved = array();

	/**
	 * @param SiteList $sites
	 * @param string $baseUri
	 * @param string $dataUri
	 * @param EasyRdf_Graph|null $graph
	 */
	public function __construct(
		SiteList $sites,
		$baseUri,
		$dataUri,
		EasyRdf_Graph $graph = null
	) {
		if ( !$graph ) {
			$graph = new EasyRdf_Graph();
		}

		$this->graph = $graph;

		$this->sites = $sites;
		$this->baseUri = $baseUri;
		$this->dataUri = $dataUri;

		$this->namespaces = array(
			self::NS_ONTOLOGY => self::ONTOLOGY_BASE_URI,
			self::NS_DATA => $this->dataUri,
			self::NS_ENTITY => $this->baseUri,
			self::NS_VALUE => $this->baseUri . 'value/',
			self::NS_QUALIFIER => $this->baseUri . 'qualifier/',
			self::NS_STATEMENT => $this->baseUri . 'statement/',
			self::NS_SKOS => self::SKOS_URI,
			self::NS_SCHEMA_ORG => self::SCHEMA_ORG_URI,
			self::NS_CC => self::CC_URI,
		);

		//XXX: Ugh, static. Should go into $this->graph.
		foreach ( $this->getNamespaces() as $gname => $uri ) {
			EasyRdf_Namespace::set( $gname, $uri );
		}
	}

	/**
	 * Returns the builder's graph
	 *
	 * @return EasyRdf_Graph
	 */
	public function getGraph() {
		return $this->graph;
	}

	/**
	 * Returns a map of namespace names to URIs
	 *
	 * @return array
	 */
	public function getNamespaces() {
		return $this->namespaces;
	}

	/**
	 * Returns a qname for the given entity using the given prefix.
	 * To refer to the entity as such, use self::NS_ENTITY.
	 * To refer to the description of the entity, use NS_DATA.
	 * For the different prefixes used for properties in statements,
	 * refer to the Wikibase RDF mapping spec.
	 *
	 * @param string   $prefix use a self::NS_XXX constant
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function getEntityQName( $prefix, EntityId $entityId ) {
		return $prefix . ':' . ucfirst( $entityId->getSerialization() );
	}

	/**
	 * Returns a qname for the given statement using the given prefix.
	 *
	 * @param string $prefix use a self::NS_XXX constant, usually self::NS_STATEMENT
	 * @param Statement $statement
	 *
	 * @return string
	 */
	private function getStatementQName( $prefix, Statement $statement ) {
		return $prefix . ':' . $statement->getGuid();
	}

	/**
	 * Returns a qname for the given entity type.
	 * For well known types, these qnames refer to classes from the Wikibase ontology.
	 *
	 * @param $type
	 *
	 * @return string
	 */
	private function getEntityTypeQName( $type ) {
		//TODO: the list of types is configurable, need to register URIs for extra types!

		return self::NS_ONTOLOGY . ':' . ucfirst( $type );
	}

	/**
	 * Gets a resource object representing the given entity
	 *
	 * @param EntityId $entityId
	 *
	 * @return EasyRDF_Resource
	 */
	private function getEntityResource( EntityId $entityId ) {
		$entityQName = $this->getEntityQName( self::NS_ENTITY, $entityId );
		$entityResource = $this->graph->resource( $entityQName );
		return $entityResource;
	}

	/**
	 * Gets a URL of the rdf description of the given entity
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function getDataURL( EntityId $entityId ) {
		return $this->namespaces[self::NS_DATA] . ucfirst( $entityId->getSerialization() );
	}

	/**
	 * Language filter
	 *
	 * @param $lang
	 *
	 * @return bool
	 */
	private function isLanguageIncluded( $lang ) {
		return true; //todo: optional filter
	}

	/**
	 * Registers an entity as mentioned. Will be recorded as unresolved
	 * if it wasn't already marked as resolved.
	 *
	 * @param EntityId $entityId
	 */
	private function entityMentioned( EntityId $entityId ) {
		$prefixedId = $entityId->getSerialization();

		if ( !isset( $this->entitiesResolved[$prefixedId] ) ) {
			$this->entitiesResolved[$prefixedId] = false;
		}
	}

	/**
	 * Registers an entity as resolved.
	 *
	 * @param EntityId $entityId
	 */
	private function entityResolved( EntityId $entityId ) {
		$prefixedId = $entityId->getSerialization();
		$this->entitiesResolved[$prefixedId] = true;
	}

	/**
	 * Adds revision information about an entity's revision to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param int $revision
	 * @param string $timestamp in TS_MW format
	 */
	public function addEntityRevisionInfo( EntityId $entityId, $revision, $timestamp ) {
		$dataURL = $this->getDataURL( $entityId );
		$dataResource = $this->graph->resource( $dataURL );

		$timestamp = wfTimestamp( TS_ISO_8601, $timestamp );
		$dataResource->addLiteral(
			self::NS_SCHEMA_ORG . ':version',
			new EasyRdf_Literal( $revision, null, 'xsd:integer' )
		);
		$dataResource->addLiteral(
			self::NS_SCHEMA_ORG . ':dateModified',
			new EasyRdf_Literal( $timestamp, null, 'xsd:dateTime' )
		);
		//TODO: versioned data URI, current-version-of
	}

	/**
	 * Adds meta-information about an entity (such as the ID and type) to the RDF graph.
	 *
	 * @param Entity         $entity
	 */
	private function addEntityMetaData( Entity $entity ) {
		$entityResource = $this->getEntityResource( $entity->getId() );
		$entityResource->addResource( 'rdf:type', $this->getEntityTypeQName( $entity->getType() ) );
		$dataURL = $this->getDataURL( $entity->getId() );

		$dataResource = $this->graph->resource( $dataURL );
		$dataResource->addResource( 'rdf:type', self::NS_SCHEMA_ORG . ":Dataset" );
		$dataResource->addResource( self::NS_SCHEMA_ORG . ':about', $entityResource );
		// TODO: make the license settable
		$dataResource->addResource( self::NS_CC . ':license', 'http://creativecommons.org/publicdomain/zero/1.0/' );

		//TODO: add support for property date types to RDF output

		$this->entityResolved( $entity->getId() );
	}

	/**
	 * Adds the labels of the given entity to the RDF graph
	 *
	 * @param Entity $entity
	 */
	private function addLabels( Entity $entity ) {
		$entityResource = $this->getEntityResource( $entity->getId() );

		foreach ( $entity->getLabels() as $languageCode => $labelText ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			$entityResource->addLiteral( 'rdfs:label', $labelText, $languageCode );
			$entityResource->addLiteral( self::NS_SKOS . ':prefLabel', $labelText, $languageCode );
			$entityResource->addLiteral( self::NS_SCHEMA_ORG . ':name', $labelText, $languageCode );
		}
	}

	/**
	 * Adds the descriptions of the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	private function addDescriptions( Entity $entity ) {
		$entityResource = $this->getEntityResource( $entity->getId() );

		foreach ( $entity->getDescriptions() as $languageCode => $description ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			$entityResource->addLiteral( self::NS_SCHEMA_ORG . ':description', $description, $languageCode );
		}
	}

	/**
	 * Adds the aliases of the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	private function addAliases( Entity $entity ) {
		$entityResource = $this->getEntityResource( $entity->getId() );

		foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			foreach ( $aliases as $alias ) {
				$entityResource->addLiteral( self::NS_SKOS . ':altLabel', $alias, $languageCode );
			}
		}
	}

	/**
	 * Adds the site links of the given item to the RDF graph.
	 *
	 * @param Item $item
	 */
	private function addSiteLinks( Item $item ) {
		$entityResource = $this->getEntityResource( $item->getId() );

		/** @var SiteLink $siteLink */
		foreach ( $item->getSiteLinkList() as $siteLink ) {
			$site = $this->sites->getSite( $siteLink->getSiteId() );

			$languageCode = $site->getLanguageCode();

			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			//XXX: ideally, we'd use https if the target site supports it.
			$baseUrl = $site->getPageUrl( $siteLink->getPageName() );
			$url = wfExpandUrl( $baseUrl, PROTO_HTTP );
			$pageRecourse = $this->graph->resource( $url );

			$pageRecourse->addResource( 'rdf:type', self::NS_SCHEMA_ORG . ':Article' );
			$pageRecourse->addResource( self::NS_SCHEMA_ORG . ':about', $entityResource );
			$pageRecourse->addLiteral( self::NS_SCHEMA_ORG . ':inLanguage', $languageCode );
		}
	}

	/**
	 * Adds all Statements from the given entity to the RDF graph.
	 *
	 * @param EntityDocument $entity
	 */
	private function addStatements( EntityDocument $entity ) {
		$entityId = $entity->getId();

		if ( $entity instanceof StatementListProvider ) {
			foreach ( $entity->getStatements() as $statement ) {
				$this->addStatement( $entityId, $statement );
			}
		}
	}

	/**
	 * Adds the given Statement from the given Entity to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 */
	private function addStatement( EntityId $entityId, Statement $statement ) {
		$this->addMainSnak( $entityId, $statement );

		//TODO: add qualifiers
		//TODO: add references
	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 */
	private function addMainSnak( EntityId $entityId, Statement $statement ) {
		$snak = $statement->getMainSnak();

		if ( $snak instanceof PropertyValueSnak ) {
			$this->addPropertyValueSnak( $entityId, $statement, $snak );
		} else {
			//TODO: NoValueSnak, SomeValueSnak
			wfDebug( __METHOD__ . ": Unsupported snak type: " . get_class( $snak ) );
		}
	}

	/**
	 * Returns a resource representing the given Statement.
	 *
	 * @param Statement $statement
	 *
	 * @return EasyRDF_Resource
	 */
	private function getStatementResource( Statement $statement ) {
		$statementQName = $this->getStatementQName( self::NS_STATEMENT, $statement );
		$statementResource = $this->graph->resource( $statementQName, array( self::WIKIBASE_STATEMENT_QNAME ) );
		return $statementResource;
	}

	/**
	 * Adds the given PropertyValueSnak to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param PropertyValueSnak $snak
	 * @param Statement $statement
	 */
	private function addPropertyValueSnak( EntityId $entityId, Statement $statement, PropertyValueSnak $snak ) {
		$entityResource = $this->getEntityResource( $entityId );

		$propertyId = $statement->getMainSnak()->getPropertyId();
		$propertyQName = $this->getEntityQName( self::NS_ENTITY, $propertyId );

		$statementResource = $this->getStatementResource( $statement );
		$entityResource->addResource( $propertyQName, $statementResource );

		$value = $snak->getDataValue();

		$this->entityMentioned( $propertyId );
		$this->addStatementValue( $statement, $propertyId, $value );
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param Statement $statement
	 * @param EntityId  $propertyId
	 * @param DataValue $value
	 */
	private function addStatementValue( Statement $statement, EntityId $propertyId, DataValue $value ) {
		$statementResource = $this->getStatementResource( $statement );
		$propertyValueQName = $this->getEntityQName( self::NS_VALUE, $propertyId );

		$typeId = $value->getType();

		switch ( $typeId ) {
			case 'wikibase-item':
				$entityId = $value->getValue();
				$entityQName = $this->getEntityQName( self::NS_ENTITY, $entityId );
				$entityResource = $this->graph->resource( $entityQName );
				$statementResource->addResource( $propertyValueQName, $entityResource );
				$this->entityMentioned( $entityId );
				break;
			case 'commonsMedia':
				$statementResource->addResource( $propertyValueQName, $value );
				break;
			default:
				//TODO: more media types
				wfDebug( __METHOD__ . ": Unsupported data type: $typeId\n" );
		}
	}

	/**
	 * Add stubs for any entities that were previously mentioned (e.g. as properties
	 * or data values).
	 *
	 * @param EntityLookup $entityLookup
	 */
	public function resolvedMentionedEntities( EntityLookup $entityLookup ) {
		// @todo inject a DispatchingEntityIdParser
		$idParser = new BasicEntityIdParser();

		foreach ( $this->entitiesResolved as $entityId => $resolved ) {
			if ( !$resolved ) {
				$entityId = $idParser->parse( $entityId );
				$entity = $entityLookup->getEntity( $entityId );

				$this->addEntityStub( $entity );
			}
		}
	}

	/**
	 * Add an entity to the RDF graph, including all supported structural components
	 * of the entity.
	 *
	 * @param Entity $entity the entity to output.
	 */
	public function addEntity( Entity $entity ) {
		$this->addEntityMetaData( $entity );
		$this->addLabels( $entity );
		$this->addDescriptions( $entity );
		$this->addAliases( $entity );

		if ( $entity instanceof Item ) {
			$this->addSiteLinks( $entity );
		}

		if ( $entity instanceof EntityDocument ) {
			//$this->addStatements( $entity ); //TODO: finish this.
		}
	}

	/**
	 * Adds stub information for the given Entity to the RDF graph. Stub information
	 * means meta information and labels.
	 *
	 * @param Entity $entity
	 */
	private function addEntityStub( Entity $entity ) {
		$this->addEntityMetaData( $entity );
		$this->addLabels( $entity );
		$this->addDescriptions( $entity );
	}

}
