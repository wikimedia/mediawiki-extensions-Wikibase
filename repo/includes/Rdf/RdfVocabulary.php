<?php

namespace Wikibase\Rdf;

use DataValues\DataValue;
use OutOfBoundsException;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\Statement;
use Wikimedia\Assert\Assert;

/**
 * RDF vocabulary for use in mapping for wikibase data model.
 *
 * @license GPL-2.0+
 */
class RdfVocabulary {

	// Change this when changing data format!
	const FORMAT_VERSION = '0.1.0';
	const ONTOLOGY_VERSION = '1.0';

	const ONTOLOGY_BASE_URI = 'http://wikiba.se/ontology';
	const NS_ONTOLOGY = 'wikibase'; // wikibase ontology (shared)
	// Nodes
	const NS_ENTITY = 'wd'; // concept uris
	const NS_DATA = 'wdata'; // document uris
	const NS_STATEMENT = 'wds'; // statement
	const NS_REFERENCE = 'wdref'; // reference
	const NS_VALUE = 'wdv'; // value
	// Predicates
	const NSP_DIRECT_CLAIM = 'wdt'; // direct assertion entity -> value
	const NSP_DIRECT_CLAIM_NORM = 'wdtn'; // direct assertion entity -> value, normalized
	const NSP_CLAIM = 'p'; // entity -> statement
	const NSP_CLAIM_STATEMENT = 'ps'; // statement -> simple value
	const NSP_CLAIM_VALUE = 'psv'; // statement -> deep value
	const NSP_CLAIM_VALUE_NORM = 'psn'; // statement -> deep value, normalized
	const NSP_QUALIFIER = 'pq'; // statement -> qualifier
	const NSP_QUALIFIER_VALUE = 'pqv'; // statement ->  qualifier deep value
	const NSP_QUALIFIER_VALUE_NORM = 'pqn'; // statement ->  qualifier deep value, normalized
	const NSP_REFERENCE = 'pr'; // reference -> simple value
	const NSP_REFERENCE_VALUE = 'prv'; // reference -> deep value
	const NSP_REFERENCE_VALUE_NORM = 'prn'; // reference -> deep value, normalized
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
	//FIXME: get from config
	const MEDIA_URI = 'http://commons.wikimedia.org/wiki/Special:FilePath/';
	//FIXME: get from config
	const COMMONS_DATA_URI = 'http://commons.wikimedia.org/data/main/';
	const GEO_URI = 'http://www.opengis.net/ont/geosparql#';
	const PROV_URI = 'http://www.w3.org/ns/prov#';
	// TODO: make the license settable
	const LICENSE = 'http://creativecommons.org/publicdomain/zero/1.0/';

	// Gregorian calendar link.
	// I'm not very happy about hardcoding it here but see no better way so far.
	// See also DataValues\TimeValue\TimeFormatter::XXX_CALENDAR constants.
	const GREGORIAN_CALENDAR = 'http://www.wikidata.org/entity/Q1985727';
	const JULIAN_CALENDAR = 'http://www.wikidata.org/entity/Q1985786';
	/**
	 * URI for unit "1"
	 * See: https://phabricator.wikimedia.org/T105432
	 * @var string
	 */
	const ONE_ENTITY = 'http://www.wikidata.org/entity/Q199';
	// Ranks
	const WIKIBASE_RANK_BEST = 'BestRank';

