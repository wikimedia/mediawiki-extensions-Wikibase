<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\GraphQLPrototype;

use GraphQL\GraphQL;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\GraphQLPrototype\GraphQLQueryService;
use Wikibase\Repo\GraphQLPrototype\LabelsResolver;
use Wikibase\Repo\GraphQLPrototype\Schema;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\GraphQLPrototype\GraphQLQueryService
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class GraphQLQueryServiceTest extends MediaWikiIntegrationTestCase {
	private static Item $item;

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	public function addDBDataOnce() {
		$item = NewItem::withLabel( 'en', 'potato' )
			->andLabel( 'de', 'Kartoffel' )
			->build();

		WikibaseRepo::getEntityStore()->saveEntity(
			$item,
			__CLASS__,
			$this->getTestUser()->getUser(),
			EDIT_NEW
		);

		self::$item = $item;
	}

	public function testIdQuery(): void {
		$itemId = self::$item->getId()->getSerialization();

		$this->assertNotNull( $itemId );
		$this->assertEquals(
			[ 'data' => [ 'item' => [ 'id' => $itemId ] ] ],
			$this->newGraphQLService()->query( "query { item(id: \"$itemId\") { id } }" )
		);
	}

	public function testLabelsQuery(): void {
		$itemId = self::$item->getId()->getSerialization();
		$enLabel = self::$item->getLabels()->getByLanguage( 'en' )->getText();
		$deLabel = self::$item->getLabels()->getByLanguage( 'de' )->getText();

		$this->assertNotNull( $enLabel );
		$this->assertEquals(
			[ 'data' => [ 'item' => [ 'labels' => [
				'en' => $enLabel,
				'de' => $deLabel,
			] ] ] ],
			$this->newGraphQLService()->query( "
			query {
				item(id: \"$itemId\") {
					labels { en de }
				}
			}" )
		);
	}

	public function newGraphQLService(): GraphQLQueryService {
		return new GraphQLQueryService( new Schema(
			WikibaseRepo::getTermsLanguages(),
			new LabelsResolver( WikibaseRepo::getPrefetchingTermLookup() )
		) );
	}

}
