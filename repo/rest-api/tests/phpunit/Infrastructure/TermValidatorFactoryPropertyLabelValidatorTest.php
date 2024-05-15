<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryPropertyLabelValidator;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryPropertyLabelValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryPropertyLabelValidatorTest extends TestCase {

	private const MAX_LENGTH = 50;

	private TermsCollisionDetector $termsCollisionDetector;

	protected function setUp(): void {
		parent::setUp();

		$this->termsCollisionDetector = $this->createStub( TermsCollisionDetector::class );
	}

	public function testValid(): void {
		$this->assertNull(
			$this->newValidator()->validate( 'en', 'valid label', new TermList( [] ) )
		);
	}

	/**
	 * @dataProvider provideInvalidLabel
	 */
	public function testGivenInvalidDescription_returnsValidationError(
		string $language,
		string $label,
		string $errorCode,
		array $errorContext = []
	): void {
		$this->assertEquals(
			new ValidationError( $errorCode, $errorContext ),
			$this->newValidator()->validate( $language, $label, new TermList( [] ) )
		);
	}

	public static function provideInvalidLabel(): Generator {
		$language = 'en';
		yield 'empty label' => [
			$language,
			'',
			PropertyLabelValidator::CODE_EMPTY,
			[ PropertyLabelValidator::CONTEXT_LANGUAGE => $language ],
		];

		$label = str_repeat( 'a', self::MAX_LENGTH + 1 );
		yield 'label too long' => [
			$language,
			$label,
			PropertyLabelValidator::CODE_TOO_LONG,
			[
				PropertyLabelValidator::CONTEXT_LABEL => $label,
				PropertyLabelValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				PropertyLabelValidator::CONTEXT_LANGUAGE => $language,
			],
		];

		$label = "label with tab character \t not allowed";
		yield 'label has invalid character' => [
			$language,
			$label,
			PropertyLabelValidator::CODE_INVALID,
			[
				PropertyLabelValidator::CONTEXT_LABEL => $label,
				PropertyLabelValidator::CONTEXT_LANGUAGE => $language,
			],
		];
	}

	public function testLabelEqualsDescription_returnsValidationError(): void {
		$languageCode = 'en';
		$description = 'some description';

		$this->assertEquals(
			new ValidationError(
				PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyLabelValidator::CONTEXT_LANGUAGE => $languageCode ]
			),
			$this->newValidator()->validate( $languageCode, $description, new TermList( [ new Term( $languageCode, $description ) ] ) )
		);
	}

	public function testLabelCollision_returnsValidationError(): void {
		$languageCode = 'en';
		$label = 'some label';
		$matchingPropertyId = 'P456';

		$this->termsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$this->termsCollisionDetector
			->expects( $this->once() )
			->method( 'detectLabelCollision' )
			->with( $languageCode, $label )
			->willReturn( new NumericPropertyId( $matchingPropertyId ) );

		$this->assertEquals(
			new ValidationError(
				PropertyLabelValidator::CODE_LABEL_DUPLICATE,
				[
					PropertyLabelValidator::CONTEXT_LANGUAGE => $languageCode,
					PropertyLabelValidator::CONTEXT_LABEL => $label,
					PropertyLabelValidator::CONTEXT_MATCHING_PROPERTY_ID => $matchingPropertyId,
				]
			),
			$this->newValidator()->validate( $languageCode, $label, new TermList( [] ) )
		);
	}

	private function newValidator(): TermValidatorFactoryPropertyLabelValidator {
		return new TermValidatorFactoryPropertyLabelValidator(
			new TermValidatorFactory(
				self::MAX_LENGTH,
				WikibaseRepo::getTermsLanguages()->getLanguages(),
				WikibaseRepo::getEntityIdParser(),
				WikibaseRepo::getTermsCollisionDetectorFactory(),
				WikibaseRepo::getTermLookup(),
				$this->createStub( LanguageNameUtils::class )
			),
			$this->termsCollisionDetector,
		);
	}

}
