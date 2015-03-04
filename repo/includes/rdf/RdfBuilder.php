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
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\SnakObject;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\DataModel\Entity\EntityIdValue;
use DataValues\TimeValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\MonolingualTextValue;
use DataValues\GlobeCoordinateValue;

/**
 * RDF mapping for wikibase data model.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class RdfBuilder {
	// Change this when changing data format!
	const FORMAT_VERSION = '0.0.1';

	const ONTOLOGY_BASE_URI = 'http://www.wikidata.org/ontology'; // XXX: Denny made me put the "www" there...
	const NS_ONTOLOGY = 'wikibase'; // wikibase ontology (shared)
	const NS_ENTITY = 'entity'; // concept uris
	const NS_DATA = 'data'; // document uris
	const NS_VALUE = 'v'; // statement -> value
	const NS_QUALIFIER = 'q'; // statement -> qualifier
	const NS_STATEMENT = 's'; // entity -> statement
	const NS_DIRECT_CLAIM = 'wdt'; // direct assertion entity -> value
	const NS_REFERENCE = 'ref';
	const NS_SKOS = 'skos'; // SKOS vocabulary
	const NS_SCHEMA_ORG = 'schema'; // schema.org vocabulary
	const NS_CC = 'cc'; // Creative Commons
	const NS_GEO = 'geo'; // prefix for geolocations
	const NS_PROV = 'prov'; // for provenance
	const SKOS_URI = 'http://www.w3.org/2004/02/skos/core#';
	const SCHEMA_ORG_URI = 'http://schema.org/';
	const CC_URI = 'http://creativecommons.org/ns#';

	const WIKIBASE_STATEMENT_QNAME = 'wikibase:Statement';
	const WIKIBASE_REFERENCE_QNAME = 'wikibase:Reference';
	const WIKIBASE_VALUE_QNAME = 'wikibase:Value';
	const WIKIBASE_RANK_QNAME = 'wikibase:Rank';
	const WIKIBASE_SOMEVALUE_QNAME = "wikibase:Somevalue";
	const WIKIBASE_NOVALUE_QNAME = "wikibase:Novalue";
	const WIKIBASE_RANK_NORMAL = 'wikibase:NormalRank';
	const WIKIBASE_RANK_PREFERRED = 'wikibase:PreferredRank';
	const WIKIBASE_RANK_DEPRECATED = 'wikibase:DeprecatedRank';
	const WIKIBASE_BADGE_QNAME = 'wikibase:Badge';

	const PROV_QNAME = 'prov:wasDerivedFrom';

	const COMMONS_URI = 'http://commons.wikimedia.org/wiki/Special:FilePath/';
	const GEO_URI = 'http://www.opengis.net/ont/geosparql#';
	const PROV_URI = 'http://www.w3.org/ns/prov#';
	// TODO: make the license settable
	const LICENSE = 'http://creativecommons.org/publicdomain/zero/1.0/';

	public static $rank_map = array(
		Statement::RANK_DEPRECATED => self::WIKIBASE_RANK_DEPRECATED,
		Statement::RANK_NORMAL => self::WIKIBASE_RANK_NORMAL,
		Statement::RANK_PREFERRED => self::WIKIBASE_RANK_PREFERRED,
	);

	/**
	 *
	 * @var SiteList
	 */
	private $sites;

	/**
	 * Map of qnames to namespace URIs
	 *
	 * @var array
	 */
	private $namespaces = array ();

	/**
	 * A list of entities mentioned/touched to or by this builder.
	 * The prefixed entity IDs are used as keys in the array, the values 'true'
	 * is used to indicate that the entity has been resolved, 'false' indicates
	 * that the entity was mentioned but not resolved (defined).
	 *
	 * @var array
	 */
	private $entitiesResolved = array ();

	/**
	 *
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * What the serializer would produce?
	 * @var integer
	 */
	private $produceWhat;

	/**
	 *
	 * @param SiteList $sites
	 * @param string $baseUri
	 * @param string $dataUri
	 * @param EntityLookup $entityLookup
	 * @param integer $flavor
	 * @param EasyRdf_Graph|null $graph
	 */
	public function __construct( SiteList $sites, $baseUri, $dataUri, EntityLookup $entityLookup, $flavor, EasyRdf_Graph $graph = null ) {
		if ( !$graph ) {
			$graph = new EasyRdf_Graph();
		}

		$this->graph = $graph;

		$this->sites = $sites;
		$this->baseUri = $baseUri;
		$this->dataUri = $dataUri;
		$this->entityLookup = $entityLookup;
		$this->produceWhat = $flavor;

		$this->namespaces = array (
				self::NS_ONTOLOGY => self::ONTOLOGY_BASE_URI . "-" . self::FORMAT_VERSION . "#",
				self::NS_DIRECT_CLAIM => $this->baseUri . 'assert/',
				self::NS_VALUE => $this->baseUri . 'value/',
				self::NS_QUALIFIER => $this->baseUri . 'qualifier/',
				self::NS_STATEMENT => $this->baseUri . 'statement/',
				self::NS_REFERENCE => $this->baseUri . 'reference/',
				self::NS_DATA => $this->dataUri,
				self::NS_ENTITY => $this->baseUri,
				self::NS_SKOS => self::SKOS_URI,
				self::NS_SCHEMA_ORG => self::SCHEMA_ORG_URI,
				self::NS_CC => self::CC_URI,
				self::NS_GEO => self::GEO_URI,
				self::NS_PROV => self::PROV_URI
		);

		// XXX: Ugh, static. Should go into $this->graph.
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
	 * @param string $prefix use a self::NS_* constant
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
	 * @param string $prefix use a self::NS_* constant, usually self::NS_STATEMENT
	 * @param Statement $statement
	 *
	 * @return string
	 */
	private function getStatementQName( $prefix, Statement $statement ) {
		return $prefix . ':' . preg_replace( '/[^\w-]/', '-', $statement->getGuid() );
	}

	/**
	 * Returns a qname for the given reference using the given prefix.
	 *
	 * @param string $prefix use a self::NS_* constant, usually self::NS_REFERENCE
	 * @param Reference $statement
	 *
	 * @return string
	 */
	private function getReferenceQName( $prefix, Reference $ref ) {
		return $prefix . ':' . $ref->getHash();
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
		// TODO: the list of types is configurable, need to register URIs for extra types!
		return self::NS_ONTOLOGY . ':' . ucfirst( $type );
	}

	/**
	 * Create Commons URL from filename value
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	private function getCommonsURI( $file ) {
		return self::COMMONS_URI . $file;
	}

	/**
	 * Should we produce this aspect?
	 *
	 * @param integer $what
	 * @return boolean
	 */
	private function shouldProduce( $what ) {
		return ($this->produceWhat & $what) != 0;
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
		return true; // todo: optional filter
	}

	/**
	 * Registers an entity as mentioned.
	 * Will be recorded as unresolved
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
		$this->graph->addLiteral( $dataResource, self::NS_SCHEMA_ORG . ':version', new EasyRdf_Literal( $revision, null, 'xsd:integer' ) );
		$this->graph->addLiteral( $dataResource, self::NS_SCHEMA_ORG . ':dateModified', new EasyRdf_Literal( $timestamp, null, 'xsd:dateTime' ) );
		// TODO: versioned data URI, current-version-of
	}

	/**
	 * Adds meta-information about an entity (such as the ID and type) to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	private function addEntityMetaData( Entity $entity ) {
		$entityResource = $this->getEntityResource( $entity->getId() );
		$this->graph->addResource( $entityResource, 'rdf:type', $this->getEntityTypeQName( $entity->getType() ) );
		$dataURL = $this->getDataURL( $entity->getId() );

		$dataResource = $this->graph->resource( $dataURL );
		$this->graph->addResource( $dataResource, 'rdf:type', self::NS_SCHEMA_ORG . ":Dataset" );
		$this->graph->addResource( $dataResource, self::NS_SCHEMA_ORG . ':about', $entityResource );
		if($this->shouldProduce( RdfSerializer::PRODUCE_VERSION_INFO ) ) {
			$this->graph->addLiteral( $dataResource, self::NS_CC . ':license', self::LICENSE );
			$this->graph->addLiteral( $dataResource, self::NS_SCHEMA_ORG . ':softwareVersion', self::FORMAT_VERSION );
		}

		// TODO: add support for property date types to RDF output

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

			$this->graph->addLiteral( $entityResource, 'rdfs:label', $labelText, $languageCode );
			$this->graph->addLiteral( $entityResource, self::NS_SKOS . ':prefLabel', $labelText, $languageCode );
			$this->graph->addLiteral( $entityResource, self::NS_SCHEMA_ORG . ':name', $labelText, $languageCode );
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

			$this->graph->addLiteral( $entityResource, self::NS_SCHEMA_ORG . ':description', $description, $languageCode );
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
				$this->graph->addLiteral( $entityResource, self::NS_SKOS . ':altLabel', $alias, $languageCode );
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

		foreach ( $item->getSiteLinkList() as $siteLink ) {
			$site = $this->sites->getSite( $siteLink->getSiteId() );

			$languageCode = $site->getLanguageCode();

			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			// XXX: ideally, we'd use https if the target site supports it.
			$baseUrl = $site->getPageUrl( $siteLink->getPageName() );
			$url = wfExpandUrl( $baseUrl, PROTO_HTTP );
			$pageResourse = $this->graph->resource( $url );

			$this->graph->addResource( $pageResourse, 'rdf:type', self::NS_SCHEMA_ORG . ':Article' );
			$this->graph->addResource( $pageResourse, self::NS_SCHEMA_ORG . ':about', $entityResource );
			$this->graph->addResource( $pageResourse, self::NS_SCHEMA_ORG . ':inLanguage', $languageCode );

			foreach ( $siteLink->getBadges() as $badge ) {
				$this->graph->addResource( $pageResourse, self::WIKIBASE_BADGE_QNAME, $this->getEntityQName( self::NS_ENTITY, $badge ) );
			}
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
			$statementList = $entity->getStatements();
			if ( $this->shouldProduce( RdfSerializer::PRODUCE_TRUTHY_STATEMENTS ) ) {
				foreach ( $statementList->getBestStatementPerProperty() as $statement ) {
					$this->addMainSnak( $entityId, $statement, true );
				}
			}

			if ( $this->shouldProduce( RdfSerializer::PRODUCE_ALL_STATEMENTS ) ) {
				foreach ( $statementList as $statement ) {
					$this->addStatement( $entityId, $statement );
				}
			}
		}
	}

	/**
	 * Adds the given Statement from the given Entity to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 * @param boolean Is this producing "truthy" or full-form statement?
	 */
	private function addStatement( EntityId $entityId, Statement $statement ) {
		$this->addMainSnak( $entityId, $statement, false );

		if ( $this->shouldProduce( RdfSerializer::PRODUCE_QUALIFIERS ) ) {
			// this assumes statement was added by addMainSnak
			$statementResource = $this->getStatementResource( $statement );
			foreach ( $statement->getQualifiers() as $q ) {
				$this->addSnak( $statementResource, $q, self::NS_QUALIFIER );
			}
		}

		if ( $this->shouldProduce( RdfSerializer::PRODUCE_REFERENCES ) ) {
			$statementResource = $this->getStatementResource( $statement );
			foreach ( $statement->getReferences() as $ref ) {
				$refResource = $this->getReferenceResource( $ref );
				$this->graph->addResource( $statementResource, self::PROV_QNAME, $refResource );
				foreach ( $ref->getSnaks() as $refSnak ) {
					$this->addSnak( $refResource, $refSnak, self::NS_VALUE );
				}
			}
		}
	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 * @param boolean Is this producing "truthy" or full-form statement?
	 */
	private function addMainSnak( EntityId $entityId, Statement $statement, $truthy ) {
		$snak = $statement->getMainSnak();

		$entityResource = $this->getEntityResource( $entityId );

		if ( $truthy ) {
			$this->addSnak( $entityResource, $snak, self::NS_DIRECT_CLAIM, true); // simple value here
		} else {
			$propertyQName = $this->getEntityQName( self::NS_ENTITY, $snak->getPropertyId() );
			$statementResource = $this->getStatementResource( $statement );
			$this->graph->addResource( $entityResource, $propertyQName, $statementResource );
			$this->addSnak( $statementResource, $snak, self::NS_VALUE );
			if ( $this->shouldProduce( RdfSerializer::PRODUCE_PROPERTIES ) ) {
				$this->entityMentioned( $snak->getPropertyId() );
			}
			$rank = $statement->getRank();
			if( !empty(self::$rank_map[$rank]) ) {
				// TODO: think if we need to record normal rank. It would be more compact if we didn't.
				$this->graph->addResource( $statementResource, self::WIKIBASE_RANK_QNAME,  self::$rank_map[$rank] );
			}
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
		return $this->graph->resource( $statementQName, array (
				self::WIKIBASE_STATEMENT_QNAME
		) );
	}

	/**
	 * Returns a resource representing the given Reference.
	 *
	 * @param Reference $ref
	 *
	 * @return EasyRDF_Resource
	 */
	private function getReferenceResource( Reference $ref ) {
		$refQName = $this->getReferenceQName( self::NS_REFERENCE, $ref );
		return $this->graph->resource( $refQName, array (
				self::WIKIBASE_REFERENCE_QNAME
		) );
	}

	/**
	 * Adds the given SnakObject to the RDF graph.
	 *
	 * @param EasyRdf_Resource $target Target node to which we're attaching the snak
	 * @param SnakObject $snak Snak object
	 * @param string $claimType Type of the claim for which we're using the snak
	 */
	private function addSnak( EasyRdf_Resource $target, SnakObject $snak, $claimType, $simpleValue = false ) {
		$propertyId = $snak->getPropertyId();
		switch ( $snak->getType() ) {
			case 'value' :
				$this->addStatementValue( $target, $propertyId, $snak->getDataValue(), $claimType, $simpleValue );
				break;
			case 'somevalue' :
				$propertyValueQName = $this->getEntityQName( $claimType, $propertyId );
				$this->graph->addResource( $target, $propertyValueQName, self::WIKIBASE_SOMEVALUE_QNAME );
				break;
			case 'novalue' :
				$propertyValueQName = $this->getEntityQName( $claimType, $propertyId );
				$this->graph->addResource( $target, $propertyValueQName, self::WIKIBASE_NOVALUE_QNAME );
				break;
		}
	}

	/**
	 * Created full data value
	 * @param \EasyRdf_Resource $target Place to attach the value
	 * @param string $propertyValueQName Relationship name
	 * @param DataValue $value
	 * @param array $props List of properties
	 */
	private function addExpandedValue( \EasyRdf_Resource $target, $propertyValueQName, DataValue $value, array $props) {
		$node = $this->graph->resource( self::NS_VALUE . ":" . $value->getHash(), self::WIKIBASE_VALUE_QNAME );
		$this->graph->addResource( $target, $propertyValueQName."-value", $node);
		foreach( $props as $prop => $type ) {
			$getter = "get" . ucfirst( $prop );
			$data = $value->$getter();
			if ( $type == 'url' ) {
				$this->graph->addResource( $node, $this->getEntityTypeQName( $prop ), $data );
				continue;
			}
			$this->graph->addLiteral( $node, $this->getEntityTypeQName( $prop ),
					new \EasyRdf_Literal( $data, null, $type ) );
		}
	}

	/**
	 * Property types local cache
	 * @var array
	 */
	private static $propTypes = array();

	/**
	 * Fetch the data type for property
	 * @param EntityId $propertyId
	 * @param string $typeId
	 * @return string
	 */
	private function getDataType(EntityId $propertyId, $typeId) {
		$id = $propertyId->getPrefixedId();
		if( !isset(self::$propTypes[$id]) ) {
			$property = $this->entityLookup->getEntity( $propertyId );
			if( empty($property) ) {
				$dataType = $typeId;
			} else {
				$dataType = $property->getDataTypeId();
			}
			self::$propTypes[$id] = $dataType;
		}
		return self::$propTypes[$id];
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param Statement $statement
	 * @param EntityId $propertyId
	 * @param DataValue $value
	 * @param string $claimType Type of the claim for which we're using the snak
	 */
	private function addStatementValue( EasyRdf_Resource $target, EntityId $propertyId, DataValue $value, $claimType, $simpleValue = false ) {
		$propertyValueQName = $this->getEntityQName( $claimType, $propertyId );

		$typeId = $value->getType();
		$dataType = $this->getDataType( $propertyId, $typeId );

		$typeId = "addStatementFor".preg_replace( '/[^\w]/', '', ucwords( $typeId ) );
		if( !is_callable( array($this, $typeId ) ) ) {
			wfDebug( __METHOD__ . ": Unsupported data type: $typeId\n" );
		} else {
			$this->$typeId( $target, $propertyValueQName, $dataType, $value, $simpleValue );
		}
		// TODO: add special handling like in WDTK?
		// https://github.com/Wikidata/Wikidata-Toolkit/blob/master/wdtk-rdf/src/main/java/org/wikidata/wdtk/rdf/extensions/SimpleIdExportExtension.java
	}

	/**
	 * Adds specific value
	 *
	 * @param Statement $statement
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param EntityIdValue $value
	 * @param string $claimType Type of the claim for which we're using the snak
	 */
	private function addStatementForWikibaseEntityid( EasyRdf_Resource $target, $propertyValueQName, $dataType,
			EntityIdValue $value, $simpleValue = false ) {
		$entityId = $value->getValue()->getEntityId();
		$entityQName = $this->getEntityQName( self::NS_ENTITY, $entityId );
		$entityResource = $this->graph->resource( $entityQName );
		$this->graph->addResource( $target, $propertyValueQName, $entityResource );
		// TODO: should we do this? $this->entityMentioned( $entityId );
	}

	/**
	 * Adds specific value
	 *
	 * @param Statement $statement
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param StringValue $value
	 * @param string $claimType Type of the claim for which we're using the snak
	 */
	private function addStatementForString(EasyRdf_Resource $target, $propertyValueQName, $dataType,
			StringValue $value, $simpleValue = false ) {
		if ( $dataType == 'commonsMedia' ) {
			$this->graph->addResource($target, $propertyValueQName, $this->getCommonsURI( $value->getValue() ) );
		} elseif ( $dataType == 'url' ) {
			$this->graph->addResource($target, $propertyValueQName, $value->getValue() );
		} else {
			$this->graph->addLiteral($target, $propertyValueQName, new EasyRdf_Literal( $value->getValue() ) );
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param Statement $statement
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param MonolingualTextValue $value
	 * @param string $claimType Type of the claim for which we're using the snak
	 */
	private function addStatementForMonolingualtext(EasyRdf_Resource $target, $propertyValueQName, $dataType,
			MonolingualTextValue $value, $simpleValue = false ) {
		$this->graph->addLiteral( $target, $propertyValueQName, $value->getText(), $value->getLanguageCode() );
	}

	/**
	 * Adds specific value
	 *
	 * @param Statement $statement
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param TimeValue $value
	 * @param string $claimType Type of the claim for which we're using the snak
	 */
	private function addStatementForTime(EasyRdf_Resource $target, $propertyValueQName, $dataType,
			TimeValue $value, $simpleValue = false ) {
		// TODO: we may want to deal with Julian dates here?
		$this->graph->addLiteral( $target, $propertyValueQName, new \EasyRdf_Literal_DateTime( $value->getTime() ) );
		if ( !$simpleValue  && $this->shouldProduce( RdfSerializer::PRODUCE_FULL_VALUES ) ) {
			$this->addExpandedValue( $target, $propertyValueQName, $value,
					array(  'time' => 'xsd:dateTime',
							// TODO: eventually use identifier here
							'precision' => 'xsd:integer',
							'timezone' => 'xsd:integer',
							'calendarModel' => 'url',
// TODO: not used currently
//							'before' => 'xsd:dateTime',
// 							'after'=> 'xsd:dateTime',
					)
			);
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param Statement $statement
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param QuantityValue $value
	 * @param string $claimType Type of the claim for which we're using the snak
	 */
	private function addStatementForQuantity(EasyRdf_Resource $target, $propertyValueQName, $dataType,
			QuantityValue $value, $simpleValue = false ) {
		$this->graph->addLiteral( $target, $propertyValueQName, new \EasyRdf_Literal_Decimal( $value->getAmount() ) );
		if ( !$simpleValue  && $this->shouldProduce( RdfSerializer::PRODUCE_FULL_VALUES ) ) {
			$this->addExpandedValue( $target, $propertyValueQName, $value,
					array(  'amount' => 'xsd:decimal',
							'upperBound' => 'xsd:decimal',
							'lowerBound' => 'xsd:decimal',
							'unit' => null,
						)
			);
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param Statement $statement
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param GlobeCoordinateValue $value
	 * @param string $claimType Type of the claim for which we're using the snak
	 */
	private function addStatementForGlobecoordinate(EasyRdf_Resource $target, $propertyValueQName, $dataType,
			GlobeCoordinateValue $value, $simpleValue = false ) {
		$this->graph->addLiteral( $target, $propertyValueQName, new EasyRdf_Literal( "Point({$value->getLatitude()} {$value->getLongitude()})", null, self::NS_GEO . ":wktLiteral" ) );
		if ( !$simpleValue  && $this->shouldProduce( RdfSerializer::PRODUCE_FULL_VALUES ) ) {
			$this->addExpandedValue( $target, $propertyValueQName, $value,
					array(  'latitude' => 'xsd:decimal',
							'longitude' => 'xsd:decimal',
							'precision' => 'xsd:decimal',
							'globe' => 'url',
						)
			);
		}
	}

	/**
	 * Add stubs for any entities that were previously mentioned (e.g.
	 * as properties
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
				if ( !$entity ) {
					continue;
				}
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

		if ( $this->shouldProduce( RdfSerializer::PRODUCE_SITELINKS ) && $entity instanceof Item ) {
			$this->addSiteLinks( $entity );
		}

		if ( $this->shouldProduce( RdfSerializer::PRODUCE_ALL_STATEMENTS | RdfSerializer::PRODUCE_TRUTHY_STATEMENTS ) && $entity instanceof EntityDocument ) {
			$this->addStatements( $entity );
		}
	}

	/**
	 * Adds stub information for the given Entity to the RDF graph.
	 * Stub information means meta information and labels.
	 *
	 * @param Entity $entity
	 */
	private function addEntityStub( Entity $entity ) {
		$this->addEntityMetaData( $entity );
		$this->addLabels( $entity );
		$this->addDescriptions( $entity );
	}

	/**
	 * Create header structure for the dump
	 */
	public function addDumpHeader() {
		// TODO: this should point to "this document"
		$dataResource = $this->graph->resource( $this->getEntityTypeQName('Dump') );
		$this->graph->addResource( $dataResource, 'rdf:type', self::NS_SCHEMA_ORG . ":Dataset" );
		$this->graph->addResource( $dataResource, self::NS_CC . ':license', self::LICENSE );
		$this->graph->addLiteral( $dataResource, self::NS_SCHEMA_ORG . ':softwareVersion', self::FORMAT_VERSION );
		$this->graph->addLiteral( $dataResource, self::NS_SCHEMA_ORG . ':dateModified', new EasyRdf_Literal( gmdate('Y-m-d\TH:i:s\Z'), null, 'xsd:dateTime' ) );
	}
}
