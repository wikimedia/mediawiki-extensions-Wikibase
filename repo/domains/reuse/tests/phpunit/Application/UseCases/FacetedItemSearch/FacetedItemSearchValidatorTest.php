<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Application\UseCases\FacetedItemSearch;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchRequest;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchValidator;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Reuse\Domain\Model\AndOperation;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValueFilter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Application\UseCases\FacetedItemSearch\FacetedItemSearchValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FacetedItemSearchValidatorTest extends TestCase {
	private const STRING_PROPERTY = 'P1';
	private const ITEM_PROPERTY = 'P2';
	private const EXTERNAL_ID_PROPERTY = 'P3';
	private const PROPERTY_PROPERTY = 'P4';

	/**
	 * @dataProvider invalidQueryProvider
	 */
	public function testGivenInvalidQuery_validateThrows( array $query, string $expectedError ): void {
		try {
			$this->newValidator()->validate( new FacetedItemSearchRequest( $query ) );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e->getMessage() );
		}
	}

	public static function invalidQueryProvider(): Generator {
		yield 'no fields' => [
			[],
			"Query filters must contain either an 'and' or a 'property' field",
		];

		yield 'no fields in nested filter' => [
			[ 'and' => [ [], [ 'property' => self::STRING_PROPERTY ] ] ],
			"Query filters must contain either an 'and' or a 'property' field",
		];

		yield 'empty "and"' => [
			[ 'and' => [] ],
			"'and' fields must contain at least two elements",
		];

		yield '"and" with only one element' => [
			[ 'and' => [ [ 'property' => 'P1' ] ] ],
			"'and' fields must contain at least two elements",
		];

		yield 'both "property" and "and"' => [
			[ 'property' => 'P1', 'and' => [ [ 'property' => 'P2' ], [ 'property' => 'P3' ] ] ],
			"Filters must not contain both an 'and' and a 'property' field",
		];

		yield 'unsupported property data type' => [
			[ 'property' => self::PROPERTY_PROPERTY ],
			"Data type of Property '" . self::PROPERTY_PROPERTY . "' is not supported",
		];

		yield 'unknown property data type' => [
			[ 'property' => 'P99999' ],
			"Data type of Property 'P99999' is not supported",
		];
	}

	/**
	 * @dataProvider validQueryProvider
	 */
	public function testGivenValidQuery_getValidatedQueryReturnsQuery( array $rawQuery, AndOperation|PropertyValueFilter $expected ): void {
		$validator = $this->newValidator();
		$validator->validate( new FacetedItemSearchRequest( $rawQuery ) );
		$this->assertEquals( $expected, $validator->getValidatedQuery() );
	}

	public static function validQueryProvider(): Generator {
		yield 'property-only query with string property' => [
			[ 'property' => self::STRING_PROPERTY ],
			new PropertyValueFilter( new NumericPropertyId( self::STRING_PROPERTY ) ),
		];

		yield 'property-only query with item property' => [
			[ 'property' => self::ITEM_PROPERTY ],
			new PropertyValueFilter( new NumericPropertyId( self::ITEM_PROPERTY ) ),
		];

		yield 'property-only query with external id property' => [
			[ 'property' => self::EXTERNAL_ID_PROPERTY ],
			new PropertyValueFilter( new NumericPropertyId( self::EXTERNAL_ID_PROPERTY ) ),
		];

		yield 'property and value query' => [
			[ 'property' => self::STRING_PROPERTY, 'value' => 'potato' ],
			new PropertyValueFilter( new NumericPropertyId( self::STRING_PROPERTY ), 'potato' ),
		];

		yield 'nested query' => [
			[
				'and' => [
					[ 'property' => self::STRING_PROPERTY ],
					[
						'and' => [
							[ 'property' => self::ITEM_PROPERTY, 'value' => 'Q123' ],
							[ 'property' => self::STRING_PROPERTY ],
						],
					],
				],
			],
			new AndOperation( [
				new PropertyValueFilter( new NumericPropertyId( self::STRING_PROPERTY ) ),
				new AndOperation( [
					new PropertyValueFilter(
						new NumericPropertyId( self::ITEM_PROPERTY ),
						'Q123'
					),
					new PropertyValueFilter(
						new NumericPropertyId( self::STRING_PROPERTY ),
					),
				] ),
			] ),
		];
	}

	public function newValidator(): FacetedItemSearchValidator {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::STRING_PROPERTY ), 'string' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::ITEM_PROPERTY ), 'wikibase-item' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::EXTERNAL_ID_PROPERTY ), 'external-id' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::PROPERTY_PROPERTY ), 'wikibase-property' );

		return new FacetedItemSearchValidator( $dataTypeLookup, WikibaseRepo::getDataTypeDefinitions()->getValueTypes() );
	}
}
