<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\ItemAliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelsAndDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemStatementsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\ItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemValidatorTest extends TestCase {

	public const MAX_LENGTH = 50;
	private ItemLabelsAndDescriptionsValidator $itemLabelsAndDescriptionsValidator;
	private ItemAliasesValidator $itemAliasesValidator;
	private ItemStatementsValidator $itemStatementsValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->itemLabelsAndDescriptionsValidator = $this->createStub( ItemLabelsAndDescriptionsValidator::class );
		$this->itemAliasesValidator = $this->createStub( ItemAliasesValidator::class );
		$this->itemStatementsValidator = $this->createStub( ItemStatementsValidator::class );
	}

	public function testValid(): void {
		$labels = [ 'en' => 'english-label' ];
		$descriptions = [ 'en' => 'english-description' ];
		$aliases = [ 'en' => [ 'en-alias-1', 'en-alias-2' ] ];
		$statementsSerialization = [
			'P567' => [ [ 'property' => [ 'id' => 'P567' ], 'value' => [ 'type' => 'somevalue' ] ] ],
			'P789' => [ [ 'property' => [ 'id' => 'P789' ], 'value' => [ 'type' => 'somevalue' ] ] ],
		];

		$itemSerialization = [
			'labels' => $labels,
			'descriptions' => $descriptions,
			'aliases' => $aliases,
			'sitelinks' => [ 'somewiki' => [ 'title' => 'test-title' ] ],
			'statements' => $statementsSerialization,
		];

		$deserializedLabels = new TermList( [ new Term( 'en', 'english-label' ) ] );
		$deserializedDescriptions = new TermList( [ new Term( 'en', 'english-description' ) ] );
		$deserializedAliases = new AliasGroupList( [ new AliasGroup( 'en', [ 'en-alias-1', 'en-alias-2' ] ) ] );
		$deserializedStatements = new StatementList(
			NewStatement::someValueFor( 'P567' )->build(),
			NewStatement::someValueFor( 'P789' )->build()
		);

		$this->itemLabelsAndDescriptionsValidator = $this->createMock( ItemLabelsAndDescriptionsValidator::class );
		$this->itemLabelsAndDescriptionsValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $labels, $descriptions )
			->willReturn( null );
		$this->itemLabelsAndDescriptionsValidator->expects( $this->once() )
			->method( 'getValidatedLabels' )
			->willReturn( $deserializedLabels );
		$this->itemLabelsAndDescriptionsValidator->expects( $this->once() )
			->method( 'getValidatedDescriptions' )
			->willReturn( $deserializedDescriptions );

		$this->itemAliasesValidator = $this->createMock( ItemAliasesValidator::class );
		$this->itemAliasesValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $aliases )
			->willReturn( null );
		$this->itemAliasesValidator->expects( $this->once() )
			->method( 'getValidatedAliases' )
			->willReturn( $deserializedAliases );

		$this->itemStatementsValidator = $this->createMock( ItemStatementsValidator::class );
		$this->itemStatementsValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $statementsSerialization )
			->willReturn( null );
		$this->itemStatementsValidator->expects( $this->once() )
			->method( 'getValidatedStatements' )
			->willReturn( $deserializedStatements );

		$validator = $this->newValidator();
		$this->assertNull(
			$validator->validate( $itemSerialization )
		);

		$this->assertEquals(
			new Item(
				null,
				new Fingerprint( $deserializedLabels, $deserializedDescriptions, $deserializedAliases ),
				new SiteLinkList( [ new SiteLink( 'somewiki', 'test-title' ) ] ),
				$deserializedStatements
			),
			$validator->getValidatedItem()
		);
	}

	public function testGivenItemWithoutLabelsAndDescriptions_returnsValidationError(): void {
		$error = $this->newValidator()->validate( [] );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( ItemValidator::CODE_MISSING_LABELS_AND_DESCRIPTIONS, $error->getCode() );
	}

	public function testLabelsOrDescriptionsValidationError(): void {
		$invalidSerialization = [
			'labels' => [ 'invalid' => 'labels' ],
			'descriptions' => [ 'invalid' => 'descriptions' ],
		];

		$expectedError = $this->createStub( ValidationError::class );
		$this->itemLabelsAndDescriptionsValidator =
			$this->createMock( ItemLabelsAndDescriptionsValidator::class );
		$this->itemLabelsAndDescriptionsValidator
			->method( 'validate' )
			->with( $invalidSerialization[ 'labels' ], $invalidSerialization[ 'descriptions' ], )
			->willReturn( $expectedError );

		$this->assertEquals( $expectedError, $this->newValidator()->validate( $invalidSerialization ) );
	}

	public function testAliasesValidationError(): void {
		$invalidSerialization = [
			'labels' => [ 'en' => 'label' ],
			'aliases' => [ 'invalid' => 'aliases' ],
		];

		$expectedError = $this->createStub( ValidationError::class );
		$this->itemAliasesValidator = $this->createMock( ItemAliasesValidator::class );
		$this->itemAliasesValidator->method( 'validate' )
			->with( $invalidSerialization[ 'aliases' ] )
			->willReturn( $expectedError );

		$this->assertEquals( $expectedError, $this->newValidator()->validate( $invalidSerialization ) );
	}

	public function testStatementsValidationError(): void {
		$invalidSerialization = [
			'labels' => [ 'en' => 'label' ],
			'statements' => [ 'invalid' => 'statement' ],
		];

		$expectedError = $this->createStub( ValidationError::class );
		$this->itemStatementsValidator = $this->createMock( ItemStatementsValidator::class );
		$this->itemStatementsValidator->method( 'validate' )
			->with( $invalidSerialization[ 'statements' ] )
			->willReturn( $expectedError );

		$this->assertEquals( $expectedError, $this->newValidator()->validate( $invalidSerialization ) );
	}

	public function testGivenInvalidField_validateReturnsValidationError(): void {
		$invalidSerialization = [ 'labels' => 'not an array' ];
		$this->assertEquals(
			new ValidationError(
				ItemValidator::CODE_INVALID_FIELD,
				[
					ItemValidator::CONTEXT_FIELD_NAME => 'labels',
					ItemValidator::CONTEXT_FIELD_VALUE => 'not an array',
				]
			),
			$this->newValidator()->validate( $invalidSerialization )
		);
	}

	public function testGivenUnexpectedField_validateReturnsValidationError(): void {
		$unexpectedSerialization = [
			'labels' => [ 'en' => 'English Label' ],
			'descriptions' => [ 'en' => 'English Description' ],
			'foo' => 'var',
		];
		$this->assertEquals(
			new ValidationError(
				ItemValidator::CODE_UNEXPECTED_FIELD,
				[ ItemValidator::CONTEXT_FIELD_NAME => 'foo' ]
			),
			$this->newValidator()->validate( $unexpectedSerialization )
		);
	}

	public function testGetValidatedItem_calledBeforeValidate(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedItem();
	}

	private function newValidator(): ItemValidator {
		return new ItemValidator(
			$this->itemLabelsAndDescriptionsValidator,
			$this->itemAliasesValidator,
			$this->itemStatementsValidator,
			new SitelinksDeserializer(
				new SitelinkDeserializer(
					'/\?/',
					[ 'Q123' ],
					new SameTitleSitelinkTargetResolver(),
					new DummyItemRevisionMetaDataRetriever()
				)
			),
		);
	}
}
