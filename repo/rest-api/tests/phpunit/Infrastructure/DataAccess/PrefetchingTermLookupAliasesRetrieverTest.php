<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\NullPrefetchingTermLookup;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\Tests\FakePrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\PrefetchingTermLookupAliasesRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\PrefetchingTermLookupAliasesRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingTermLookupAliasesRetrieverTest extends TestCase {

	private const ALL_TERM_LANGUAGES = [ 'de', 'en', 'ar' ];
	private const ITEM_ID = 'Q123';
	private const PROPERTY_ID = 'P123';

	/**
	 * @dataProvider provideEntityId
	 */
	public function testGetAliases( EntityId $entityId ): void {
		$aliasesRetriever = new PrefetchingTermLookupAliasesRetriever(
			new FakePrefetchingTermLookup(),
			new StaticContentLanguages( self::ALL_TERM_LANGUAGES )
		);

		$aliases = $aliasesRetriever->getAliases( $entityId );

		$aliasesReadModel = new Aliases(
			new AliasesInLanguage(
				'de',
				[ "{$entityId->getSerialization()} de alias 1", "{$entityId->getSerialization()} de alias 2" ]
			),
			new AliasesInLanguage(
				'en',
				[ "{$entityId->getSerialization()} en alias 1", "{$entityId->getSerialization()} en alias 2" ]
			),
			new AliasesInLanguage(
				'ar',
				[ "{$entityId->getSerialization()} ar alias 1", "{$entityId->getSerialization()} ar alias 2" ]
			),
		);

		$this->assertEquals( $aliasesReadModel, $aliases );
	}

	/**
	 * @dataProvider provideEntityId
	 */
	public function testGetAliasesForSpecificLanguages( EntityId $entityId ): void {
		$languages = [ 'en', 'de' ];

		$prefetchingTermLookup = $this->createStub( PrefetchingTermLookup::class );
		$prefetchingTermLookup->method( 'getPrefetchedAliases' )
			->willReturnMap( [
				[ $entityId, 'en', [ "{$entityId->getSerialization()} en alias 1", "{$entityId->getSerialization()} en alias 2" ] ],
				[ $entityId, 'de', false ],
			] );

		$aliasesRetriever = new PrefetchingTermLookupAliasesRetriever(
			$prefetchingTermLookup,
			new StaticContentLanguages( $languages )
		);

		$aliases = $aliasesRetriever->getAliases( $entityId );

		$this->assertCount( 1, $aliases );
		$this->assertArrayHasKey( 'en', $aliases );
		$this->assertArrayNotHasKey( 'de', $aliases );
	}

	public function testGivenLanguageCodeWithNoAliasesFor_getAliasesInLanguageReturnsNull(): void {
		$itemId = new ItemId( self::ITEM_ID );
		$languageCode = 'de';

		$aliasesRetriever = new PrefetchingTermLookupAliasesRetriever(
			new NullPrefetchingTermLookup(),
			new StaticContentLanguages( [ $languageCode ] )
		);

		$aliasesInLanguage = $aliasesRetriever->getAliasesInLanguage( $itemId, $languageCode );
		$this->assertNull( $aliasesInLanguage );
	}

	public function testGetAliasesInLanguage(): void {
		$itemId = new ItemId( self::ITEM_ID );
		$languageCode = 'en';

		$aliasesRetriever = new PrefetchingTermLookupAliasesRetriever(
			new FakePrefetchingTermLookup(),
			new StaticContentLanguages( [ $languageCode ] )
		);

		$aliasesInLanguage = $aliasesRetriever->getAliasesInLanguage( $itemId, $languageCode );

		$this->assertEquals(
			new AliasesInLanguage( 'en', [ 'Q123 en alias 1', 'Q123 en alias 2' ] ),
			$aliasesInLanguage
		);
	}

	public function provideEntityId(): Generator {
		yield 'item id' => [ new ItemId( self::ITEM_ID ) ];
		yield 'property id' => [ new NumericPropertyId( self::PROPERTY_ID ) ];
	}

}
