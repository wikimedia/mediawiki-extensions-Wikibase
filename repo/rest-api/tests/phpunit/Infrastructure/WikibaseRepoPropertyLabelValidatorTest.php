<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoPropertyLabelValidator;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoPropertyLabelValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseRepoPropertyLabelValidatorTest extends TestCase {

	private const MAX_LENGTH = 50;

	private PropertyRetriever $propertyRetriever;
	private TermsCollisionDetector $termsCollisionDetector;

	protected function setUp(): void {
		parent::setUp();
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->termsCollisionDetector = $this->createStub( TermsCollisionDetector::class );
	}

	public function testValid(): void {
		$propertyId = new NumericPropertyId( 'P123' );

		$property = new Property( $propertyId,
			new Fingerprint( new TermList( [ new Term( 'en', 'property label' ) ] ) ),
			'string'
		);

		$this->createPropertyRetrieverMock( $propertyId, $property );

		$this->assertNull(
			$this->newValidator()->validate( $propertyId, 'en', 'valid label' )
		);
	}

	public function testGivenValidLabelForNonExistentProperty_returnsNull(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$this->createPropertyRetrieverMock( $propertyId, null );
		$this->assertNull(
			$this->newValidator()->validate( $propertyId, 'en', 'valid label' )
		);
	}

	public function testUnchangedLabel_willNotPerformValidation(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$languageCode = 'en';
		$propertyLabel = 'some property label';

		$property = new Property( $propertyId,
			new Fingerprint( new TermList( [ new Term( 'en', $propertyLabel ) ] ) ),
			'string'
		);

		$this->createPropertyRetrieverMock( $propertyId, $property );

		$this->termsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$this->termsCollisionDetector
			->expects( $this->never() )
			->method( 'detectLabelCollision' );

		$this->assertNull( $this->newValidator()->validate( $propertyId, $languageCode, $propertyLabel ) );
	}

	/**
	 * @dataProvider provideInvalidLabel
	 */
	public function testGivenInvalidDescription_returnsValidationError(
		string $label,
		string $errorCode,
		array $errorContext = []
	): void {
		$this->assertEquals(
			new ValidationError( $errorCode, $errorContext ),
			$this->newValidator()->validate( new NumericPropertyId( 'P123' ), 'en', $label )
		);
	}

	public static function provideInvalidLabel(): Generator {
		yield 'empty label' => [ '', PropertyLabelValidator::CODE_EMPTY ];

		$label = str_repeat( 'a', self::MAX_LENGTH + 1 );
		yield 'label too long' => [
			$label,
			PropertyLabelValidator::CODE_TOO_LONG,
			[
				PropertyLabelValidator::CONTEXT_LABEL => $label,
				PropertyLabelValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
			],
		];

		$label = "label with tab character \t not allowed";
		yield 'label has invalid character' => [
			$label,
			PropertyLabelValidator::CODE_INVALID,
			[ PropertyLabelValidator::CONTEXT_LABEL => $label ],
		];
	}

	public function testLabelEqualsDescription_returnsValidationError(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$languageCode = 'en';
		$description = 'some description';

		$property = new Property( $propertyId,
			new Fingerprint( null, new TermList( [ new Term( $languageCode, $description ) ] ) ),
			'string'
		);

		$this->createPropertyRetrieverMock( $propertyId, $property );

		$this->assertEquals(
			new ValidationError(
				PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyLabelValidator::CONTEXT_LANGUAGE => $languageCode ]
			),
			$this->newValidator()->validate( $propertyId, $languageCode, $description )
		);
	}

	public function testLabelCollision_returnsValidationError(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$languageCode = 'en';
		$label = 'some label';
		$matchingPropertyId = 'P456';

		$property = new Property( $propertyId, new Fingerprint(), 'string' );

		$this->createPropertyRetrieverMock( $propertyId, $property );

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
			$this->newValidator()->validate( $propertyId, $languageCode, $label )
		);
	}

	private function newValidator(): WikibaseRepoPropertyLabelValidator {
		return new WikibaseRepoPropertyLabelValidator(
			new TermValidatorFactory(
				self::MAX_LENGTH,
				WikibaseRepo::getTermsLanguages()->getLanguages(),
				WikibaseRepo::getEntityIdParser(),
				WikibaseRepo::getTermsCollisionDetectorFactory(),
				WikibaseRepo::getTermLookup(),
				$this->createStub( LanguageNameUtils::class )
			),
			$this->termsCollisionDetector,
			$this->propertyRetriever
		);
	}

	private function createPropertyRetrieverMock( PropertyId $propertyId, ?Property $property ): void {
		$this->propertyRetriever = $this->createMock( PropertyRetriever::class );
		$this->propertyRetriever
			->expects( $this->once() )
			->method( 'getProperty' )
			->with( $propertyId )
			->willReturn( $property );
	}

}
