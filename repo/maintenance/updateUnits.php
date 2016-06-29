<?php
namespace Wikibase;

use Maintenance;
use Wikibase\Repo\Maintenance\SPARQLClient;
use Wikibase\Repo\WikibaseRepo;

$basePath =
	getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

class UpdateUnits extends Maintenance {
	
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Update units." );

		$this->addOption( 'base-unit-types', 'Types of base units.', true, true );
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->error( "You need to have Wikibase enabled in order to use this maintenance script!\n",
				1 );
		}

		$repo = WikibaseRepo::getDefaultInstance();
		$endPoint = $repo->getSettings()->getSetting( 'sparqlEndpoint' );
		if ( !$endPoint ) {
			$this->error( 'SPARQL endpoint not defined', 1 );
		}
		$baseUri =
			WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'conceptBaseUri' );
		$client = new SPARQLClient( $endPoint, $baseUri );

		$types =
			str_replace( [ ',', 'Q' ], [ ' ', 'wd:Q' ], $this->getOption( 'base-unit-types' ) );

		$baseUnits =
			$client->getIDs( "SELECT ?item WHERE { VALUES ?class { $types } ?item wdt:P31 ?class }",
				"item" );
		// flip for better lookup
		$baseUnits = array_flip( $baseUnits );
		$unitsQuery = <<<QUERY
		SELECT REDUCED ?unit ?si ?siUnit WHERE {
		  ?unit wdt:P31 ?type .
		  ?type wdt:P279* wd:Q47574 .
		  # Not a currency
		  FILTER (?type != wd:Q8142)
		  # Not a cardinal number
		  FILTER NOT EXISTS { ?unit wdt:P31 wd:Q163875 }
		  # Has conversion to SI Units
		  ?unit p:P2370/psv:P2370 [ wikibase:quantityAmount ?si; wikibase:quantityUnit ?siUnit ] .
		}        
QUERY;
		$convertUnits = [ ];
		$units = $client->query( $unitsQuery );
		foreach ( $units as $unit ) {
			$unit['unit'] = substr( $unit['unit'], 31 );
			$unit['siUnit'] = substr( $unit['siUnit'], 31 );
			if ( isset( $convertUnits[$unit['unit']] ) ) {
				// done already
				continue;
			}
			if ( $unit['unit'] == $unit['siUnit'] ) {
				// base unit
				if ( $unit['si'] != 1 ) {
					print "Weird unit: {$unit['unit']} is {$unit['si']} of itself!\n";
				}
				if ( !isset( $baseUnits[$unit['siUnit']] ) ) {
					print "Weird unit: {$unit['unit']} is self-referring but not base!\n";
				}
			}
			if ( !isset( $baseUnits[$unit['siUnit']] ) ) {
				// base unit is not actually base
				continue;
			}
			$convertUnits[$unit['unit']] = [ $unit['si'], $unit['siUnit'] ];
		}
		echo json_encode( $convertUnits, JSON_PRETTY_PRINT );
	}
}

$maintClass = UpdateUnits::class;
require_once RUN_MAINTENANCE_IF_MAIN;