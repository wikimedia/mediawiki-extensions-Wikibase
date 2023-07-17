<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryLabelTextValidator;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoItemLabelValidator;
use Wikibase\Repo\Store\TermsCollisionDetector;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoItemLabelValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseRepoItemLabelValidatorTest extends TestCase {

	private ItemRetriever $itemRetriever;
	private TermsCollisionDetector $termsCollisionDetector;
	private TermValidatorFactoryLabelTextValidator $labelTextValidator;

	protected function setUp(): void {
		parent::setUp();
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->termsCollisionDetector = $this->createStub( TermsCollisionDetector::class );
		$this->labelTextValidator = $this->createStub( TermValidatorFactoryLabelTextValidator::class );
	}

	public function testValid(): void {
		$itemId = new ItemId( 'Q123' );
		$itemLabel = 'valid item label';

		$item = NewItem::withId( $itemId )
			->andLabel( 'en', $itemLabel )
			->build();

		$this->createItemRetrieverMock( $itemId, $item );

		$this->assertNull(
			$this->newValidator()->validate( $itemId, 'en', 'valid label' )
		);
	}

	public function testUnchangedLabel_willNotPerformValidation(): void {
		$itemId = new ItemId( 'Q123' );
		$languageCode = 'en';
		$label = 'some label';

		$item = NewItem::withId( $itemId )->andLabel( $languageCode, $label )->build();
		$this->createItemRetrieverMock( $itemId, $item );

		$this->termsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$this->termsCollisionDetector
			->expects( $this->never() )
			->method( 'detectLabelAndDescriptionCollision' );

		$this->assertNull( $this->newValidator()->validate( $itemId, $languageCode, $label ) );
	}

	public function testGivenInvalidLabelText_returnsValidationError(): void {
		$invalidLabel = '';
		$expectedError = $this->createStub( ValidationError::class );

		$this->labelTextValidator = $this->createMock( TermValidatorFactoryLabelTextValidator::class );
		$this->labelTextValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $invalidLabel )
			->willReturn( $expectedError );

		$this->assertSame(
			$expectedError,
			$this->newValidator()->validate( new ItemId( 'Q123' ), 'en', $invalidLabel )
		);
	}

	public function testLabelEqualsDescription_returnsValidationError(): void {
		$itemId = new ItemId( 'Q123' );
		$language = 'en';
		$description = 'some description';

		$item = NewItem::withId( $itemId )
			->andDescription( 'en', $description )
			->build();

		$this->createItemRetrieverMock( $itemId, $item );

		$this->assertEquals(
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => $language ]
			),
			$this->newValidator()->validate( $itemId, $language, $description )
		);
	}

	public function testLabelDescriptionCollision_returnsValidationError(): void {
		$itemId = new ItemId( 'Q123' );
		$languageCode = 'en';
		$label = 'some label';
		$description = 'some description';
		$matchingItemId = 'Q456';

		$item = NewItem::withId( $itemId )
			->andDescription( $languageCode, $description )
			->build();
		$this->createItemRetrieverMock( $itemId, $item );

		$this->termsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$this->termsCollisionDetector
			->expects( $this->once() )
			->method( 'detectLabelAndDescriptionCollision' )
			->with( $languageCode, $label, $description )
			->willReturn( new ItemId( $matchingItemId ) );

		$this->assertEquals(
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemLabelValidator::CONTEXT_LANGUAGE => $languageCode,
					ItemLabelValidator::CONTEXT_LABEL => $label,
					ItemLabelValidator::CONTEXT_DESCRIPTION => $description,
					ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,
				]

			),
			$this->newValidator()->validate( $itemId, $languageCode, $label )
		);
	}

	private function newValidator(): WikibaseRepoItemLabelValidator {
		return new WikibaseRepoItemLabelValidator(
			$this->labelTextValidator,
			$this->termsCollisionDetector,
			$this->itemRetriever
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
