<?php

namespace Wikibase\Lib\Tests\Store;

use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;
use Wikibase\Lib\Store\MatchingTermsLookupFactory;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Lib\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\MatchingTermsLookupFactory
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class MatchingTermsLookupFactoryTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	/**
	 * @var TermsDomainDbFactory
	 */
	private $dbFactory;

	private const MOCK_ITEM_LABELS = [
		'Q100' => 'Hello',
		'Q200' => 'Goodbye',
	];

	protected function setUp(): void {
		parent::setUp();

		$this->setUpDB();
	}

	private function setUpDB(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		$this->dbFactory = $this->getTermsDomainDbFactory();
		$termsDb = $this->dbFactory->newTermsDb();

		$itemTermStoreWriter = new DatabaseItemTermStoreWriter(
			$termsDb,
			$this->getServiceContainer()->getJobQueueGroup(),
			new DatabaseTermInLangIdsAcquirer( $termsDb ),
			new DatabaseTermInLangIdsResolver( $termsDb ),
			new StringNormalizer()
		);

		foreach ( self::MOCK_ITEM_LABELS as $id => $label ) {
			$item = new Item( new ItemId( $id ) );
			$item->setLabel( 'en', $label );

			$itemTermStoreWriter->storeTerms(
				$item->getId(),
				$item->getFingerprint()
			);
		}
	}

	public function testReturnsWorkingLookup() {
		$factory = new MatchingTermsLookupFactory(
			new EntityIdComposer( [
				Item::ENTITY_TYPE => function( $uniquePartOfId ) {
					return new ItemId( 'Q' . $uniquePartOfId );
				},
			] ),
			$this->dbFactory,
			new NullLogger()
		);

		$itemSource = $this->createMock( DatabaseEntitySource::class );
		$itemSource->expects( $this->once() )
			->method( 'getDatabaseName' )
			->willReturn( false ); // false means local db

		$matchingTermsLookup = $factory->getLookupForSource( $itemSource );

		$criteria = new TermIndexSearchCriteria( [
			'termText' => self::MOCK_ITEM_LABELS['Q100'],
		] );

		$actual = $matchingTermsLookup->getMatchingTerms( [ $criteria ] );
		$results = array_map( function ( TermIndexEntry $entry ) {
			return $entry->getEntityId()->getSerialization();
		}, $actual );

		$this->assertContains( 'Q100', $results );
		$this->assertNotContains( 'Q200', $results );
	}
}
