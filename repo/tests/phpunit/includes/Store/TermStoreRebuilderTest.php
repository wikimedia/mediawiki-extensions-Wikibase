<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use Onoi\MessageReporter\SpyMessageReporter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Store\TermStoreRebuilder;
use Wikibase\TermStore\Implementations\InMemoryItemTermStore;
use Wikibase\TermStore\Implementations\InMemoryPropertyTermStore;
use Wikibase\TermStore\Implementations\ThrowingItemTermStore;
use Wikibase\TermStore\Implementations\ThrowingPropertyTermStore;

/**
 * @covers \Wikibase\Repo\Store\TermStoreRebuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermStoreRebuilderTest extends MediaWikiTestCase {

	/**
	 * @var InMemoryItemTermStore
	 */
	private $itemTermStore;

	/**
	 * @var InMemoryPropertyTermStore
	 */
	private $propertyTermStore;

	/**
	 * @var SpyMessageReporter
	 */
	private $errorReporter;

	public function setUp() {
		parent::setUp();

		$this->itemTermStore = new InMemoryItemTermStore();
		$this->propertyTermStore = new InMemoryPropertyTermStore();
		$this->errorReporter = new SpyMessageReporter();
	}

	public function testStoresAllTerms() {
		$this->newRebuilder()->rebuild();

		$this->assertQ1IsStored();
		$this->assertP1IsStored();
	}

	private function newRebuilder(): TermStoreRebuilder {
		return new TermStoreRebuilder(
			$this->propertyTermStore,
			$this->itemTermStore,
			$this->newIdPager(),
			new SpyMessageReporter(),
			$this->errorReporter,
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$this->newEntityLookup(),
			2,
			0
		);
	}

	private function assertQ1IsStored() {
		$this->assertEquals(
			$this->newQ1()->getFingerprint(),
			$this->itemTermStore->getTerms( new ItemId( 'Q1' ) )
		);
	}

	public function assertP1IsStored() {
		$this->assertEquals(
			$this->newP1()->getFingerprint(),
			$this->propertyTermStore->getTerms( new PropertyId( 'P1' ) )
		);
	}

	private function newIdPager(): MockEntityIdPager {
		$pager = new MockEntityIdPager();

		$pager->addEntityId( new ItemId( 'Q1' ) );
		$pager->addEntityId( new PropertyId( 'P1' ) );

		return $pager;
	}

	private function newEntityLookup(): InMemoryEntityLookup {
		$lookup = new InMemoryEntityLookup();

		$lookup->addEntity( $this->newQ1() );
		$lookup->addEntity( $this->newP1() );

		return $lookup;
	}

	private function newQ1() {
		return new Item(
			new ItemId( 'Q1' ),
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

	public function testErrorsAreReported() {
		$this->itemTermStore = new ThrowingItemTermStore();
		$this->propertyTermStore = new ThrowingPropertyTermStore();

		$this->newRebuilder()->rebuild();

		$this->assertSame(
			[
				'Failed to save terms of entity: Q1',
				'Failed to save terms of entity: P1',
			],
			$this->errorReporter->getMessages()
		);
	}


}
