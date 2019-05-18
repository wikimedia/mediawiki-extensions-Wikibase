<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use Onoi\MessageReporter\SpyMessageReporter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\ItemLookup;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Store\ItemTermsRebuilder;
use Wikibase\TermStore\Implementations\InMemoryItemTermStore;
use Wikibase\TermStore\Implementations\ThrowingItemTermStore;

/**
 * @covers \Wikibase\Repo\Store\ItemTermsRebuilder
 *
 * @group Wikibase
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 */
class ItemTermsRebuilderTest extends MediaWikiTestCase {

	/**
	 * @var InMemoryItemTermStore
	 */
	private $itemTermStore;

	/**
	 * @var SpyMessageReporter
	 */
	private $errorReporter;

	/**
	 * @var SpyMessageReporter
	 */
	private $progressReporter;

	private $itemIds;

	public function setUp() {
		parent::setUp();

		$this->itemTermStore = new InMemoryItemTermStore();
		$this->errorReporter = new SpyMessageReporter();
		$this->progressReporter = new SpyMessageReporter();

		$this->itemIds = [
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		];
	}

	public function testStoresAllTerms() {
		$this->newRebuilder()->rebuild();

		$this->assertQ1IsStored();
		$this->assertQ2IsStored();
	}

	private function newRebuilder(): ItemTermsRebuilder {
		return new ItemTermsRebuilder(
			$this->itemTermStore,
			$this->itemIds,
			$this->progressReporter,
			$this->errorReporter,
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$this->newItemLookup(),
			1,
			0
		);
	}

	public function assertQ1IsStored() {
		$this->assertEquals(
			$this->newQ1()->getFingerprint(),
			$this->itemTermStore->getTerms( new ItemId( 'Q1' ) )
		);
	}

	private function assertQ2IsStored() {
		$this->assertEquals(
			$this->newQ2()->getFingerprint(),
			$this->itemTermStore->getTerms( new ItemId( 'Q2' ) )
		);
	}

	private function newItemLookup(): ItemLookup {
		$lookup = new InMemoryEntityLookup();

		$lookup->addEntity( $this->newQ1() );
		$lookup->addEntity( $this->newQ2() );

		return $lookup;
	}

	private function newQ1() {
		return new Item(
			new ItemId( 'Q1' ),
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'EnglishItemLabel' ),
					new Term( 'de', 'GermanItemLabel' ),
					new Term( 'nl', 'DutchItemLabel' ),
				] )
			)
		);
	}

	private function newQ2() {
		return new Item(
			new ItemId( 'Q2' ),
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

	public function testErrorsAreReported() {
		$this->itemTermStore = new ThrowingItemTermStore();

		$this->newRebuilder()->rebuild();

		$this->assertSame(
			[
				'Failed to save terms of item: Q1',
				'Failed to save terms of item: Q2',
			],
			$this->errorReporter->getMessages()
		);
	}

	public function testProgressIsReportedEachBatch() {
		$this->newRebuilder()->rebuild();

		$this->assertSame(
			[
				'Rebuilding Q1 till Q1',
				'Rebuilding Q2 till Q2',
			],
			$this->progressReporter->getMessages()
		);
	}

	public function testNonExistentItemsDoNotCauseAnyErrors() {
		$this->itemIds[] = new ItemId( 'Q3' );
		$this->itemIds[] = new ItemId( 'Q4' );

		$this->newRebuilder()->rebuild();

		$this->assertLastMessageContains( 'Q4' );
	}

	private function assertLastMessageContains( $expectedString ) {
		$messages = $this->progressReporter->getMessages();

		$this->assertContains(
			$expectedString,
			end( $messages )
		);
	}

}
