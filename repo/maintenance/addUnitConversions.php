<?php

namespace Wikibase\Repo\Maintenance;

use DataValues\DecimalValue;
use DataValues\QuantityValue;
use Maintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Sparql\SparqlClient;
use Title;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Lib\Units\JsonUnitStorage;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Repo\Rdf\Values\QuantityRdfBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;
use Wikimedia\Purtle\RdfWriterFactory;

$basePath =
	getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Generate dump-like RDF for newly added units without running full dump.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class AddUnitConversions extends Maintenance {

	/**
	 * Max chunk of values processed by one query
	 */
	private const MAX_QUERY_CHUNK = 100;

	/**
	 * @var RdfVocabulary
	 */
	private $rdfVocabulary;

	/**
	 * @var RdfWriter
	 */
	protected $rdfWriter;

	/**
	 * @var UnitConverter
	 */
	protected $unitConverter;

	/**
	 * @var SparqlClient
	 */
	protected $client;

	/**
	 * @var resource
	 */
	private $out;

	/**
	 * map of normalization predicates by full name
	 * @var string[]
	 */
	private $normMap;

	/**
	 * Value URI prefix
	 * @var string
	 */
	private $valueURI;

	/**
	 * Set of normalized namespace names.
	 * @var bool[]
	 */
	private $normalizedNames;

	/**
	 * @var QuantityRdfBuilder
	 */
	protected $builder;

	/**
	 * @var bool
	 */
	private $dryRun;

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Produce RDF for new units." );

		$this->addOption( 'config', 'Current units config.', true, true );
		$this->addOption( 'old-config', 'Previous units config.', false, true );
		$this->addOption( 'output', 'File to output the data to.', true, true );
		$this->addOption( 'format', "Set the dump format.", false, true );
		$this->addOption( 'base-uri', 'Base URI for the data.', false, true );
		$this->addOption( 'sparql', 'SPARQL endpoint URL.', false, true );
		$this->addOption( 'dry-run', 'Do not generate output, only count values.', false, false );
	}

	/**
	 * Do the actual work. All child classes will need to implement this
	 */
	public function execute() {
		$newJsonName = $this->getOption( 'config' );
		$newJson = json_decode( file_get_contents( $newJsonName ), true );
		if ( !$newJson ) {
			$this->fatalError( "Cannot load new config" );
		}

		$oldJsonName = $this->getOption( 'old-config' );
		if ( !$oldJsonName ) {
			$oldJson = [];
		} else {
			$oldJson = json_decode( file_get_contents( $oldJsonName ), true );
			if ( !$oldJson ) {
				$this->fatalError( "Cannot load old config" );
			}
		}

		$diffUnits = array_diff( array_keys( $newJson ), array_keys( $oldJson ) );
		if ( empty( $diffUnits ) ) {
			$this->error( "No new units." );
			return;
		}
		$this->output( 'Detected ' . count( $diffUnits ) . " new units\n" );
		$this->dryRun = $this->getOption( 'dry-run' );

		if ( !$this->dryRun ) {
			$this->out = fopen( $this->getOption( 'output' ), 'w' );
		}

		$settings = WikibaseRepo::getSettings();
		$endPoint = $this->getOption( 'sparql',
				$settings->getSetting( 'sparqlEndpoint' ) );
		if ( !$endPoint ) {
			$this->fatalError( 'SPARQL endpoint should be supplied in config or parameters' );
		}

		$baseUri = $this->getOption( 'base-uri', WikibaseRepo::getItemVocabularyBaseUri() );

		$this->client = new SparqlClient( $endPoint, MediaWikiServices::getInstance()->getHttpRequestFactory() );
		$this->client->appendUserAgent( __CLASS__ );
		$format = $this->getOption( 'format', 'ttl' );
		$this->initializeWriter( $baseUri, $format );
		$this->unitConverter = new UnitConverter( new JsonUnitStorage( $newJsonName ), $baseUri );
		$this->initializeBuilder();

		foreach ( $diffUnits as $unit ) {
			$this->processUnit( $unit );
			$this->writeOut();
		}
	}

	/**
	 * Initialize RDF writer
	 *
	 * @param string $baseUri
	 * @param string $format File extension or MIME type of the output format.
	 */
	public function initializeWriter( $baseUri, $format ) {
		$this->rdfVocabulary = $this->createRdfVocabulary(
			$baseUri,
			WikibaseRepo::getDataTypeDefinitions()->getRdfTypeUris()
		);
		$this->rdfWriter = $this->createRdfWriter( $format );

		$ns = $this->rdfVocabulary->getNamespaces();
		$this->valueURI = $ns[RdfVocabulary::NS_VALUE];
		foreach ( $this->rdfVocabulary->claimToValueNormalized as $value => $norm ) {
			$this->normMap[$ns[$this->rdfVocabulary->claimToValue[$value]]] = $norm;
			$this->normalizedNames[$ns[$norm]] = true;
		}
		$this->startDocument();
	}

	/**
	 * Initialize quantity builder.
	 */
	public function initializeBuilder() {
		$this->builder =
			new QuantityRdfBuilder( new ComplexValueRdfHelper( $this->rdfVocabulary,
				$this->rdfWriter ), $this->unitConverter );
	}

	/**
	 * Generate all statements for a specific unit.
	 *
	 * @param string $unit Unit Q-id
	 */
	public function processUnit( $unit ) {
		$this->output( "Processing $unit...\n" );
		$query = <<<QUERY
SELECT * WHERE {
{
    SELECT DISTINCT ?v  WHERE {
        ?v wikibase:quantityUnit wd:$unit .
        FILTER EXISTS { ?s ?p ?v }
    }
}
  ?v wikibase:quantityAmount ?amount .
  ?v wikibase:quantityUpperBound ?upper .
  ?v wikibase:quantityLowerBound ?lower .
}
QUERY;
		$values = $this->client->query( $query );
		'@phan-var array[] $values';
		$this->output( "Got " . count( $values ) . " ids\n" );
		if ( $this->dryRun ) {
			return;
		}
		$map = [];
		foreach ( $values as $value ) {
			if ( substr_compare( $value['v'], $this->valueURI, 0, strlen( $this->valueURI ) ) !== 0 ) {
				$this->error( "Invalid value: {$value['v']}!" );
				continue;
			}
			$id = str_replace( $this->valueURI, '', $value['v'] );
			$map[$id] = $this->getNormalized( $id, $unit, $value );
			$this->rdfWriter->about( RdfVocabulary::NS_VALUE, $id )
				->say( RdfVocabulary::NS_ONTOLOGY, 'quantityNormalized' )
				->is( RdfVocabulary::NS_VALUE, $map[$id] );

		}
		$this->writeOut();
		foreach ( array_chunk( array_keys( $map ), self::MAX_QUERY_CHUNK ) as $idChunk ) {
			$this->processStatements( $idChunk, $map );
			$this->writeOut();
		}
		$this->output( "Done.\n" );
	}

	/**
	 * Normalize unit and return the hash of the normalized node.
	 *
	 * @param string   $id Original value ID (hash)
	 * @param string   $unit Short ID of the unit
	 * @param string[] $value Value data array
	 *
	 * @return string Hash of the normalized node
	 */
	private function getNormalized( $id, $unit, array $value ) {
		$q =
			new QuantityValue( new DecimalValue( $value['amount'] ), $unit,
				new DecimalValue( $value['upper'] ),
				new DecimalValue( $value['lower'] ) );
		$qNorm = $this->unitConverter->toStandardUnits( $q );
		if ( $q === $qNorm ) {
			// didn't actually convert, so return original one
			return $id;
		} else {
			$normLName = $qNorm->getHash();

			$this->rdfWriter->about( RdfVocabulary::NS_VALUE, $normLName )
				->a( RdfVocabulary::NS_ONTOLOGY, $this->rdfVocabulary->getValueTypeName( $qNorm ) );

			$this->builder->writeQuantityValue( $qNorm );

			$this->rdfWriter->about( RdfVocabulary::NS_VALUE, $normLName )
				->say( RdfVocabulary::NS_ONTOLOGY, 'quantityNormalized' )
				->is( RdfVocabulary::NS_VALUE, $normLName );

			return $normLName;
		}
	}

	/**
	 * Process statements for particular set of values.
	 * Will scan through the triples which use each of the values and
	 * add appropriate normalized triple referring to the normalized value.
	 * E.g. <s123> psv:P345 wdv:xys -> <s123> psn:P345 wdv:xyznorm
	 *
	 * @param string[] $values Value hashes
	 * @param string[] $map Map old id -> normalized id
	 */
	private function processStatements( $values, $map ) {
		$shortValues = array_map( function ( $str ) {
			return 'wdv:' . $str;
		}, $values );
		$valuesStr = implode( ' ', $shortValues );
		$query = <<<QUERY
SELECT ?s ?p ?v WHERE {
	VALUES ?v { $valuesStr }
	?s ?p ?v
	FILTER (?p != wikibase:quantityNormalized)
} ORDER BY ?s
QUERY;
		$data = $this->client->query( $query );
		'@phan-var array[] $data';
		foreach ( $data as $statement ) {
			// Split predicate name into $prefix and $name (actual P123 part)
			$last = strrpos( $statement['p'], '/' );
			$prefix = substr( $statement['p'], 0, $last + 1 );
			$name = substr( $statement['p'], $last + 1 );
			if ( isset( $this->normalizedNames[$prefix] ) ) {
				// This is already normalized predicate
				// This can happen when we deployed new config and
				// somebody edits the data with that unit - the update will already have
				// the normalized value. We can just ignore it.
				continue;
			}
			if ( !isset( $this->normMap[$prefix] ) ) {
				// This shouldn't happen - it means value used in predicate
				// that is not in RdfVocabulary.
				$this->error( "Unknown predicate {$statement['p']}" );
				continue;
			}
			$v = str_replace( $this->valueURI, '', $statement['v'] );
			$this->rdfWriter->about( $statement['s'] )
				->say( $this->normMap[$prefix], $name )
				->is( RdfVocabulary::NS_VALUE, $map[$v] );
		}
		$this->output( '.' );
	}

	/**
	 * Kick off the document
	 */
	public function startDocument() {
		foreach ( $this->rdfVocabulary->getNamespaces() as $gname => $uri ) {
			$this->rdfWriter->prefix( $gname, $uri );
		}

		$this->writeOut();
	}

	/**
	 * Write data to the output
	 */
	protected function writeOut() {
		$data = $this->rdfWriter->drain();
		if ( $this->out ) {
			if ( fwrite( $this->out, $data ) === false ) {
				$this->fatalError( "Failed to write to the output, exiting." );
			}
		}
	}

	/**
	 * Get vocabulary instance
	 *
	 * @param string   $baseUri
	 * @param string[] $typeUris
	 *
	 * @return RdfVocabulary
	 */
	private function createRdfVocabulary( $baseUri, $typeUris ) {
		$entityDataTitle = Title::makeTitle( NS_SPECIAL, 'EntityData' );

		return new RdfVocabulary(
			[ '' => $baseUri ],
			[ '' => $entityDataTitle->getCanonicalURL() . '/' ],
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			[ '' => 'wd' ],
			[ '' => '' ],
			[],
			$typeUris,
			[]
		);
	}

	/**
	 * @param string $format File extension or MIME type of the output format.
	 *
	 * @return RdfWriter
	 */
	private function createRdfWriter( $format ) {
		$factory = new RdfWriterFactory();
		return $factory->getWriter( $factory->getFormatName( $format ) );
	}

}

$maintClass = AddUnitConversions::class;
require_once RUN_MAINTENANCE_IF_MAIN;