	public static $rankMap = [
		Statement::RANK_DEPRECATED => 'DeprecatedRank',
		Statement::RANK_NORMAL => 'NormalRank',
		Statement::RANK_PREFERRED => 'PreferredRank',
	];
	// Value properties
	public static $claimToValue = [
		self::NSP_CLAIM_STATEMENT => self::NSP_CLAIM_VALUE,
		self::NSP_QUALIFIER => self::NSP_QUALIFIER_VALUE,
		self::NSP_REFERENCE => self::NSP_REFERENCE_VALUE,
	];
	// Value properties for normalized values
	public static $claimToValueNormalized = [
		self::NSP_CLAIM_STATEMENT => self::NSP_CLAIM_VALUE_NORM,
		self::NSP_QUALIFIER => self::NSP_QUALIFIER_VALUE_NORM,
		self::NSP_REFERENCE => self::NSP_REFERENCE_VALUE_NORM,
	];
	// Value properties for normalized values, including for direct claims
	public static $normalizedPropertyValueNamespace = [
		self::NSP_DIRECT_CLAIM => self::NSP_DIRECT_CLAIM_NORM,
		self::NSP_CLAIM_STATEMENT => self::NSP_CLAIM_VALUE_NORM,
		self::NSP_QUALIFIER => self::NSP_QUALIFIER_VALUE_NORM,
		self::NSP_REFERENCE => self::NSP_REFERENCE_VALUE_NORM
	];

	/**
	 * @var string[] Mapping of namespace names to URIs.
	 */
	private $namespaces = [];

	/**
	 * @var array Associative array mapping repository names to names specific to the particular repository
	 * (ie. containing repository suffix).
	 */
	public $entityNamespaceNames = [];

	/**
	 * @var array Associative array mapping repository names to maps, each mapping the "general" property
	 * namespace name to the name specific to the particular repository (ie. containing repository suffix).
	 */
	public $propertyNamespaceNames = [];

	/**
	 * @var string[] Mapping of non-standard to canonical language codes.
	 */
	private $canonicalLanguageCodes;

	/**
	 * @var string[]
	 */
	private $dataTypeUris;

	/**
	 * @var string[]
	 */
	private static $canonicalLanguageCodeCache = [];

	/**
	 * Map of the configured page properties.
	 * @var string[]
	 */
	private $pagePropertyDefs;

	/**
	 * @param string[] $conceptUris Associative array mapping repository names to base URIs for entity concept URIs.
	 * @param string   $dataUri Base URI for entity description URIs.
	 * @param string[] $canonicalLanguageCodes Mapping of non-standard to canonical language codes.
	 * @param string[] $dataTypeUris Mapping of property data type IDs to their URIs,
	 *                 if different from the default mapping.
	 * @param string[] $pagePropertyDefs Mapping of page props: pageProp => wikibase predicate
	 *                 All predicates will be prefixed with wikibase:
	 */
	public function __construct(
		array $conceptUris,
		$dataUri,
		array $canonicalLanguageCodes = [],
		array $dataTypeUris = [],
		array $pagePropertyDefs = []
	) {
		Assert::parameter(
			array_key_exists( '', $conceptUris ),
			'$conceptUris',
			'must contain entry for the local repository, ie. empty-string key'
		);
		Assert::parameterElementType( 'string', $conceptUris, '$conceptUris' );
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $conceptUris, '$conceptUris' );

		$this->canonicalLanguageCodes = $canonicalLanguageCodes;
		$this->dataTypeUris = $dataTypeUris;
		$this->pagePropertyDefs = $pagePropertyDefs;

		$this->namespaces = [
			'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
			'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
			'xsd' => 'http://www.w3.org/2001/XMLSchema#',
			'owl' => 'http://www.w3.org/2002/07/owl#',
			// TODO: drop beta once we have stable ontology
			self::NS_ONTOLOGY => self::ONTOLOGY_BASE_URI . "-beta#",

			self::NS_STATEMENT => $conceptUris[''] . 'statement/',

			// nodes
			self::NS_DATA => $dataUri,

			// external
			self::NS_SKOS => self::SKOS_URI,
			self::NS_SCHEMA_ORG => self::SCHEMA_ORG_URI,
			self::NS_CC => self::CC_URI,
			self::NS_GEO => self::GEO_URI,
			self::NS_PROV => self::PROV_URI,
		];

		$topUri = $this->getConceptUriBase( $conceptUris[''] );

