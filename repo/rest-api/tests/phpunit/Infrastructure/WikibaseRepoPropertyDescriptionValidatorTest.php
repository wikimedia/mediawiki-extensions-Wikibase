<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoPropertyDescriptionValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoPropertyDescriptionValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseRepoPropertyDescriptionValidatorTest extends TestCase {

	private const MAX_LENGTH = 50;

	private PropertyRetriever $propertyRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
	}

	public function testGivenValidDescription_returnsNull(): void {
		$propertyId = new NumericPropertyId( 'P123' );

		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		$property->setLabel( 'en', 'Property Label' );
		$property->setDescription( 'en', 'Property Description' );

		$this->createPropertyRetrieverMock( $propertyId, $property );

		$this->assertNull(
			$this->newValidator()->validate( $propertyId, 'en', 'valid description' )
		);
	}

	public function testGivenValidDescriptionAndPropertyWithoutLabel_returnsNull(): void {
		$propertyId = new NumericPropertyId( 'P123' );

		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		$property->setDescription( 'en', 'Property Description' );

		$this->createPropertyRetrieverMock( $propertyId, $property );

		$this->assertNull(
			$this->newValidator()->validate( $propertyId, 'en', 'valid description' )
		);
	}

	public function testGivenValidDescriptionForNonExistentProperty_returnsNull(): void {
		$propertyId = new NumericPropertyId( 'P123' );

		$this->createPropertyRetrieverMock( $propertyId, null );

		$this->assertNull(
			$this->newValidator()->validate( $propertyId, 'en', 'valid description' )
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
			$this->newValidator()->validate( new NumericPropertyId( 'P123' ), 'en', $description )
		);
	}

	public static function provideInvalidDescription(): Generator {
		yield 'description too short' => [ '', PropertyDescriptionValidator::CODE_EMPTY ];

		$description = str_repeat( 'a', self::MAX_LENGTH + 1 );
		yield 'description too long' => [
			$description,
			PropertyDescriptionValidator::CODE_TOO_LONG,
			[
				PropertyDescriptionValidator::CONTEXT_DESCRIPTION => $description,
				PropertyDescriptionValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
			],
		];

		$description = "description with tab character \t not allowed";
		yield 'description has invalid character' => [
			$description,
			PropertyDescriptionValidator::CODE_INVALID,
			[ PropertyDescriptionValidator::CONTEXT_DESCRIPTION => $description ],
		];
	}

	public function testGivenDescriptionSameAsLabel_returnsValidationError(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$propertyLabel = 'Property Label';
		$language = 'en';

		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		$property->setLabel( $language, $propertyLabel );
		$property->setDescription( $language, 'Property Description' );

		$this->createPropertyRetrieverMock( $propertyId, $property );

		$this->assertEquals(
			new ValidationError(
				PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyDescriptionValidator::CONTEXT_LANGUAGE => $language ]
			),
			$this->newValidator()->validate( $propertyId, $language, $propertyLabel )
		);
	}

	public function testUnchangedDescription_willNotPerformValidation(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$language = 'en';
		$description = 'Property Description';

		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		$property->setLabel( $language, 'New Label' );
		$property->setDescription( $language, $description );

		$this->createPropertyRetrieverMock( $propertyId, $property );

		$this->assertNull( $this->newValidator()->validate( $propertyId, $language, $description ) );
	}

	private function newValidator(): WikibaseRepoPropertyDescriptionValidator {
		return new WikibaseRepoPropertyDescriptionValidator(
			$this->newTermValidatorFactory(),
			$this->propertyRetriever
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

	private function createPropertyRetrieverMock( PropertyId $propertyId, ?Property $property ): void {
		$this->propertyRetriever = $this->createMock( PropertyRetriever::class );
		$this->propertyRetriever
			->expects( $this->once() )
			->method( 'getProperty' )
			->with( $propertyId )
			->willReturn( $property );
	}

}
