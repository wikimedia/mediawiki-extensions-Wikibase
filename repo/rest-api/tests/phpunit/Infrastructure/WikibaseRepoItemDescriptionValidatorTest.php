<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoItemDescriptionValidator;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoItemDescriptionValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseRepoItemDescriptionValidatorTest extends TestCase {

	private const MAX_LENGTH = 50;

	private ItemRetriever $itemRetriever;
	private TermsCollisionDetector $termsCollisionDetector;

	protected function setUp(): void {
		parent::setUp();

		$this->termsValidatorFactory = WikibaseRepo::getTermValidatorFactory();
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->termsCollisionDetector = $this->createStub( TermsCollisionDetector::class );
	}

	public function testGivenValidDescription_returnsNull(): void {
		$itemId = new ItemId( 'Q123' );
		$item = NewItem::withId( $itemId )
			->andLabel( 'en', 'Item Label' )
			->andDescription( 'en', 'Item Description' )
			->build();

		$this->createItemRetrieverMock( $itemId, $item );

		$this->assertNull(
			$this->newValidator()->validate( $itemId, 'en', 'valid description' )
		);
	}

	public function testGivenValidDescriptionAndItemWithoutLabel_returnsNull(): void {
		$itemId = new ItemId( 'Q123' );
		$item = NewItem::withId( $itemId )->andDescription( 'en', 'Item Description' )->build();

		$this->createItemRetrieverMock( $itemId, $item );

		$this->assertNull(
			$this->newValidator()->validate( $itemId, 'en', 'valid description' )
		);
	}

	public function testGivenValidDescriptionForNonExistentItem_returnsNull(): void {
		$itemId = new ItemId( 'Q123' );

		$this->createItemRetrieverMock( $itemId, null );

		$this->assertNull(
			$this->newValidator()->validate( $itemId, 'en', 'valid description' )
		);
	}

	/**
	 * @dataProvider provideInvalidDescription
	 */
	public function testGivenInvalidDescription_returnsValidationError(
		string $description,
		string $errorCode,
		array $errorContext = []
	): void {
		$this->assertEquals(
			new ValidationError( $errorCode, $errorContext ),
			$this->newValidator()->validate( new ItemId( 'Q123' ), 'en', $description )
		);
	}

	public function provideInvalidDescription(): Generator {
		yield 'description too short' => [ '', ItemDescriptionValidator::CODE_EMPTY ];

		$description = str_repeat( 'a', self::MAX_LENGTH + 1 );
		yield 'description too long' => [
			$description,
			ItemDescriptionValidator::CODE_TOO_LONG,
			[
				ItemDescriptionValidator::CONTEXT_VALUE => $description,
				ItemDescriptionValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
			],
		];

		$description = "description with tab character \t not allowed";
		yield 'description has invalid character' => [
			$description,
			ItemDescriptionValidator::CODE_INVALID,
			[ ItemDescriptionValidator::CONTEXT_VALUE => $description ],
		];
	}

	public function testGivenDescriptionSameAsLabel_returnsValidationError(): void {
		$itemId = new ItemId( 'Q123' );
		$itemLabel = 'Item Label';
		$language = 'en';
		$item = NewItem::withId( $itemId )
			->andLabel( $language, $itemLabel )
			->andDescription( $language, 'Item Description' )
			->build();

		$this->createItemRetrieverMock( $itemId, $item );

		$this->assertEquals(
			new ValidationError(
				ItemDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ ItemDescriptionValidator::CONTEXT_LANGUAGE => $language ]
			),
			$this->newValidator()->validate( $itemId, $language, $itemLabel )
		);
	}

	public function testGivenLabelDescriptionCollision_returnsValidationError(): void {
		$itemId = new ItemId( 'Q123' );
		$language = 'en';
		$label = 'Item Label';
		$description = 'Item Description';
		$item = NewItem::withId( $itemId )->andLabel( $language, $label )->build();

		$this->createItemRetrieverMock( $itemId, $item );

		$this->termsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$matchingItemId = 'Q789';
		$this->termsCollisionDetector
			->expects( $this->once() )
			->method( 'detectLabelAndDescriptionCollision' )
			->with( $language, $label, $description )
			->willReturn( new ItemId( $matchingItemId ) );

		$this->assertEquals(
			new ValidationError(
				ItemDescriptionValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemDescriptionValidator::CONTEXT_LANGUAGE => $language,
					ItemDescriptionValidator::CONTEXT_LABEL => $label,
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => $description,
					ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,
				]
			),
			$this->newValidator()->validate( $itemId, $language, $description )
		);
	}

	public function testUnchangedDescription_willNotPerformValidation(): void {
		$itemId = new ItemId( 'Q123' );
		$language = 'en';
		$description = 'Item Description';
		$item = NewItem::withId( $itemId )
			->andLabel( $language, 'New Label' )
			->andDescription( $language, $description )
			->build();

		$this->createItemRetrieverMock( $itemId, $item );

		$this->termsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$this->termsCollisionDetector
			->expects( $this->never() )
			->method( 'detectLabelAndDescriptionCollision' );

		$this->assertNull( $this->newValidator()->validate( $itemId, $language, $description ) );
	}

	private function newValidator(): WikibaseRepoItemDescriptionValidator {
		return new WikibaseRepoItemDescriptionValidator(
			$this->newTermValidatorFactory(),
			$this->termsCollisionDetector,
			$this->itemRetriever
		);
	}

	private function newTermValidatorFactory(): TermValidatorFactory {
		return new TermValidatorFactory(
			self::MAX_LENGTH,
			WikibaseRepo::getTermsLanguages()->getLanguages(),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getTermsCollisionDetectorFactory(),
			WikibaseRepo::getTermLookup(),
			$this->createStub( LanguageNameUtils::class )
		);
	}

	private function createItemRetrieverMock( ItemId $itemId, ?Item $item ): void {
		$this->itemRetriever = $this->createMock( ItemRetriever::class );
		$this->itemRetriever
			->expects( $this->once() )
			->method( 'getItem' )
			->with( $itemId )
			->willReturn( $item );
	}

}
