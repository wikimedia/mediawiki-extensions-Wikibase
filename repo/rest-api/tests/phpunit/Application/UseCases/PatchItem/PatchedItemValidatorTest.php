<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItem;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItem\PatchedItemValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItem\PatchedItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedItemValidatorTest extends TestCase {

	private const ALLOWED_BADGES = [ 'Q999' ];

	/**
	 * @dataProvider patchedItemProvider
	 */
	public function testValid( array $patchedItemSerialization, Item $expectedPatchedItem ): void {
		$originalItem = new Item( new ItemId( 'Q123' ), new Fingerprint() );

		$this->assertEquals(
			$expectedPatchedItem,
			$this->newValidator()->validateAndDeserialize( $patchedItemSerialization, $originalItem )
		);
	}

	public static function patchedItemProvider(): Generator {
		yield 'minimal item' => [
			[ 'id' => 'Q123' ],
			new Item( new ItemId( 'Q123' ) ),
		];
		yield 'item with all fields' => [
			[
				'id' => 'Q123',
				'type' => 'item',
				'labels' => [ 'en' => 'english-label' ],
				'descriptions' => [ 'en' => 'english-description' ],
				'aliases' => [ 'en' => [ 'english-alias' ] ],
				'sitelinks' => [ 'enwiki' => [ 'title' => 'potato' ] ],
				'statements' => [
					'P321' => [
						[
							'property' => [ 'id' => 'P321' ],
							'value' => [ 'type' => 'somevalue' ],
						],
					],
				],
			],
			new Item(
				new ItemId( 'Q123' ),
				new Fingerprint(
					new TermList( [ new Term( 'en', 'english-label' ) ] ),
					new TermList( [ new Term( 'en', 'english-description' ) ] ),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'english-alias' ] ) ] )
				),
				new SitelinkList( [ new SiteLink( 'enwiki', 'potato' ) ] ),
				new StatementList( NewStatement::someValueFor( 'P321' )->build() )
			),
		];
	}

	public function testIgnoresItemIdRemoval(): void {
		$originalItem = new Item( new ItemId( 'Q123' ), new Fingerprint() );

		$patchedItem = [
			'type' => 'item',
			'labels' => [ 'en' => 'potato' ],
		];

		$validatedItem = $this->newValidator()->validateAndDeserialize( $patchedItem, $originalItem );

		$this->assertEquals( $originalItem->getId(), $validatedItem->getId() );
	}

	/**
	 * @dataProvider topLevelValidationProvider
	 */
	public function testTopLevelValidationError_throws( array $patchedItem, Exception $expectedError ): void {
		$originalItem = new Item(
			new ItemId( 'Q123' ),
			new Fingerprint(),
		);

		try {
			$this->newValidator()->validateAndDeserialize( $patchedItem, $originalItem );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public function topLevelValidationProvider(): Generator {
		yield 'unexpected field' => [
			[
				'id' => 'Q123',
				'type' => 'item',
				'labels' => [ 'de' => 'Kartoffel' ],
				'foo' => 'bar',
			],
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_UNEXPECTED_FIELD,
				"The patched item contains an unexpected field: 'foo'"
			),
		];

		yield 'invalid field' => [
			[
				'id' => 'Q123',
				'type' => 'item',
				'labels' => 'invalid-labels',
			],
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_INVALID_FIELD,
				"Invalid input for 'labels' in the patched item",
				[ UseCaseError::CONTEXT_PATH => 'labels', UseCaseError::CONTEXT_VALUE => 'invalid-labels' ]
			),
		];

		yield "Illegal modification 'id' field" => [
			[
				'id' => 'Q12',
				'type' => 'item',
				'labels' => [ 'en' => 'potato' ],
			],
			new UseCaseError(
				UseCaseError::PATCHED_ITEM_INVALID_OPERATION_CHANGE_ITEM_ID,
				'Cannot change the ID of the existing item'
			),
		];
	}

	private function newValidator(): PatchedItemValidator {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);
		return new PatchedItemValidator(
			new LabelsDeserializer(),
			new DescriptionsDeserializer(),
			new AliasesDeserializer(),
			new SitelinkDeserializer(
				'/\?/',
				self::ALLOWED_BADGES,
				new SameTitleSitelinkTargetResolver(),
				new DummyItemRevisionMetaDataRetriever()
			),
			new StatementsDeserializer(
				new StatementDeserializer( $propValPairDeserializer, $this->createStub( ReferenceDeserializer::class ) )
			)
		);
	}

}
