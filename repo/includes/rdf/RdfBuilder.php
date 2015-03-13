<?php

namespace Wikibase;

use DataValues\DataValue;
use SiteList;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyValueSnak;
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
use Wikibase\RDF\RdfWriter;

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

	//FIXME: this is the wikibase ontology, NOT the wikidata ontology!
	const ONTOLOGY_BASE_URI = 'http://www.wikidata.org/ontology';
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

	const COMMONS_URI = 'http://commons.wikimedia.org/wiki/Special:FilePath/'; //FIXME: get from config
	const GEO_URI = 'http://www.opengis.net/ont/geosparql#';
	const PROV_URI = 'http://www.w3.org/ns/prov#';
	// TODO: make the license settable
	const LICENSE = 'http://creativecommons.org/publicdomain/zero/1.0/';

	public static $rankMap = array(
		Statement::RANK_DEPRECATED => 'DeprecatedRank',
		Statement::RANK_NORMAL => 'NormalRank',
		Statement::RANK_PREFERRED => 'PreferredRank',
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
	 * @var RdfWriter
	 */
	private $documentWriter;

	/**
	 * @var RdfWriter
	 */
	private $headerWriter;

	/**
	 * @var RdfWriter
	 */
	private $entityWriter;

	/**
	 * @var RdfWriter
	 */
	private $sitelinkWriter;

	/**
	 * @var RdfWriter
	 */
	private $statementWriter;

	/**
	 * @var RdfWriter
	 */
	private $referenceWriter;

	/**
	 * @var RdfWriter
	 */
	private $valueWriter;

	/**
	 *
	 * @param SiteList $sites
	 * @param string $baseUri
	 * @param string $dataUri
	 * @param EntityLookup $entityLookup
	 * @param integer $flavor
	 * @param RdfWriter $writer
	 */
	public function __construct( SiteList $sites, $baseUri, $dataUri,
			EntityLookup $entityLookup, $flavor, RdfWriter $writer ) {

		$this->documentWriter = $writer;

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
			$this->documentWriter->prefix( $gname, $uri );
		}

		$this->headerWriter = $this->documentWriter->sub();
		$this->entityWriter = $this->documentWriter->sub();
		$this->sitelinkWriter = $this->documentWriter->sub();
		$this->statementWriter = $this->documentWriter->sub();
		$this->referenceWriter = $this->documentWriter->sub();
		$this->valueWriter = $this->documentWriter->sub();
	}

	/**
	 * Returns the RDF generated by the builder
	 *
	 * @return string RDF
	 */
	public function getRDF() {
		return $this->documentWriter->drain();
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
	 * Returns a local name for the given entity using the given prefix.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function getEntityLName( EntityId $entityId ) {
		return ucfirst( $entityId->getSerialization() );
	}

	/**
	 * Returns a qname for the given statement using the given prefix.
	 *
	 * @param Statement $statement
	 *
	 * @return string
	 */
	private function getStatementLName( Statement $statement ) {
		return preg_replace( '/[^\w-]/', '-', $statement->getGuid() );
	}

	/**
	 * Returns a qname for the given reference using the given prefix.
	 *
	 * @param Reference $ref
	 *
	 * @return string
	 */
	private function getReferenceLName( Reference $ref ) {
		return $ref->getHash();
	}

	/**
	 * Returns a name for the given entity type.
	 * For well known types, these names refer to classes from the Wikibase ontology.
	 *
	 * @param $type
	 *
	 * @return string
	 */
	private function getEntityTypeName( $type ) {
		return ucfirst( $type );
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

		$this->entityWriter->about( self::NS_DATA, $entityId )
			->say( self::NS_SCHEMA_ORG, 'version' )->value( $revision, 'xsd', 'integer' )
			->say( self::NS_SCHEMA_ORG, 'dateModified' )->value( $timestamp, 'xsd', 'dateTime' );

		// TODO: versioned data URI, current-version-of
	}

	/**
	 * Adds meta-information about an entity (such as the ID and type) to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	private function addEntityMetaData( Entity $entity ) {
		$entityLName = $this->getEntityLName( $entity->getId() );
		$this->entityWriter->about( self::NS_ENTITY, $entityLName )
			->a( self::NS_ONTOLOGY, $this->getEntityTypeName( $entity->getType() ) );

		$this->entityWriter->about( self::NS_DATA, $entity->getId() )
			->a( self::NS_SCHEMA_ORG, "Dataset" )
			->say( self::NS_SCHEMA_ORG, 'about' )->is( self::NS_ENTITY, $entityLName );

		if( $this->shouldProduce( RdfProducer::PRODUCE_VERSION_INFO ) ) {
			// Dumps don't need version/license info for each entity, since it is included in the dump header
			$this->entityWriter
				->say( self::NS_CC, 'license' )->is( self::LICENSE ) 
				->say( self::NS_SCHEMA_ORG, 'softwareVersion' )->value( self::FORMAT_VERSION );
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
		$entityLName = $this->getEntityLName( $entity->getId() );

		foreach ( $entity->getLabels() as $languageCode => $labelText ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			$this->entityWriter->about( self::NS_ENTITY, $entityLName )
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
	private function addDescriptions( Entity $entity ) {
		$entityLName = $this->getEntityLName( $entity->getId() );

		foreach ( $entity->getDescriptions() as $languageCode => $description ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			$this->entityWriter->about( self::NS_ENTITY, $entityLName )
				->say( self::NS_SCHEMA_ORG, 'description' )->text( $description, $languageCode );
		}
	}

	/**
	 * Adds the aliases of the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	private function addAliases( Entity $entity ) {
		$entityLName = $this->getEntityLName( $entity->getId() );

		foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			foreach ( $aliases as $alias ) {
				$this->entityWriter->about( self::NS_ENTITY, $entityLName )
					->say( self::NS_SKOS, 'altLabel' )->text( $alias, $languageCode );
			}
		}
	}

	/**
	 * Adds the site links of the given item to the RDF graph.
	 *
	 * @param Item $item
	 */
	private function addSiteLinks( Item $item ) {
		$entityLName = $this->getEntityLName( $item->getId() );

		/** @var SiteLink $siteLink */
		foreach ( $item->getSiteLinkList() as $siteLink ) {
			$site = $this->sites->getSite( $siteLink->getSiteId() );

			$languageCode = $site->getLanguageCode();

			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			// XXX: ideally, we'd use https if the target site supports it.
			$baseUrl = $site->getPageUrl( $siteLink->getPageName() );
			$url = wfExpandUrl( $baseUrl, PROTO_HTTP );

			$this->sitelinkWriter->about( $url )
				->a( self::NS_SCHEMA_ORG, 'Article' )
				->say( self::NS_SCHEMA_ORG, 'about' )->is( self::NS_ENTITY, $entityLName )
				->say( self::NS_SCHEMA_ORG, 'inLanguage' )->text( $languageCode );

			foreach ( $siteLink->getBadges() as $badge ) {
				$this->sitelinkWriter
					->say( self::NS_ONTOLOGY, 'Badge' )
						->is( self::NS_ENTITY, $this->getEntityLName( $badge ) );
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
		$statementLName = $this->getStatementLName( $statement );
		$this->statementWriter->about( self::NS_STATEMENT, $statementLName )
			->a( self::NS_ONTOLOGY, 'Statement' );

		$this->addMainSnak( $entityId, $statement, false );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_QUALIFIERS ) ) {
			// this assumes statement was added by addMainSnak
			foreach ( $statement->getQualifiers() as $q ) {
				$this->addSnak( $this->statementWriter, $q, self::NS_QUALIFIER );
			}
		}

		if ( $this->shouldProduce( RdfProducer::PRODUCE_REFERENCES ) ) {
			foreach ( $statement->getReferences() as $ref ) { //FIXME: split body into separate method
				//FIXME: the reference mechanism is prone to duplication. De-dupe by hash.
				$refLName = $this->getReferenceLName( $ref );
				$this->referenceWriter->about( self::NS_REFERENCE, $refLName )
					->a( self::NS_ONTOLOGY, 'Reference' );

				$this->statementWriter->about( self::NS_STATEMENT, $statementLName )
					->say( self::NS_PROV, 'wasDerivedFrom' )->is( self::NS_REFERENCE, $refLName );

				foreach ( $ref->getSnaks() as $refSnak ) {
					$this->addSnak( $this->referenceWriter, $refSnak, self::NS_VALUE );
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

		$entityLName = $this->getEntityLName( $entityId );

		if ( $truthy ) { //FIXME: have a separate method for each mode.
			$this->entityWriter->about( self::NS_ENTITY, $entityLName );
			$this->addSnak( $this->entityWriter, $snak, self::NS_DIRECT_CLAIM, true ); // simple value here
		} else {
			$propertyLName = $this->getEntityLName( $snak->getPropertyId() );
			$statementLName = $this->getStatementLName( $statement );

			$this->entityWriter->about( self::NS_ENTITY,  $entityLName )
				->say( self::NS_ENTITY, $propertyLName )->is( self::NS_STATEMENT, $statementLName );

			$this->statementWriter->about( self::NS_STATEMENT, $statementLName );
			$this->addSnak( $this->statementWriter, $snak, self::NS_VALUE );

			if ( $this->shouldProduce( RdfProducer::PRODUCE_PROPERTIES ) ) {
				$this->entityMentioned( $snak->getPropertyId() );
			}

			$rank = $statement->getRank();
			if( isset( self::$rankMap[$rank] ) ) {
				$this->statementWriter->about( self::NS_STATEMENT, $statementLName )
					->say( self::NS_ONTOLOGY, 'Rank' )->is( self::NS_ONTOLOGY, self::$rankMap[$rank] );
			} else {
				wfLogWarning( "Unknown rank $rank encountered for $entityId:{$statement->getGuid()}" );
			}
		}
	}

	/**
	 * Adds the given Snak to the RDF graph.
	 *
	 * @param RdfWriter $writer The writer to receive the property value (must be primed to expect a predicate).
	 * @param Snak $snak Snak object
	 * @param string $propertyNamespace The property namespace for this snak
	 * @param bool $simpleValue
	 */
	private function addSnak( RdfWriter $writer, Snak $snak, $propertyNamespace, $simpleValue = false ) {
		$propertyId = $snak->getPropertyId();
		switch ( $snak->getType() ) {
			case 'value' :
				/** @var PropertyValueSnak $snak */
				$this->addStatementValue( $writer, $propertyId, $snak->getDataValue(), $propertyNamespace, $simpleValue );
				break;
			case 'somevalue' :
				$propertyValueLName = $this->getEntityLName( $propertyId );

				$writer->say( $propertyNamespace, $propertyValueLName )->is( self::NS_ONTOLOGY, 'Somevalue' );
				break;
			case 'novalue' :
				$propertyValueLName = $this->getEntityLName( $propertyId );

				$writer->say( $propertyNamespace, $propertyValueLName )->is( self::NS_ONTOLOGY, 'Novalue' );
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
	 * @return string the id of the value node, for use with the self::NS_VALUE namespace.
	 */
	private function addExpandedValue( DataValue $value, array $props ) {
		$valueLName = $value->getHash();
		$this->valueWriter->about( self::NS_VALUE, $valueLName )->a( self::NS_ONTOLOGY, 'Value' );

		foreach( $props as $prop => $type ) {
			$propLName = ucfirst( $prop );
			$getter = "get" . ucfirst( $prop );
			$data = $value->$getter();

			if ( $type == 'url' ) {
				$this->valueWriter->about( self::NS_VALUE, $valueLName )
					->say( self::NS_ONTOLOGY, $propLName )->is( $data );
				continue;
			}

			$nsType = $type === null ? null : 'xsd';
			$this->valueWriter->about( self::NS_VALUE, $valueLName )
				->say( self::NS_ONTOLOGY, $propLName )->value( $data, $nsType, $type ); //FIXME: what happens is $data is not scalar? Avoid hard crash.
		}

		return $valueLName;
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param RdfWriter $writer The writer to receive the property value (must be primed to expect a predicate).
	 * @param PropertyId $propertyId
	 * @param DataValue $value
	 * @param string $propertyNamespace The property namespace for this snak
	 * @param bool $simpleValue
	 */
	private function addStatementValue( RdfWriter $writer, PropertyId $propertyId,
			DataValue $value, $propertyNamespace, $simpleValue = false ) {
		$propertyValueLName = $this->getEntityLName( $propertyId );

		/** @var Property $property */
		$property = $this->entityLookup->getEntity( $propertyId ); //FIXME: use PropertyDataTypeLookup!
		$dataType = $property->getDataTypeId();
		$typeId = $value->getType();

		//FIXME: use a proper registry / dispatching builder
		$typeId = "addStatementFor".preg_replace( '/[^\w]/', '', ucwords( $typeId ) );

		if( !is_callable( array( $this, $typeId ) ) ) {
			wfLogWarning( __METHOD__ . ": Unsupported data type: $typeId" );
		} else {
			//TODO: RdfWriter could support aliases -> instead of passing around $propertyNamespace
			//      and $propertyValueLName, we could define an alias for that and use e.g. '%property' to refer to them.
			$this->$typeId( $writer, $propertyNamespace, $propertyValueLName, $dataType, $value, $simpleValue );
		}
		// TODO: add special handling like in WDTK?
		// https://github.com/Wikidata/Wikidata-Toolkit/blob/master/wdtk-rdf/src/main/java/org/wikidata/wdtk/rdf/extensions/SimpleIdExportExtension.java
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer The writer to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param EntityIdValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForWikibaseEntityid( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			EntityIdValue $value, $simpleValue = false ) {

		$entityId = $value->getValue()->getEntityId();
		$entityLName = $this->getEntityLName( $entityId );
		$writer->say( $propertyValueNamespace, $propertyValueLName )->is( self::NS_ENTITY, $entityLName );
		$this->entityMentioned( $entityId );
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer The writer to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param StringValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForString( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			StringValue $value, $simpleValue = false ) {
		if ( $dataType == 'commonsMedia' ) {
			$writer->say( $propertyValueNamespace, $propertyValueLName )->is( $this->getCommonsURI( $value->getValue() ) );
		} elseif ( $dataType == 'url' ) {
			$writer->say( $propertyValueNamespace, $propertyValueLName )->is( $value->getValue() );
		} else {
			$writer->say( $propertyValueNamespace, $propertyValueLName )->text( $value->getValue() );
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer The writer to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param MonolingualTextValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForMonolingualtext( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			MonolingualTextValue $value, $simpleValue = false ) {
		$writer->say( $propertyValueNamespace, $propertyValueLName )->text( $value->getText(), $value->getLanguageCode() );
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer The writer to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param TimeValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForTime( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			TimeValue $value, $simpleValue = false ) {
		// TODO: we may want to deal with Julian dates here? Chinese? Lunar?
		$timestamp = $value->getTime(); //FIXME: xsd:DateTime is too restrictive! will fail for dates out of the ISO range!
		$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $timestamp, 'xsd', 'dateTime' );

		if ( !$simpleValue && $this->shouldProduce( RdfProducer::PRODUCE_FULL_VALUES ) ) { //FIXME: register separate generators for different output flavors.

			$valueLName = $this->addExpandedValue( $value,
					array(  'time' => 'dateTime', //FIXME: only true for gregorian!
							// TODO: eventually use identifier here
							'precision' => 'integer',
							'timezone' => 'integer',
							'calendarModel' => 'url',
// TODO: not used currently
//							'before' => 'dateTime',
// 							'after'=> 'dateTime',
					)
			);

			$writer->say( $propertyValueNamespace, $propertyValueLName."-value" )->is( self::NS_VALUE, $valueLName );
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer The writer to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param QuantityValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForQuantity( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			QuantityValue $value, $simpleValue = false ) {
		$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $value->getAmount(), 'xsd', 'decimal' );

		if ( !$simpleValue && $this->shouldProduce( RdfProducer::PRODUCE_FULL_VALUES ) ) {
			$valueLName = $this->addExpandedValue( $value,
					array(  'amount' => 'decimal',
							'upperBound' => 'decimal',
							'lowerBound' => 'decimal',
							'unit' => null, //FIXME: it's a URI (or "1"), should be of type url!
						)
			);

			$writer->say( $propertyValueNamespace, $propertyValueLName."-value" )->is( self::NS_VALUE, $valueLName );
		}
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer The writer to receive the property value (must be primed to expect a predicate).
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param GlobeCoordinateValue $value
	 * @param bool $simpleValue
	 */
	private function addStatementForGlobecoordinate( RdfWriter $writer, $propertyValueNamespace, $propertyValueLName, $dataType,
			GlobeCoordinateValue $value, $simpleValue = false ) {

		$point = "Point({$value->getLatitude()} {$value->getLongitude()})";
		$writer->say( $propertyValueNamespace, $propertyValueLName )->value( $point, self::NS_GEO, "wktLiteral" );

		if ( !$simpleValue && $this->shouldProduce( RdfProducer::PRODUCE_FULL_VALUES ) ) {
			$valueLName = $this->addExpandedValue( $value,
					array(  'latitude' => 'decimal',
							'longitude' => 'decimal',
							'precision' => 'decimal',
							'globe' => 'url',
						)
			);

			$writer->say( $propertyValueNamespace, $propertyValueLName . "-value" )->is( self::NS_VALUE, $valueLName );
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
		$this->documentWriter->about( self::NS_ONTOLOGY, 'Dump' )
			->a( self::NS_SCHEMA_ORG, "Dataset" )
			->say( self::NS_CC, 'license' )->is( self::LICENSE )
			->say( self::NS_SCHEMA_ORG, 'softwareVersion' )->value( self::FORMAT_VERSION )
			->say( self::NS_SCHEMA_ORG, 'dateModified' )->value( wfTimestamp( TS_ISO_8601, $ts ), 'xsd', 'dateTime'  );
	}
}
