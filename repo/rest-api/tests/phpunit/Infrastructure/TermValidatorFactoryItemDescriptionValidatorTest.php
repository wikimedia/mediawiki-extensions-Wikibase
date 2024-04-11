<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryItemDescriptionValidator;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryItemDescriptionValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryItemDescriptionValidatorTest extends TestCase {

	private const MAX_LENGTH = 50;

	private TermsCollisionDetector $termsCollisionDetector;

	protected function setUp(): void {
		parent::setUp();

		$this->termsCollisionDetector = $this->createStub( TermsCollisionDetector::class );
	}

	public function testGivenValidDescription_returnsNull(): void {
		$this->assertNull(
			$this->newValidator()->validate(
				'en',
				'new valid description',
				new TermList( [ new Term( 'en', 'valid item label' ) ] )
			)
		);
	}

	public function testGivenValidDescriptionAndItemWithoutLabel_returnsNull(): void {
		$this->assertNull(
			$this->newValidator()->validate(
				'en',
				'new valid description',
				new TermList( [] )
			)
		);
	}

	/**
	 * @dataProvider provideInvalidDescription
	 */
	public function testGivenInvalidDescription_returnsValidationError(
		string $description,
		string $language,
		string $errorCode,
		array $errorContext = []
	): void {
		$this->assertEquals(
			new ValidationError( $errorCode, $errorContext ),
			$this->newValidator()->validate( $language, $description, new TermList( [] ) )
		);
	}

	public static function provideInvalidDescription(): Generator {
		yield 'description too short' => [
			'',
			'en',
			ItemDescriptionValidator::CODE_EMPTY,
			[ ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en' ],
		];

		$description = str_repeat( 'a', self::MAX_LENGTH + 1 );
		yield 'description too long' => [
			$description,
			'en',
			ItemDescriptionValidator::CODE_TOO_LONG,
			[
				ItemDescriptionValidator::CONTEXT_DESCRIPTION => $description,
				ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
				ItemDescriptionValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
			],
		];

		$description = "description with tab character \t not allowed";
		yield 'description has invalid character' => [
			$description,
			'en',
			ItemDescriptionValidator::CODE_INVALID,
			[
				ItemDescriptionValidator::CONTEXT_DESCRIPTION => $description,
				ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
			],
		];
	}

	public function testGivenDescriptionSameAsLabel_returnsValidationError(): void {
		$label = 'Item Label';
		$language = 'en';

		$this->assertEquals(
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL,
				[ ItemDescriptionValidator::CONTEXT_LANGUAGE => $language ]
			),
			$this->newValidator()->validate(
				$language,
				$label,
				new TermList( [ new Term( $language, $label ) ] )
			)
		);
	}

	public function testGivenLabelDescriptionCollision_returnsValidationError(): void {
		$language = 'en';
		$label = 'Item Label';
		$description = 'Item Description';

		$this->termsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$matchingItemId = 'Q789';
		$this->termsCollisionDetector
			->expects( $this->once() )
			->method( 'detectLabelAndDescriptionCollision' )
			->with( $language, $label, $description )
			->willReturn( new ItemId( $matchingItemId ) );

		$this->assertEquals(
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE,
				[
					ItemDescriptionValidator::CONTEXT_LANGUAGE => $language,
					ItemDescriptionValidator::CONTEXT_LABEL => $label,
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => $description,
					ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,
				]
			),
			$this->newValidator()->validate(
				$language,
				$description,
				new TermList( [ new Term( $language, $label ) ] )
			)
		);
	}

	private function newValidator(): ItemDescriptionValidator {
		return new TermValidatorFactoryItemDescriptionValidator(
			$this->newTermValidatorFactory(),
			$this->termsCollisionDetector
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

}
