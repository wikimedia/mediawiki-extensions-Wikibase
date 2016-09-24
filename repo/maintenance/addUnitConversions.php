<?php
namespace Wikibase;

use DataValues\DecimalValue;
use DataValues\QuantityValue;
use Maintenance;
use Title;
use Wikibase\Lib\JsonUnitStorage;
use Wikibase\Lib\UnitConverter;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Repo\Maintenance\SPARQLClient;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;
use Wikimedia\Purtle\RdfWriterFactory;

$basePath =
	getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';
require_once __DIR__ . '/SPARQLClient.php';

/**
 * Generate dump-like RDF for newly added units without running full dump.
 * @package Wikibase
 */
class AddUnitConversions extends Maintenance {

	/**
	 * Max chunk of values processed by one query
	 */
	const MAX_QUERY_CHUNK = 100;

	/**
	 * @var RdfVocabulary
	 */
	private $rdfVocabulary;

	/**
	 * @var RdfWriter
	 */
	private $rdfWriter;
	/**
	 * @var UnitConverter
	 */
	private $unitConverter;
	/**
	 * @var SPARQLClient
	 */
	private $client;
	/***
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

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Produce RDF for new units." );

		$this->addOption( 'config', 'Current units config.', true, true );
		$this->addOption( 'old-config', 'Previous units config.', false, true );
		$this->addOption( 'output', 'File to output the data to.', true, true );
		$this->addOption( 'format', "Set the dump format.", false, true );
		$this->addOption( 'base-uri', 'Base URI for the data.', false, true );
	}

	/**
	 * Do the actual work. All child classes will need to implement this
	 */
	public function execute() {
		$newJsonName = $this->getOption( 'config' );
		$newJson = json_decode( file_get_contents( $newJsonName ), true );
		if ( !$newJson ) {
			$this->error( "Can not load new config", 1 );
		}

		$oldJsonName = $this->getOption( 'old-config' );
		if ( !$oldJsonName ) {
			$oldJson = [];
		} else {
			$oldJson = json_decode( file_get_contents( $oldJsonName ), true );
			if ( !$oldJsonName ) {
				$this->error( "Can not load old config", 1 );
			}
		}

		$diffUnits = array_diff( array_keys( $newJson ), array_keys( $oldJson ) );
		if ( empty( $diffUnits ) ) {
			// we're done
			$this->error( "No new units." );
			return;
		}
		$this->output( 'Detected ' . count( $diffUnits ) . " new units\n" );

		$this->out = fopen( $this->getOption( 'output' ), 'w' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$endPoint = $wikibaseRepo->getSettings()->getSetting( 'sparqlEndpoint' );
		$baseUri =
			$this->getOption( 'base-uri',
				$wikibaseRepo->getSettings()->getSetting( 'conceptBaseUri' ) );
		$this->client = new SPARQLClient( $endPoint, $baseUri );

		$this->unitConverter = new UnitConverter( new JsonUnitStorage( $newJsonName ), $baseUri );
		$this->rdfVocabulary =
			$this->getRdfVocabulary( $baseUri,
				$wikibaseRepo->getDataTypeDefinitions()->getRdfTypeUris() );
		$this->rdfWriter = $this->getRdfWriter();

		$ns = $this->rdfVocabulary->getNamespaces();
		$this->valueURI = $ns[RdfVocabulary::NS_VALUE];
		foreach ( RdfVocabulary::$claimToValueNormalized as $value => $norm ) {
			$this->normMap[$ns[RdfVocabulary::$claimToValue[$value]]] = $norm;
		}

		$this->startDocument();

		foreach ( $diffUnits as $unit ) {
			$this->processUnit( $unit );
			$this->writeOut( $this->rdfWriter->drain() );
		}


	}

	private function processUnit( $unit ) {
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
		$this->output( "Got " . count( $values ) . " ids\n" );
		$map = [];
		foreach ( $values as $value ) {
			$id = str_replace( $this->valueURI, '', $value['v'] );
			$map[$id] = $this->getNormalized( $id, $unit, $value );
			$this->rdfWriter->about( RdfVocabulary::NS_VALUE, $id )
				->say( RdfVocabulary::NS_ONTOLOGY, 'quantityNormalized' )
				->is( RdfVocabulary::NS_VALUE, $map[$id] );

		}
		$this->writeOut( $this->rdfWriter->drain() );
		foreach ( array_chunk( array_keys( $map ), self::MAX_QUERY_CHUNK ) as $idChunk ) {
			$this->processStatements( $idChunk, $map );
			$this->writeOut( $this->rdfWriter->drain() );
		}
		$this->output( "Done.\n" );
	}

	/**
	 * Create DecimalValue from regular numeric string or value.
	 * @param int|float|string $number
	 * FIXME: replace with DecimalValue method from https://github.com/DataValues/Number/pull/67
	 * @return DecimalValue
	 */
	private function makeDecimalValue( $number ) {

		if ( is_string( $number ) && $number !== '' ) {
			if ( $number[0] !== '-' && $number[0] !== '+' ) {
				$number = '+' . $number;
			}
		}

		return new DecimalValue( $number );
	}

	/**
	 * Normalize unit and return the hash of the normalized node.
	 * @param string $id Original value ID (hash)
	 * @param string $unit Short ID of the unit
	 * @param string[] $value Value data array
	 * @return string Hash of the normalized node
	 */
	private function getNormalized( $id, $unit, $value ) {
		$q =
			new QuantityValue( $this->makeDecimalValue( $value['amount'] ), $unit,
				$this->makeDecimalValue( $value['upper'] ),
				$this->makeDecimalValue( $value['lower'] ) );
		$qNorm = $this->unitConverter->toStandardUnits( $q );
		if ( $q === $qNorm ) {
			// didn't actually convert, so return original one
			return $id;
		} else {
			// FIXME: copypasting QuantityRdfBuilder here, but not sure how to
			// get only the relevant part
			$valueLName = $qNorm->getHash();
			$this->rdfWriter->about( RdfVocabulary::NS_VALUE, $valueLName )
				->a( RdfVocabulary::NS_ONTOLOGY, $this->rdfVocabulary->getValueTypeName( $qNorm ) );
			$this->rdfWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityAmount' )
				->value( $qNorm->getAmount(), 'xsd', 'decimal' );

			$this->rdfWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityUpperBound' )
				->value( $qNorm->getUpperBound(), 'xsd', 'decimal' );

			$this->rdfWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityLowerBound' )
				->value( $qNorm->getLowerBound(), 'xsd', 'decimal' );

			$unitUri = trim( $qNorm->getUnit() );

			if ( $unitUri === '1' ) {
				$unitUri = RdfVocabulary::ONE_ENTITY;
			}
			$this->rdfWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityUnit' )->is( $unitUri );
			$this->rdfWriter->say( RdfVocabulary::NS_ONTOLOGY, 'quantityNormalized' )
				->is( RdfVocabulary::NS_VALUE, $valueLName );

			return $valueLName;
		}
	}

	private function processStatements( $values, $map ) {
		$shortValues = array_map( function ( $str ) {
			return 'wdv:' . $str;
		}, $values );
		$valuesStr = join( ' ', $shortValues );
		$query = <<<QUERY
SELECT ?s ?p ?v WHERE {
	VALUES ?v { $valuesStr }
	?s ?p ?v
} ORDER BY ?s
QUERY;
		$data = $this->client->query( $query );
		foreach ( $data as $statement ) {
			$last = strrpos( $statement['p'], '/' );
			$prefix = substr( $statement['p'], 0, $last + 1 );
			$name = substr( $statement['p'], $last + 1 );
			if ( !isset( $this->normMap[$prefix] ) ) {
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

	public function startDocument() {
		foreach ( $this->rdfVocabulary->getNamespaces() as $gname => $uri ) {
			$this->rdfWriter->prefix( $gname, $uri );
		}

		$this->writeOut( $this->rdfWriter->drain() );
	}

	/**
	 * Write data to the output
	 * @param $data
	 */
	private function writeOut( $data ) {
		fwrite( $this->out, $data );
	}

	/**
	 * Get vocabulary instance
	 * @param string $baseUri
	 * @param string[] $typeUris
	 * @return RdfVocabulary
	 */
	private function getRdfVocabulary( $baseUri, $typeUris ) {
		$entityDataTitle = Title::makeTitle( NS_SPECIAL, 'EntityData' );

		return new RdfVocabulary( $baseUri, $entityDataTitle->getCanonicalURL() . '/', [],
			$typeUris, [] );

	}

	/**
	 * @return \Wikimedia\Purtle\RdfWriter
	 */
	private function getRdfWriter() {
		$factory = new RdfWriterFactory();
		return $factory->getWriter( $factory->getFormatName( $this->getOption( 'format',
			'ttl' ) ) );
	}

}

$maintClass = AddUnitConversions::class;
require_once RUN_MAINTENANCE_IF_MAIN;