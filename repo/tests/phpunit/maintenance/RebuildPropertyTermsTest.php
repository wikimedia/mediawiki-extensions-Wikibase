<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Onoi\MessageReporter\NullMessageReporter;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\InMemoryEntityIdPager;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\RebuildPropertyTerms;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermStore\Implementations\InMemoryPropertyTermStore;

/**
 * @covers \Wikibase\RebuildPropertyTerms
 *W
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RebuildPropertyTermsTest extends MediaWikiTestCase {

	public function testScriptStoresTerms() {
		$propertyLookup = new InMemoryEntityLookup();
		$propertyLookup->addEntity( $this->newP1() );
		$propertyLookup->addEntity( $this->newP2() );

		$idPager = new InMemoryEntityIdPager();
		$idPager->addEntityId( $this->newP1()->getId() );
		$idPager->addEntityId( $this->newP2()->getId() );

		$termStore = new InMemoryPropertyTermStore();

		WikibaseRepo::getDefaultInstance()->setPropertyTermStore( $termStore );

		$script = new RebuildPropertyTerms();
		$script->executeWithDependencies( $propertyLookup, $idPager, new NullMessageReporter() );

		$this->assertEquals(
			$this->newP1()->getFingerprint(),
			$termStore->getTerms( $this->newP1()->getId() )
		);

		$this->assertEquals(
			$this->newP2()->getFingerprint(),
			$termStore->getTerms( $this->newP2()->getId() )
		);
	}

	private function newP1() {
		return new Property(
			new PropertyId( 'P1' ),
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'EnglishPropLabel' ),
					new Term( 'de', 'GermanPropLabel' ),
					new Term( 'nl', 'DutchPropLabel' ),
				] )
			),
			'data-type-id'
		);
	}

	private function newP2() {
		return new Property(
			new PropertyId( 'P2' ),
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
			),
			'data-type-id'
		);
	}

}