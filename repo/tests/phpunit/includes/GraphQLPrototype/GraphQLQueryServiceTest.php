<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\GraphQLPrototype;

use DataValues\TimeValue;
use GraphQL\GraphQL;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\GraphQLPrototype\GraphQLQueryService;
use Wikibase\Repo\GraphQLPrototype\ItemResolver;
use Wikibase\Repo\GraphQLPrototype\LabelsResolver;
use Wikibase\Repo\GraphQLPrototype\Schema;
use Wikibase\Repo\GraphQLPrototype\StatementsResolver;
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
	private static Property $stringProperty;

	public static function setUpBeforeClass(): void {
		if ( !class_exists( GraphQL::class ) ) {
			self::markTestSkipped( 'Needs webonyx/graphql-php to run' );
		}
	}

	public function addDBDataOnce() {
		$stringProperty = new Property(
			null,
			new Fingerprint( new TermList( [
				new Term( 'en', 'instance of' ),
				new Term( 'de', 'ist ein(e)' ),
			] ) ),
			'string',
		);
		$this->saveEntity( $stringProperty );
		self::$stringProperty = $stringProperty;

		$timeProperty = new Property(
			null,
			new Fingerprint( new TermList( [ new Term( 'en', 'date of birth' ) ] ) ),
			'time',
		);
		$this->saveEntity( $timeProperty );

		$item = NewItem::withLabel( 'en', 'potato' )
			->andLabel( 'de', 'Kartoffel' )
			->andStatement(
				NewStatement::forProperty( $stringProperty->getId() )
					->withValue( 'potato value' )
					->build()
			)
			->andStatement( NewStatement::noValueFor( $stringProperty->getId() )->build() )
			->andStatement( NewStatement::someValueFor( $stringProperty->getId() )->build() )
			->andStatement( // this statement will get filtered out, because we don't support time values yet
				NewStatement::forProperty( $timeProperty->getId() )
					->withValue( new TimeValue(
						'+2025-01-01T00:00:00Z',
						0,
						0,
						0,
						TimeValue::PRECISION_DAY,
						TimeValue::CALENDAR_GREGORIAN
					) )
					->build()
			)
			->build();
		$this->saveEntity( $item );
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

	public function testStatementsQueryWithPropertyLabels(): void {
		$itemId = self::$item->getId()->getSerialization();
		$enLabel = self::$stringProperty->getLabels()->getByLanguage( 'en' )->getText();
		$deLabel = self::$stringProperty->getLabels()->getByLanguage( 'de' )->getText();
		$expectedStatementData = [
			'property' => [
				'id' => self::$stringProperty->getId()->getSerialization(),
				'labels' => [
					'en' => $enLabel,
					'de' => $deLabel,
				],
			],
		];

		$this->assertNotNull( $enLabel );
		$this->assertEquals(
			[ 'data' => [ 'item' => [
				'statements' => [
					$expectedStatementData, // 3x the same because there's a value, a novalue and a somevalue statement
					$expectedStatementData,
					$expectedStatementData,
				],
			] ] ],
			$this->newGraphQLService()->query( "
			query {
				item(id: \"$itemId\") {
					statements {
						property {
							id
							labels { en de }
						}
					}
				}
			}" )
		);
	}

	public function testInvalidItemId(): void {
		$result = $this->newGraphQLService()->query( 'query { item(id: "X123") { id } }' );
		$this->assertSame(
			"Invalid Item ID: 'X123'.",
			$result['errors'][0]['message']
		);
	}

	public function testItemNotFound(): void {
		$result = $this->newGraphQLService()->query( 'query { item(id: "Q999999") { id } }' );
		$this->assertSame(
			"Item 'Q999999' does not exist.",
			$result['errors'][0]['message']
		);
	}

	public function testStatementsQueryWithStringValue(): void {
		$itemId = self::$item->getId()->getSerialization();
		$value = self::$item->getStatements()
			->getByPropertyId( self::$stringProperty->getId() )->toArray()[0]
			->getMainSnak()->getDataValue()->getValue();

		$this->assertNotNull( $value );
		$this->assertEquals(
			[ 'data' => [ 'item' => [
				'statements' => [
					[
						'property' => [
							'id' => self::$stringProperty->getId()->getSerialization(),
						],
						'value' => [ 'content' => $value ],
					],
					[
						'property' => [
							'id' => self::$stringProperty->getId()->getSerialization(),
						],
						'value' => new \stdClass(),
					],
					[
						'property' => [
							'id' => self::$stringProperty->getId()->getSerialization(),
						],
						'value' => new \stdClass(),
					],
				],
			] ] ],
			$this->newGraphQLService()->query( "
			query {
				item(id: \"$itemId\") {
					statements {
						property { id }
						value {
							... on Value { content }
						}
					}
				}
			}" )
		);
	}

	public function newGraphQLService(): GraphQLQueryService {
		$entityLookup = WikibaseRepo::getEntityLookup();

		return new GraphQLQueryService( new Schema(
			WikibaseRepo::getTermsLanguages(),
			new LabelsResolver( WikibaseRepo::getPrefetchingTermLookup() ),
			new StatementsResolver( $entityLookup ),
			new ItemResolver( $entityLookup ),
		) );
	}

	public function saveEntity( EntityDocument $entity ): void {
		WikibaseRepo::getEntityStore()->saveEntity(
			$entity,
			__CLASS__,
			$this->getTestUser()->getUser(),
			EDIT_NEW
		);
	}

}
