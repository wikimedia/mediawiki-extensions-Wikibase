<?php

use MediaWikiTestCase;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\MediaWikiDoctrineDBALDriver;
use Wikibase\TermStore\DoctrineStoreFactory;

class TermStoreConnectionTest extends MediaWikiTestCase
{
	public function testCreateDoctrineConnection() {

		// $connection = DriverManager::getConnection([
		//     'dbname' => $GLOBALS['wgDBname'],
		//     'user' => $GLOBALS['wgDBuser'],
		//     'password' => $GLOBALS['wgDBpassword'],
		//     'host' => $GLOBALS['wgDBserver'],
		//     'driver' => 'pdo_mysql'
		// ]);

		// $connection = DriverManager::getConnection([
		//     'dbname' => $this->db->getDBname(),
		//     'user' => $GLOBALS['wgDBuser'],
		//     'password' => $GLOBALS['wgDBpassword'],
		//     'host' => $this->db->getServer(),
		//     'driver' => 'pdo_mysql'
		// ]);
		//

		$connection = new Connection(
			[],
			new MediaWikiDoctrineDBALDriver( $this->db->getConn() )
		);

		$factory = new DoctrineStoreFactory( $connection );
		// $factory->createSchema();

		$store = $factory->newPropertyTermStore();
		$store->storeTerms(
			new PropertyId( 'P123' ),
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'EnglishLabel' ),
					new Term( 'de', 'ZeGermanLabel' ),
					new Term( 'fr', 'LeFrenchLabel' ),
				] ),
				new TermList( [
					new Term( 'en', 'EnglishDescription' ),
					new Term( 'de', 'ZeGermanDescription' ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'fr', [ 'LeFrenchAlias', 'LaFrenchAlias' ] ),
					new AliasGroup( 'en', [ 'EnglishAlias' ] ),
				] )
			)
		);
	}

}

// $this->connection->executeQuery( 'SELECT count(*) as records FROM ' . $tableName )->fetchColumn(),

//$wgDBserver = 'localhost';
//
///**
// * Database port number (for PostgreSQL and Microsoft SQL Server).
// */
//$wgDBport = 5432;
//
///**
// * Name of the database; this should be alphanumeric and not contain spaces nor hyphens
// */
//$wgDBname = 'my_wiki';
//
///**
// * Database username
// */
//$wgDBuser = 'wikiuser';
//
///**
// * Database user's password
// */
//$wgDBpassword = '';
//
///**
// * Database type
// */
//$wgDBtype = 'mysql';
//
///**
// * Whether to use SSL in DB connection.
// *
// * This setting is only used if $wgLBFactoryConf['class'] is set to
// * '\Wikimedia\Rdbms\LBFactorySimple' and $wgDBservers is an empty array; otherwise
// * the DBO_SSL flag must be set in the 'flags' option of the database
// * connection to achieve the same functionality.
// */
//$wgDBssl = false;
