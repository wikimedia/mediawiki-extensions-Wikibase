<?php

namespace Wikibase;

use DataValues\DataValue;
use SiteList;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\StatementListProvider;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\DataModel\Entity\EntityIdValue;
use DataValues\TimeValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\MonolingualTextValue;
use DataValues\GlobeCoordinateValue;
use Wikibase\RDF\RdfEmitter;

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

	const COMMONS_URI = 'http://commons.wikimedia.org/wiki/Special:FilePath/'; //FIXME: get from config
	const GEO_URI = 'http://www.opengis.net/ont/geosparql#';
	const PROV_URI = 'http://www.w3.org/ns/prov#';
	// TODO: make the license settable
	const LICENSE = 'http://creativecommons.org/publicdomain/zero/1.0/';

	public static $rankMap = array(
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
	 * @var RdfEmitter
	 */
	private $documentEmitter;

	/**
	 * @var RdfEmitter
	 */
	private $headerEmitter;

	/**
	 * @var RdfEmitter
	 */
	private $entityEmitter;

	/**
	 * @var RdfEmitter
	 */
	private $sitelinkEmitter;

	/**
	 * @var RdfEmitter
	 */
	private $statementEmitter;

	/**
	 * @var RdfEmitter
	 */
	private $referenceEmitter;

	/**
	 * @var RdfEmitter
	 */
	private $valueEmitter;

	/**
	 *
	 * @param SiteList $sites
	 * @param string $baseUri
	 * @param string $dataUri
	 * @param EntityLookup $entityLookup
	 * @param integer $flavor
	 * @param RdfEmitter $emitter
	 */
	public function __construct( SiteList $sites, $baseUri, $dataUri,
			EntityLookup $entityLookup, $flavor, RdfEmitter $emitter ) {

		$this->documentEmitter = $emitter;

		$this->sites = $sites;
		$this->baseUri = $baseUri;
		$this->dataUri = $dataUri;
		$this->entityLookup = $entityLookup;
		$this->produceWhat = $flavor; //FIXME: use strategy and/or decorator pattern instead!

		$this->namespaces = array (
				'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
				'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
				'xsd' => 'http://www.w3.org/2001/XMLSchema#',
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
			$this->documentEmitter->prefix( $gname, $uri );
		}

		$this->headerEmitter = $this->documentEmitter->sub();
		$this->entityEmitter = $this->documentEmitter->sub();
		$this->sitelinkEmitter = $this->documentEmitter->sub();
		$this->statementEmitter = $this->documentEmitter->sub();
		$this->referenceEmitter = $this->documentEmitter->sub();
		$this->valueEmitter = $this->documentEmitter->sub();
	}

	/**
	 * Returns the RDF generated by the builder
	 *
	 * @return string RDF
	 */
	public function getRDF() {
		return $this->documentEmitter->drain();
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
	 * @param EntityId $entityId
	 * @param string $ns use a self::NS_* constant
	 *
	 * @return string
	 */
	public function getEntityQName( EntityId $entityId, $ns = self::NS_ENTITY ) {
		return $ns . ':' . ucfirst( $entityId->getSerialization() );
	}

	/**
	 * Returns a qname for the given statement using the given prefix.
	 *
	 * @param Statement $statement
	 * @param string $ns use a self::NS_* constant, usually self::NS_STATEMENT
	 *
	 * @return string
	 */
	private function getStatementQName( Statement $statement, $ns = self::NS_STATEMENT ) {
		return $ns . ':' . preg_replace( '/[^\w-]/', '-', $statement->getGuid() );
	}

	/**
	 * Returns a qname for the given reference using the given prefix.
	 *
	 * @param Reference $ref
	 * @param string $ns use a self::NS_* constant, usually self::NS_REFERENCE
	 *
	 * @return string
	 */
	private function getReferenceQName( Reference $ref, $ns = self::NS_REFERENCE ) {
		return $ns . ':' . $ref->getHash();
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
		return self::COMMONS_URI . rawurlencode( $file );
	}

	/**
	 * Should we produce this aspect?
	 *
	 * @param integer $what
	 * @return boolean
	 */
	private function shouldProduce( $what ) {
		return ( $this->produceWhat & $what ) != 0;
	}

	/**
	 * Gets a URL of the rdf description of the given entity
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function getDataQName( EntityId $entityId ) {
		//FIXME: use EntityTitleLookup / Title::getFullText()
		return self::NS_DATA . ':' . ucfirst( $entityId->getSerialization() );
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
		$timestamp = wfTimestamp( TS_ISO_8601, $timestamp );

		$this->entityEmitter->about( $this->getDataQName( $entityId ) )
			->say( self::NS_SCHEMA_ORG . ':version' )->value( $revision, 'xsd:integer' )
			->say( self::NS_SCHEMA_ORG . ':dateModified' )->value( $timestamp, 'xsd:dateTime' );

		// TODO: versioned data URI, current-version-of
	}

	/**
	 * Adds meta-information about an entity (such as the ID and type) to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	private function addEntityMetaData( Entity $entity ) {
		$entityQName = $this->getEntityQName( $entity->getId() );
		$this->entityEmitter->about( $entityQName )
			->say( 'rdf:type' )->is( $this->getEntityTypeQName( $entity->getType() ) );

		$this->entityEmitter->about( $this->getDataQName( $entity->getId() ) )
			->say( 'rdf:type' )->is( self::NS_SCHEMA_ORG . ":Dataset" )
			->say( self::NS_SCHEMA_ORG . ':about' )->is( $entityQName );

		if( $this->shouldProduce( RdfProducer::PRODUCE_VERSION_INFO ) ) { //FIXME: why should this be optional?
			$this->entityEmitter
				->say( self::NS_CC . ':license' ) ->is( self::LICENSE ) //FIXME: the license should always be included.
				->say( self::NS_SCHEMA_ORG . ':softwareVersion' ) ->value( self::FORMAT_VERSION );
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
		$entityQName = $this->getEntityQName( $entity->getId() );

		foreach ( $entity->getLabels() as $languageCode => $labelText ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			//FIXME: RdfEmitter needs to optimize for repeated about() calls for the same resource!
			$this->entityEmitter->about( $entityQName )
				->say( 'rdfs:label' )->text( $labelText, $languageCode )
				->say( self::NS_SKOS . ':prefLabel' )->text( $labelText, $languageCode )
				->say( self::NS_SCHEMA_ORG . ':name' )->text( $labelText, $languageCode );

			//TODO: vocabs to use for labels should be configurable
		}
	}

	/**
	 * Adds the descriptions of the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	private function addDescriptions( Entity $entity ) {
		$entityQName = $this->getEntityQName( $entity->getId() );

		foreach ( $entity->getDescriptions() as $languageCode => $description ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			//FIXME: RdfEmitter needs to optimize for repeated about() calls for the same resource!
			$this->entityEmitter->about( $entityQName )
				->say( self::NS_SCHEMA_ORG . ':description' )->text( $description, $languageCode );
		}
	}

	/**
	 * Adds the aliases of the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	private function addAliases( Entity $entity ) {
		$entityQName = $this->getEntityQName( $entity->getId() );

		foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			foreach ( $aliases as $alias ) {
				//FIXME: RdfEmitter needs to optimize for repeated about() calls for the same resource!
				$this->entityEmitter->about( $entityQName )
					->say( self::NS_SKOS . ':altLabel' )->text( $alias, $languageCode );
			}
		}
	}

	/**
	 * Adds the site links of the given item to the RDF graph.
	 *
	 * @param Item $item
	 */
	private function addSiteLinks( Item $item ) {
		$entityQName = $this->getEntityQName( $item->getId() );

		foreach ( $item->getSiteLinkList() as $siteLink ) {
			$site = $this->sites->getSite( $siteLink->getSiteId() );

			$languageCode = $site->getLanguageCode();

			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			// XXX: ideally, we'd use https if the target site supports it.
			$baseUrl = $site->getPageUrl( $siteLink->getPageName() );
			$url = wfExpandUrl( $baseUrl, PROTO_HTTP );

			$this->sitelinkEmitter->about( $url )
				->say( 'rdf:type' )->is( self::NS_SCHEMA_ORG . ':Article' )
				->say( self::NS_SCHEMA_ORG . ':about' )->is( $entityQName )
				->say( self::NS_SCHEMA_ORG . ':inLanguage' )->text( $languageCode );
				
			foreach ( $siteLink->getBadges() as $badge ) {
				$this->sitelinkEmitter->say( self::WIKIBASE_BADGE_QNAME )->is( $this->getEntityQName( self::NS_ENTITY, $badge ) );
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
			if ( $this->shouldProduce( RdfProducer::PRODUCE_TRUTHY_STATEMENTS ) ) {
				foreach ( $statementList->getBestStatementPerProperty() as $statement ) {
					$this->addMainSnak( $entityId, $statement, true );
				}
			}

			if ( $this->shouldProduce( RdfProducer::PRODUCE_ALL_STATEMENTS ) ) {
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
	 */
	private function addStatement( EntityId $entityId, Statement $statement ) {
		$statementQName = $this->getStatementQName( $statement );
		$this->statementEmitter->about( $statementQName )
			->say( 'a' )->is( self::WIKIBASE_STATEMENT_QNAME );

		$this->addMainSnak( $entityId, $statement, false );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_QUALIFIERS ) ) {
			// this assumes statement was added by addMainSnak
			foreach ( $statement->getQualifiers() as $q ) {
				$this->addSnak( $this->statementEmitter, $q, self::NS_QUALIFIER );
			}
		}

		if ( $this->shouldProduce( RdfProducer::PRODUCE_REFERENCES ) ) {
			foreach ( $statement->getReferences() as $ref ) { //FIXME: split body into separate method
				$refQName = $this->getReferenceQName( $ref );
				$this->referenceEmitter->about( $statementQName )
					->say( 'a' )->is( self::WIKIBASE_STATEMENT_QNAME );

				$this->statementEmitter->about( $statementQName ) //FIXME: optimized for repeated about()
					->say( self::PROV_QNAME )->is( $refQName );

				foreach ( $ref->getSnaks() as $refSnak ) {
					$this->addSnak( $this->referenceEmitter, $refSnak, self::NS_VALUE );
				}
			}
		}
	}

	/**
	 * Adds the given Statement's main Snak to the RDF graph.
	 *
	 * @param EntityId $entityId
	 * @param Statement $statement
	 * @param boolean $truthy Is this producing "truthy" or full-form statement?
	 */
	private function addMainSnak( EntityId $entityId, Statement $statement, $truthy ) {
		$snak = $statement->getMainSnak();

		$entityQName = $this->getEntityQName( $entityId );

		if ( $truthy ) { //FIXME: have a separate method for each mode.
			$this->entityEmitter->about( $entityQName );
			$this->addSnak( $this->entityEmitter, $snak, self::NS_DIRECT_CLAIM, true ); // simple value here
		} else {
			$propertyQName = $this->getEntityQName( $snak->getPropertyId() );
			$statementQName = $this->getStatementQName( $statement );

			$this->entityEmitter->about( $entityQName )
				->say( $propertyQName )->is( $statementQName );

			$this->statementEmitter->about( $statementQName );
			$this->addSnak( $this->statementEmitter, $snak, self::NS_VALUE );

			if ( $this->shouldProduce( RdfProducer::PRODUCE_PROPERTIES ) ) { //FIXME: get rid of PRODUCE_PROPERTIES, add an option to resolveMentionedEntities instead.
				$this->entityMentioned( $snak->getPropertyId() );
			}

			$rank = $statement->getRank();
			if( !empty( self::$rankMap[$rank] ) ) { //FIXME: use isset instead of empty.
				$this->statementEmitter->about( $statementQName )
					->say( self::WIKIBASE_RANK_QNAME )->is( self::$rankMap[$rank] );
			} //FIXME: else wfLogWarning
		}
	}

	/**
	 * Adds the given Snak to the RDF graph.
	 *
	 * @param RdfEmitter $emitter The emitter to receive the property value (must be primed to expect a predicate).
	 * @param Snak $snak Snak object
	 * @param string $propertyNamespace The property namespace for this snak
	 * @param bool $simpleValue
	 */
	private function addSnak( RdfEmitter $emitter, Snak $snak, $propertyNamespace, $simpleValue = false ) {
		$propertyId = $snak->getPropertyId();
		switch ( $snak->getType() ) {
			case 'value' :
				$this->addStatementValue( $emitter, $propertyId, $snak->getDataValue(), $propertyNamespace, $simpleValue );
				break;
			case 'somevalue' :
				$propertyValueQName = $this->getEntityQName( $propertyId, $propertyNamespace );

				$emitter->say( $propertyValueQName )->is( self::WIKIBASE_SOMEVALUE_QNAME );
				break;
			case 'novalue' :
				$propertyValueQName = $this->getEntityQName( $propertyId, $propertyNamespace );

				$emitter->say( $propertyValueQName )->is( self::WIKIBASE_NOVALUE_QNAME );
				break;
			default:
				throw new \InvalidArgumentException( 'Unknown snak type: ' . $snak->getType() );
		}
	}

	/**
	 * Created full data value
	 *
	 * @param DataValue $value
	 * @param array $props List of properties
	 *
	 * @return string the QName of the value node
	 */
	private function addExpandedValue( DataValue $value, array $props ) {
		$node = $this->valueEmitter->blank(); //TODO: avoid bnode, use a hash based qname, like WDT does
		$this->valueEmitter->about( $node )
			->say( 'a' )->is( self::WIKIBASE_VALUE_QNAME );

		foreach( $props as $prop => $type ) {
			$getter = "get" . ucfirst( $prop );
			$data = $value->$getter();
			if ( $type == 'url' ) { //FIXME: use the logic from addStatementValue recursively here.
				$this->valueEmitter->about( $node )
					->say( $this->getEntityTypeQName( $prop ) )->is( $data );
				continue;
			}

			$this->valueEmitter->about( $node )
				->say( $this->getEntityTypeQName( $prop ) )->value( $data, $type ); //FIXME: what happens is $data is not scalar? Avoid hard crash.
		}

		return $node;
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param RdfEmitter $emitter The emitter to receive the property value (must be primed to expect a predicate).
	 * @param PropertyId $propertyId
	 * @param DataValue $value
	 * @param string $propertyNamespace The property namespace for this snak
	 * @param bool $simpleValue
	 */
	private function addStatementValue( RdfEmitter $emitter, PropertyId $propertyId,
			DataValue $value, $propertyNamespace, $simpleValue = false ) {
		$propertyValueQName = $this->getEntityQName( $propertyId, $propertyNamespace );

		$property = $this->entityLookup->getEntity( $propertyId ); //FIXME: use PropertyDataTypeLookup!
		$dataType = $property->getDataTypeId();
		$typeId = $value->getType();

		//FIXME: use a proper registry / dispatching builder
		$typeId = "addStatementFor".preg_replace( '/[^\w]/', '', ucwords( $typeId ) );

		if( !is_callable( array( $this, $typeId ) ) ) {
			wfLogWarning( __METHOD__ . ": Unsupported data type: $typeId" );
		} else {
			$this->$typeId( $emitter, $propertyValueQName, $dataType, $value, $simpleValue );
		}
		// TODO: add special handling like in WDTK?
		// https://github.com/Wikidata/Wikidata-Toolkit/blob/master/wdtk-rdf/src/main/java/org/wikidata/wdtk/rdf/extensions/SimpleIdExportExtension.java
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfEmitter $emitter The emitter to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param EntityIdValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForWikibaseEntityid( RdfEmitter $emitter, $propertyValueQName, $dataType,
			EntityIdValue $value, $simpleValue = false ) {

		$entityId = $value->getValue()->getEntityId();
		$entityQName = $this->getEntityQName( $entityId );
		$emitter->say( $propertyValueQName )->is( $entityQName );
		$this->entityMentioned( $entityId );
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfEmitter $emitter The emitter to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param StringValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForString( RdfEmitter $emitter, $propertyValueQName, $dataType,
			StringValue $value, $simpleValue = false ) {
		if ( $dataType == 'commonsMedia' ) {
			$emitter->say( $propertyValueQName )->is( $this->getCommonsURI( $value->getValue() ) );
		} elseif ( $dataType == 'url' ) {
			$emitter->say( $propertyValueQName )->is( $value->getValue() );
		} else {
			$emitter->say( $propertyValueQName )->text( $value->getValue() );
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfEmitter $emitter The emitter to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param MonolingualTextValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForMonolingualtext( RdfEmitter $emitter, $propertyValueQName, $dataType,
			MonolingualTextValue $value, $simpleValue = false ) {
		$emitter->say( $propertyValueQName )->text( $value->getText(), $value->getLanguageCode() );
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfEmitter $emitter The emitter to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param TimeValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForTime( RdfEmitter $emitter, $propertyValueQName, $dataType,
			TimeValue $value, $simpleValue = false ) {
		// TODO: we may want to deal with Julian dates here? Chinese? Lunar?
		$timestamp = $value->getTime(); //FIXME: xsd:DateTime is too restrictive! will fail for dates out of the ISO range!
		$emitter->say( $propertyValueQName )->value( $timestamp, 'xsd:dateTime' );

		if ( !$simpleValue && $this->shouldProduce( RdfProducer::PRODUCE_FULL_VALUES ) ) { //FIXME: register separate generators for different output flavors.

			$valueQName = $this->addExpandedValue( $value,
					array(  'time' => 'xsd:dateTime', //FIXME: only true for gregorian!
							// TODO: eventually use identifier here
							'precision' => 'xsd:integer',
							'timezone' => 'xsd:integer',
							'calendarModel' => 'url',
// TODO: not used currently
//							'before' => 'xsd:dateTime',
// 							'after'=> 'xsd:dateTime',
					)
			);

			$emitter->say( $propertyValueQName."-value" )->is( $valueQName );
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfEmitter $emitter The emitter to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param QuantityValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForQuantity( RdfEmitter $emitter, $propertyValueQName, $dataType,
			QuantityValue $value, $simpleValue = false ) {
		$emitter->say( $propertyValueQName )->value( $value->getAmount(), 'xsd:decimal' );

		if ( !$simpleValue && $this->shouldProduce( RdfProducer::PRODUCE_FULL_VALUES ) ) {
			$valueQName = $this->addExpandedValue( $value,
					array(  'amount' => 'xsd:decimal',
							'upperBound' => 'xsd:decimal',
							'lowerBound' => 'xsd:decimal',
							'unit' => null, //FIXME: it's a URI (or "1"), should be of type url!
						)
			);

			$emitter->say( $propertyValueQName."-value" )->is( $valueQName );
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfEmitter $emitter The emitter to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueQName Property name
	 * @param string $dataType Property data type
	 * @param GlobeCoordinateValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForGlobecoordinate( RdfEmitter $emitter, $propertyValueQName, $dataType,
			GlobeCoordinateValue $value, $simpleValue = false ) {

		$point = "Point({$value->getLatitude()} {$value->getLongitude()})";
		$emitter->say( $propertyValueQName )->value( $point, self::NS_GEO . ":wktLiteral" );

		if ( !$simpleValue && $this->shouldProduce( RdfProducer::PRODUCE_FULL_VALUES ) ) {
			$valueQName = $this->addExpandedValue( $value,
					array(  'latitude' => 'xsd:decimal',
							'longitude' => 'xsd:decimal',
							'precision' => 'xsd:decimal',
							'globe' => 'url',
						)
			);

			$emitter->say( $propertyValueQName."-value" )->is( $valueQName );
		}
	}

	/**
	 * Add stubs for any entities that were previously mentioned (e.g.
	 * as properties
	 * or data values).
	 *
	 * @param EntityLookup $entityLookup
	 */
	public function resolvedMentionedEntities( EntityLookup $entityLookup ) { //FIXME: needs test
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

		if ( $this->shouldProduce( RdfProducer::PRODUCE_SITELINKS ) && $entity instanceof Item ) {
			$this->addSiteLinks( $entity );
		}

		if ( $this->shouldProduce( RdfProducer::PRODUCE_ALL_STATEMENTS
				| RdfProducer::PRODUCE_TRUTHY_STATEMENTS ) && $entity instanceof EntityDocument ) {
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
	 * @param int $ts Timestamp (for testing)
	 */
	public function addDumpHeader( $ts = 0 ) {
		// TODO: this should point to "this document"
		$this->documentEmitter->about( $this->getEntityTypeQName( 'Dump' ) )
			->say( 'rdf:type' )->is( self::NS_SCHEMA_ORG . ":Dataset" )
			->say( self::NS_CC . ':license' )->is( self::LICENSE )
			->say( self::NS_SCHEMA_ORG . ':softwareVersion' )->value( self::FORMAT_VERSION )
			->say( self::NS_SCHEMA_ORG . ':dateModified' )->value( wfTimestamp( TS_ISO_8601, $ts ),'xsd:dateTime'  );
	}
}
