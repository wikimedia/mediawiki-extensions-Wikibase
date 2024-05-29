<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItem;

use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItem\PatchedItemValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItem\PatchedItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedItemValidatorTest extends TestCase {

	private ItemDeserializer $itemDeserializer;

	protected function setUp(): void {
		parent::setUp();

		$this->itemDeserializer = $this->createStub( ItemDeserializer::class );
	}

	public function testValid(): void {
		$originalItem = new Item( new ItemId( 'Q123' ), new Fingerprint() );

		$itemSerialization = [
			'id' => 'Q123',
			'type' => 'item',
			'labels' => [ 'ar' => 'بطاطا' ],
		];

		$expectedItem = new Item( new ItemId( 'Q123' ), new Fingerprint( new TermList( [ new Term( 'ar', 'بطاطا' ) ] ) ) );

		$this->itemDeserializer = $this->createStub( ItemDeserializer::class );
		$this->itemDeserializer->method( 'deserialize' )->willReturn( $expectedItem );

		$this->assertEquals(
			$expectedItem,
			$this->newValidator()->validateAndDeserialize( $itemSerialization, $originalItem )
		);
	}

	public function testIgnoresItemIdRemoval(): void {
		$originalItem = new Item( new ItemId( 'Q123' ), new Fingerprint() );

		$patchedItem = [
			'type' => 'item',
			'labels' => [ 'en' => 'potato' ],
		];

		$expectedItem = new Item( new ItemId( 'Q123' ), new Fingerprint( new TermList( [ new Term( 'en', 'potato' ) ] ) ) );

		$this->itemDeserializer = $this->createStub( ItemDeserializer::class );
		$this->itemDeserializer->method( 'deserialize' )->willReturn( $expectedItem );
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
		return new PatchedItemValidator( $this->itemDeserializer );
	}

}
