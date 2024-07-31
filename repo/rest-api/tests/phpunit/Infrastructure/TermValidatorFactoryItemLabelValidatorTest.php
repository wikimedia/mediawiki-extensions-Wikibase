<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryItemLabelValidator;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryItemLabelValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryItemLabelValidatorTest extends TestCase {

	private const MAX_LENGTH = 50;
	private TermsCollisionDetector $termsCollisionDetector;

	protected function setUp(): void {
		parent::setUp();
		$this->termsCollisionDetector = $this->createStub( TermsCollisionDetector::class );
	}

	public function testValid(): void {
		$this->assertNull(
			$this->newValidator()->validate( 'en', 'new valid label', new TermList( [] ) )
		);
	}

	/**
	 * @dataProvider provideInvalidLabel
	 */
	public function testGivenInvalidLabel_returnsValidationError(
		string $label,
		string $language,
		string $errorCode,
		array $errorContext = []
	): void {
		$this->assertEquals(
			new ValidationError( $errorCode, $errorContext ),
			$this->newValidator()->validate( $language, $label, new TermList( [] ) )
		);
	}

	public static function provideInvalidLabel(): Generator {
		yield 'label too short' => [ '', 'en', ItemLabelValidator::CODE_EMPTY, [ ItemLabelValidator::CONTEXT_LANGUAGE => 'en' ] ];

		$label = str_repeat( 'a', self::MAX_LENGTH + 1 );
		yield 'label too long' => [
			$label,
			'en',
			ItemLabelValidator::CODE_TOO_LONG,
			[
				ItemLabelValidator::CONTEXT_LABEL => $label,
				ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
				ItemLabelValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
			],
		];

		$description = "label with tab character \t not allowed";
		yield 'label has invalid character' => [
			$description,
			'en',
			ItemLabelValidator::CODE_INVALID,
			[ ItemLabelValidator::CONTEXT_LABEL => $description, ItemLabelValidator::CONTEXT_LANGUAGE => 'en' ],
		];
	}

	public function testLabelEqualsDescription_returnsValidationError(): void {
		$language = 'en';
		$description = 'some description';

		$this->assertEquals(
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => $language ]
			),
			$this->newValidator()->validate(
				$language,
				$description,
				new TermList( [ new Term( $language, $description ) ] )
			)
		);
	}

	public function testLabelDescriptionCollision_returnsValidationError(): void {
		$languageCode = 'en';
		$label = 'some label';
		$description = 'some description';
		$conflictingItemId = 'Q456';

		$this->termsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$this->termsCollisionDetector
			->expects( $this->once() )
			->method( 'detectLabelAndDescriptionCollision' )
			->with( $languageCode, $label, $description )
			->willReturn( new ItemId( $conflictingItemId ) );

		$this->assertEquals(
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemLabelValidator::CONTEXT_LANGUAGE => $languageCode,
					ItemLabelValidator::CONTEXT_LABEL => $label,
					ItemLabelValidator::CONTEXT_DESCRIPTION => $description,
					ItemLabelValidator::CONTEXT_CONFLICTING_ITEM_ID => $conflictingItemId,
				]

			),
			$this->newValidator()->validate(
				$languageCode,
				$label,
				new TermList( [ new Term( $languageCode, $description ) ] )
			)
		);
	}

	private function newValidator(): TermValidatorFactoryItemLabelValidator {
		return new TermValidatorFactoryItemLabelValidator(
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