		$this->namespaces[self::NS_REFERENCE] = $topUri . 'reference/';
		$this->namespaces[self::NS_VALUE] = $topUri . 'value/';

		$propertyNamespaces = [
			self::NSP_DIRECT_CLAIM,
			self::NSP_DIRECT_CLAIM_NORM,
			self::NSP_CLAIM,
			self::NSP_CLAIM_STATEMENT,
			self::NSP_CLAIM_VALUE,
			self::NSP_CLAIM_VALUE_NORM,
			self::NSP_QUALIFIER,
			self::NSP_QUALIFIER_VALUE,
			self::NSP_QUALIFIER_VALUE_NORM,
			self::NSP_REFERENCE,
			self::NSP_REFERENCE_VALUE,
			self::NSP_REFERENCE_VALUE_NORM,
			self::NSP_NOVALUE,
		];

		foreach ( $conceptUris as $repositoryName => $baseUri ) {
			$namespaceSuffix = '';
			if ( $repositoryName !== '' ) {
				$namespaceSuffix = '-' . $repositoryName;
			}

			$this->entityNamespaceNames[$repositoryName] = self::NS_ENTITY . $namespaceSuffix;

			$this->propertyNamespaceNames[$repositoryName] = array_combine(
				$propertyNamespaces,
				array_map(
					function ( $ns ) use ( $namespaceSuffix ) {
						return $ns . $namespaceSuffix;
					},
					$propertyNamespaces
				)
			);

			$this->namespaces = array_merge(
				$this->namespaces,
				$this->getConceptNamespaces( $namespaceSuffix, $baseUri )
			);
		}
	}

	/**
	 * Generates mapping of concept namespaces (including the namespace suffix) to URIs
	 * using the given URI base.
	 *
	 * @param string $namespaceSuffix
	 * @param string $baseUri
	 * @return string[]
	 */
	private function getConceptNamespaces( $namespaceSuffix, $baseUri ) {
		$topUri = $this->getConceptUriBase( $baseUri );

		$propUri = $topUri . 'prop/';

		return [
			self::NS_ENTITY . $namespaceSuffix => $baseUri,
			// predicates
			self::NSP_DIRECT_CLAIM . $namespaceSuffix => $propUri . 'direct/',
			self::NSP_DIRECT_CLAIM_NORM . $namespaceSuffix => $propUri . 'direct-normalized/',
			self::NSP_CLAIM . $namespaceSuffix => $propUri,
			self::NSP_CLAIM_STATEMENT . $namespaceSuffix => $propUri . 'statement/',
			self::NSP_CLAIM_VALUE . $namespaceSuffix => $propUri . 'statement/value/',
			self::NSP_CLAIM_VALUE_NORM . $namespaceSuffix => $propUri . 'statement/value-normalized/',
			self::NSP_QUALIFIER . $namespaceSuffix => $propUri . 'qualifier/',
			self::NSP_QUALIFIER_VALUE . $namespaceSuffix => $propUri . 'qualifier/value/',
			self::NSP_QUALIFIER_VALUE_NORM . $namespaceSuffix => $propUri . 'qualifier/value-normalized/',
			self::NSP_REFERENCE . $namespaceSuffix => $propUri . 'reference/',
			self::NSP_REFERENCE_VALUE . $namespaceSuffix => $propUri . 'reference/value/',
			self::NSP_REFERENCE_VALUE_NORM . $namespaceSuffix => $propUri . 'reference/value-normalized/',
			self::NSP_NOVALUE . $namespaceSuffix => $propUri . 'novalue/',
		];
	}

	/**
	 * Returns the base part of concept URIs
	 *
	 * @param string $baseUri
	 * @return string
	 */
	private function getConceptUriBase( $baseUri ) {
		if ( substr( $baseUri, -7 ) === 'entity/' ) {
			return substr( $baseUri, 0, -7 );
		}
		return $baseUri;
	}

	/**
	 * Returns a map of namespace names (prefixes) to URIs
	 *
	 * @return string[]
	 */
	public function getNamespaces() {
		return $this->namespaces;
	}

	/**
	 * Returns the base URI for a given namespace (aka prefix).
	 *
	 * @param string $ns The namespace name
	 *
	 * @throws OutOfBoundsException if $ns is not a known namespace
	 * @return string the URI for the given namespace
	 */
	public function getNamespaceURI( $ns ) {
		if ( !isset( $this->namespaces[$ns] ) ) {
			throw new OutOfBoundsException();
		}

		return $this->namespaces[$ns];
	}

	/**
	 * Returns a local name for the given entity using the given prefix.
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	public function getEntityLName( EntityId $entityId ) {
		$id = $entityId->getSerialization();

		$localIdPart = $entityId->getLocalPart();
		// If local ID part (ID excluding repository prefix) contains a colon, ie. the ID contains
		// "chained" repository prefixes, replace all colons with periods in the local ID part.
		return str_replace( ':', '.', $localIdPart );
	}

	/**
	 * @param EntityId $entityId
	 * @return string
	 */
	public function getEntityRepositoryName( EntityId $entityId ) {
		return $entityId->getRepositoryName();
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
	 * @param string $type
	 *
	 * @return string
	 */
	public function getEntityTypeName( $type ) {
		return ucfirst( $type );
	}

	/**
	 * Get Wikibase property data type Uri for ontology
	 *
	 * @param Property $prop
	 *
	 * @return string
	 */
	public function getDataTypeURI( Property $prop ) {
		$type = $prop->getDataTypeId();

		if ( !isset( $this->dataTypeUris[$type] ) ) {
			// if the requested type has no URI in $this->dataTypeUris, add a generic one
			$name = preg_replace( '/\W+/', '', ucwords( strtr( $type, '-', ' ' ) ) );
			$this->dataTypeUris[$type] = $this->namespaces[self::NS_ONTOLOGY] . $name;
		}

		return $this->dataTypeUris[$type];
	}

	/**
	 * Get Wikibase value type name for ontology
	 *
	 * @param DataValue $val
	 *
	 * @return string
	 */
	public function getValueTypeName( DataValue $val ) {
		return ucfirst( $val->getType() ) . 'Value';
	}

	/**
	 * Create Commons URL from filename value
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function getMediaFileURI( $file ) {
		return self::MEDIA_URI . rawurlencode( $file );
	}

	/**
	 * Create data entry point URL for geo shapes
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function getGeoShapeURI( $file ) {
		return self::COMMONS_DATA_URI . wfUrlencode( $file );
	}

	/**
	 * Create data entry point URL for tabular data
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function getTabularDataURI( $file ) {
		return self::COMMONS_DATA_URI . wfUrlencode( $file );
	}

	/**
	 * @param string $languageCode Any non-standard or canonical language code
	 *
	 * @return string Canonical language code
	 */
	public function getCanonicalLanguageCode( $languageCode ) {
		// First we check the case since most languages will be cached very quickly
		if ( isset( self::$canonicalLanguageCodeCache[$languageCode] ) ) {
			return self::$canonicalLanguageCodeCache[$languageCode];
		}

		// Wikibase list goes first in case we want to override
		// Like "simple" goes to en-simple not en
		if ( isset( $this->canonicalLanguageCodes[$languageCode] ) ) {
			return $this->canonicalLanguageCodes[$languageCode];
		}

		self::$canonicalLanguageCodeCache[$languageCode] = wfBCP47( $languageCode );
		return self::$canonicalLanguageCodeCache[$languageCode];
	}

	/**
	 * Return current ontology version URI
	 * @return string
	 */
	public static function getOntologyURI() {
		return self::ONTOLOGY_BASE_URI . "-" . self::ONTOLOGY_VERSION . ".owl";
	}

	/**
	 * Get the map of configured page properties
	 * @return string[]
	 */
	public function getPageProperties() {
		return $this->pagePropertyDefs;
	}

}
