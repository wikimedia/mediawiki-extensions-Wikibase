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
use Wikibase\TermStore\DoctrineTermStore;

class TermStoreConnectionTest extends MediaWikiTestCase
{
	public function testCreateDoctrineConnection() {
		$connection = new Connection(
			[],
			new MediaWikiDoctrineDBALDriver( $this->db->getConn() )
		);

		$factory = new DoctrineTermStore( $connection, '' );
		// $factory->install(); // run

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
