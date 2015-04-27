<?php

namespace Wikibase\Rdf;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\Statement;

/**
 * RDF vocabulary for use in mapping for wikibase data model.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class RdfVocabulary {

	// Change this when changing data format!
	const FORMAT_VERSION = '0.0.1';

	//FIXME: this is the wikibase ontology, NOT the wikidata ontology!
	const ONTOLOGY_BASE_URI = 'http://www.wikidata.org/ontology';
	const NS_ONTOLOGY = 'wikibase'; // wikibase ontology (shared)
	// Nodes
	const NS_ENTITY = 'wd'; // concept uris
	const NS_DATA = 'wdata'; // document uris
	const NS_STATEMENT = 'wds'; // statement
	const NS_REFERENCE = 'wdref'; // reference
	const NS_VALUE = 'wdv'; // value
	// Predicates
	const NSP_DIRECT_CLAIM = 'wdt'; // direct assertion entity -> value
	const NSP_CLAIM = 'p'; // entity -> statement
	const NSP_CLAIM_STATEMENT = 'ps'; // statement -> simple value
	const NSP_CLAIM_VALUE = 'psv'; // statement -> deep value
	const NSP_QUALIFIER = 'pq'; // statement -> qualifier
	const NSP_QUALIFIER_VALUE = 'pqv'; // statement ->  qualifier deep value
	const NSP_REFERENCE = 'pr'; // reference -> simple value
	const NSP_REFERENCE_VALUE = 'prv'; // reference -> deep value
	const NSP_NOVALUE = 'wdno'; // novalue class
	// other prefixes
	const NS_SKOS = 'skos'; // SKOS vocabulary
	const NS_SCHEMA_ORG = 'schema'; // schema.org vocabulary
	const NS_CC = 'cc'; // Creative Commons
	const NS_GEO = 'geo'; // prefix for geolocations
	const NS_PROV = 'prov'; // for provenance
	const SKOS_URI = 'http://www.w3.org/2004/02/skos/core#';
	const SCHEMA_ORG_URI = 'http://schema.org/';
	const CC_URI = 'http://creativecommons.org/ns#';
	// External URIs
	const COMMONS_URI = 'http://commons.wikimedia.org/wiki/Special:FilePath/'; //FIXME: get from config
	const GEO_URI = 'http://www.opengis.net/ont/geosparql#';
	const PROV_URI = 'http://www.w3.org/ns/prov#';
	// TODO: make the license settable
	const LICENSE = 'http://creativecommons.org/publicdomain/zero/1.0/';

	// Gregorian calendar link.
	// I'm not very happy about hardcoding it here but see no better way so far.
	// See also DataValues\TimeValue\TimeFormatter::XXX_CALENDAR constants.
	const GREGORIAN_CALENDAR = 'http://www.wikidata.org/entity/Q1985727';
	const JULIAN_CALENDAR = 'http://www.wikidata.org/entity/Q1985786';
	// Ranks
	const WIKIBASE_RANK_BEST = 'BestRank';
	public static $rankMap = array(
		Statement::RANK_DEPRECATED => 'DeprecatedRank',
		Statement::RANK_NORMAL => 'NormalRank',
		Statement::RANK_PREFERRED => 'PreferredRank',
	);
	// Value properties
	public static $claimToValue = array(
			self::NSP_CLAIM_STATEMENT => self::NSP_CLAIM_VALUE,
			self::NSP_QUALIFIER => self::NSP_QUALIFIER_VALUE,
			self::NSP_REFERENCE => self::NSP_REFERENCE_VALUE,
	);

	/**
	 * Map of qnames to namespace URIs
	 *
	 * @var array
	 */
	private $namespaces = array ();

	/**
	 * @var string
	 */
	private $baseUri;
	/**
	 * @var string
	 */
	private $dataUri;

	/**
	 * @param string $baseUri Base URI for entity concept URIs.
	 * @param string $dataUri Base URI for entity description URIs.
	 */
	public function __construct( $baseUri, $dataUri ) {
		$this->baseUri = $baseUri;
		$this->dataUri = $dataUri;

		if( substr($this->baseUri, -7) === 'entity/') {
			$topUri = substr($this->baseUri, 0, -7);
		} else {
			$topUri = $this->baseUri;
		}
		$propUri = $topUri."prop/";

		$this->namespaces = array (
				'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
				'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
				'xsd' => 'http://www.w3.org/2001/XMLSchema#',
				'owl' => 'http://www.w3.org/2002/07/owl#',
				self::NS_ONTOLOGY => self::ONTOLOGY_BASE_URI . "-beta#",
				// nodes
				self::NS_DATA => $this->dataUri,
				self::NS_ENTITY => $this->baseUri,
				self::NS_STATEMENT => $this->baseUri . 'statement/',
				self::NS_REFERENCE => $topUri . 'reference/',
				self::NS_VALUE => $topUri . 'value/',
				// predicates
				self::NSP_DIRECT_CLAIM => $propUri . 'direct/',
				self::NSP_CLAIM => $propUri,
				self::NSP_CLAIM_STATEMENT => $propUri . 'statement/',
				self::NSP_CLAIM_VALUE => $propUri . 'statement/value/',
				self::NSP_QUALIFIER => $propUri . 'qualifier/',
				self::NSP_QUALIFIER_VALUE => $propUri . 'qualifier/value/',
				self::NSP_REFERENCE => $propUri . 'reference/',
				self::NSP_REFERENCE_VALUE => $propUri . 'reference/value/',
				self::NSP_NOVALUE => $propUri . 'novalue/',
				// external
				self::NS_SKOS => self::SKOS_URI,
				self::NS_SCHEMA_ORG => self::SCHEMA_ORG_URI,
				self::NS_CC => self::CC_URI,
				self::NS_GEO => self::GEO_URI,
				self::NS_PROV => self::PROV_URI,
		);
	}

	/**
	 * Returns a map of namespace names (prefixes) to URIs
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
	public function getStatementLName( Statement $statement ) {
		return preg_replace( '/[^\w-]/', '-', $statement->getGuid() );
	}

	/**
	 * Returns a qname for the given entity type.
	 * For well known types, these qnames refer to classes from the Wikibase ontology.
	 *
	 * @param $type
	 *
	 * @return string
	 */
	public function getEntityTypeName( $type ) {
		return ucfirst( $type );
	}

	/**
	 * Get Wikibase property name for ontology
	 * @param Property $prop
	 * @return string
	 */
	public function getDataTypeName( Property $prop ) {
		return preg_replace( '/[^\w]/', '', ucwords( strtr($prop->getDataTypeId(), "-", " ") ) );
	}

	/**
	 * Create Commons URL from filename value
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function getCommonsURI( $file ) {
		return self::COMMONS_URI . rawurlencode( $file );
	}

}
