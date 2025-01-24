<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemDescriptionEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemDescriptionEditRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemDescriptionEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemDescriptionEditRequestValidatingDeserializerTest extends TestCase {

	private InMemoryItemRepository $itemRetriever;
	private ItemDescriptionValidator $itemDescriptionValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRetriever = new InMemoryItemRepository();
		$this->itemRetriever->addItem( NewItem::withId( 'Q123' )->build() );
		$this->itemDescriptionValidator = $this->createStub( ItemDescriptionValidator::class );
	}

	public function testGivenValidRequest_returnsDescription(): void {
		$request = $this->createStub( ItemDescriptionEditRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'root vegetable' );

		$this->assertEquals(
			new Term( 'en', 'root vegetable' ),
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenItemDoesNotExist_skipsValidation(): void {
		$this->itemRetriever = new InMemoryItemRepository();

		$this->itemDescriptionValidator = $this->createMock( ItemDescriptionValidator::class );
		$this->itemDescriptionValidator->expects( $this->never() )->method( 'validate' );

		$request = $this->createStub( ItemDescriptionEditRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'root vegetable' );

		$this->assertEquals(
			new Term( 'en', 'root vegetable' ),
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenDescriptionIsUnchanged_skipsValidation(): void {
		$itemId = 'Q345';
		$languageCode = 'en';
		$description = 'root vegetable';

		$this->itemRetriever = new InMemoryItemRepository();
		$this->itemRetriever->addItem( NewItem::withId( $itemId )->andDescription( $languageCode, $description )->build() );
		$this->itemDescriptionValidator = $this->createMock( ItemDescriptionValidator::class );
		$this->itemDescriptionValidator->expects( $this->never() )->method( 'validate' );

		$request = $this->createStub( ItemDescriptionEditRequest::class );
		$request->method( 'getItemId' )->willReturn( $itemId );
		$request->method( 'getLanguageCode' )->willReturn( $languageCode );
		$request->method( 'getDescription' )->willReturn( $description );

		$this->assertEquals(
			new Term( $languageCode, $description ),
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider invalidDescriptionProvider
	 */
	public function testWithInvalidDescription(
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedContext = []
	): void {
		$request = $this->createStub( ItemDescriptionEditRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'my description' );

		$this->itemDescriptionValidator = $this->createStub( ItemDescriptionValidator::class );
		$this->itemDescriptionValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidatingDeserializer()->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( $expectedErrorCode, $error->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $error->getErrorMessage() );
			$this->assertSame( $expectedContext, $error->getErrorContext() );
		}
	}

	public static function invalidDescriptionProvider(): Generator {
		yield 'description empty' => [
			new ValidationError( ItemDescriptionValidator::CODE_EMPTY ),
			UseCaseError::INVALID_VALUE,
			"Invalid value at '/description'",
			[ UseCaseError::CONTEXT_PATH => '/description' ],
		];

		$limit = 40;
		yield 'description too long' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_TOO_LONG,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => 'description that is too long...',
					ItemDescriptionValidator::CONTEXT_LIMIT => $limit,
				]
			),
			UseCaseError::VALUE_TOO_LONG,
			'The input value is too long',
			[
				UseCaseError::CONTEXT_PATH => '/description',
				UseCaseError::CONTEXT_LIMIT => $limit,
			],
		];

		yield 'invalid description' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_INVALID,
				[ ItemDescriptionValidator::CONTEXT_DESCRIPTION => "tab characters \t not allowed" ],
			),
			UseCaseError::INVALID_VALUE,
			"Invalid value at '/description'",
			[ UseCaseError::CONTEXT_PATH => '/description' ],
		];

		$language = 'en';
		yield 'label and description are equal' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL,
				[ ItemDescriptionValidator::CONTEXT_LANGUAGE => $language ],
			),
			UseCaseError::DATA_POLICY_VIOLATION,
			'Edit violates data policy',
			[
				UseCaseError::CONTEXT_VIOLATION => UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
				UseCaseError::CONTEXT_VIOLATION_CONTEXT => [ UseCaseError::CONTEXT_LANGUAGE => $language ],
			],
		];

		$language = 'en';
		$conflictingItemId = 'Q213';
		yield 'label and description duplicate' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE,
				[
					ItemDescriptionValidator::CONTEXT_LANGUAGE => $language,
					ItemDescriptionValidator::CONTEXT_LABEL => 'test label',
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => 'test description',
					ItemDescriptionValidator::CONTEXT_CONFLICTING_ITEM_ID => $conflictingItemId,

				],
			),
			UseCaseError::DATA_POLICY_VIOLATION,
			'Edit violates data policy',
			[
				UseCaseError::CONTEXT_VIOLATION => UseCaseError::POLICY_VIOLATION_ITEM_LABEL_DESCRIPTION_DUPLICATE,
				UseCaseError::CONTEXT_VIOLATION_CONTEXT => [
					UseCaseError::CONTEXT_LANGUAGE => $language,
					UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => $conflictingItemId,
				],
			],
		];
	}

	private function newValidatingDeserializer(): ItemDescriptionEditRequestValidatingDeserializer {
		return new ItemDescriptionEditRequestValidatingDeserializer(
			$this->itemDescriptionValidator,
			$this->itemRetriever
		);
	}

}
