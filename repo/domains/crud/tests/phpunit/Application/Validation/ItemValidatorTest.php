<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Validation;

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
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemDescriptionsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemLabelsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PartiallyValidatedDescriptions;
use Wikibase\Repo\Domains\Crud\Application\Validation\PartiallyValidatedLabels;
use Wikibase\Repo\Domains\Crud\Application\Validation\SitelinksValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Validation\ItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemValidatorTest extends TestCase {

	public const MAX_LENGTH = 50;
	private LabelsSyntaxValidator $labelsSyntaxValidator;
	private ItemLabelsContentsValidator $labelsContentsValidator;
	private DescriptionsSyntaxValidator $descriptionsSyntaxValidator;
	private ItemDescriptionsContentsValidator $descriptionsContentsValidator;
	private AliasesValidator $itemAliasesValidator;
	private StatementsValidator $itemStatementsValidator;
	private SitelinksValidator $sitelinksValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->labelsSyntaxValidator = $this->createStub( LabelsSyntaxValidator::class );
		$this->labelsContentsValidator = $this->createStub( ItemLabelsContentsValidator::class );
		$this->descriptionsSyntaxValidator = $this->createStub( DescriptionsSyntaxValidator::class );
		$this->descriptionsContentsValidator = $this->createStub( ItemDescriptionsContentsValidator::class );
		$this->itemAliasesValidator = $this->createStub( AliasesValidator::class );
		$this->itemStatementsValidator = $this->createStub( StatementsValidator::class );
		$this->sitelinksValidator = $this->createStub( SitelinksValidator::class );
	}

	public function testValid(): void {
		$labels = [ 'en' => 'english-label' ];
		$descriptions = [ 'en' => 'english-description' ];
		$aliases = [ 'en' => [ 'en-alias-1', 'en-alias-2' ] ];
		$statementsSerialization = [
			'P567' => [ [ 'property' => [ 'id' => 'P567' ], 'value' => [ 'type' => 'somevalue' ] ] ],
			'P789' => [ [ 'property' => [ 'id' => 'P789' ], 'value' => [ 'type' => 'somevalue' ] ] ],
		];
		$siteId = 'somewiki';
		$sitelinks = [ $siteId => [ 'title' => 'test-title' ] ];

		$itemSerialization = [
			'labels' => $labels,
			'descriptions' => $descriptions,
			'aliases' => $aliases,
			'sitelinks' => $sitelinks,
			'statements' => $statementsSerialization,
		];

		$deserializedLabels = new TermList( [ new Term( 'en', 'english-label' ) ] );
		$deserializedDescriptions = new TermList( [ new Term( 'en', 'english-description' ) ] );
		$deserializedAliases = new AliasGroupList( [ new AliasGroup( 'en', [ 'en-alias-1', 'en-alias-2' ] ) ] );
		$deserializedStatements = new StatementList(
			NewStatement::someValueFor( 'P567' )->build(),
			NewStatement::someValueFor( 'P789' )->build()
		);
		$deserializedSitelinks = new SiteLinkList( [ new SiteLink( $siteId, $sitelinks[$siteId]['title'] ) ] );

		$this->labelsSyntaxValidator = $this->createMock( LabelsSyntaxValidator::class );
		$this->labelsSyntaxValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $labels );
		$this->labelsSyntaxValidator->expects( $this->atLeastOnce() )
			->method( 'getPartiallyValidatedLabels' )
			->willReturn( new PartiallyValidatedLabels( $deserializedLabels ) );
		$this->labelsContentsValidator = $this->createMock( ItemLabelsContentsValidator::class );
		$this->labelsContentsValidator->expects( $this->once() )
			->method( 'validate' )
			->with( new PartiallyValidatedLabels( $deserializedLabels ), new PartiallyValidatedDescriptions( $deserializedDescriptions ) );
		$this->labelsContentsValidator->expects( $this->once() )
			->method( 'getValidatedLabels' )
			->willReturn( $deserializedLabels );

		$this->descriptionsSyntaxValidator = $this->createMock( DescriptionsSyntaxValidator::class );
		$this->descriptionsSyntaxValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $descriptions );
		$this->descriptionsSyntaxValidator->expects( $this->atLeastOnce() )
			->method( 'getPartiallyValidatedDescriptions' )
			->willReturn( new PartiallyValidatedDescriptions( $deserializedDescriptions ) );
		$this->descriptionsContentsValidator = $this->createMock( ItemDescriptionsContentsValidator::class );
		$this->descriptionsContentsValidator->expects( $this->once() )
			->method( 'validate' )
			->with( new PartiallyValidatedDescriptions( $deserializedDescriptions ), new PartiallyValidatedLabels( $deserializedLabels ) );
		$this->descriptionsContentsValidator->expects( $this->once() )
			->method( 'getValidatedDescriptions' )
			->willReturn( $deserializedDescriptions );

		$this->itemAliasesValidator = $this->createMock( AliasesValidator::class );
		$this->itemAliasesValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $aliases )
			->willReturn( null );
		$this->itemAliasesValidator->expects( $this->once() )
			->method( 'getValidatedAliases' )
			->willReturn( $deserializedAliases );

		$this->itemStatementsValidator = $this->createMock( StatementsValidator::class );
		$this->itemStatementsValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $statementsSerialization )
			->willReturn( null );
		$this->itemStatementsValidator->expects( $this->once() )
			->method( 'getValidatedStatements' )
			->willReturn( $deserializedStatements );

		$this->sitelinksValidator = $this->createMock( SitelinksValidator::class );
		$this->sitelinksValidator->expects( $this->once() )
			->method( 'validate' )
			->with( null, $sitelinks );
		$this->sitelinksValidator->method( 'getValidatedSitelinks' )->willReturn( $deserializedSitelinks );

		$validator = $this->newValidator();
		$this->assertNull(
			$validator->validate( $itemSerialization )
		);

		$this->assertEquals(
			new Item(
				null,
				new Fingerprint( $deserializedLabels, $deserializedDescriptions, $deserializedAliases ),
				$deserializedSitelinks,
				$deserializedStatements
			),
			$validator->getValidatedItem()
		);
	}

	public function testGivenEmptyItem(): void {
		$this->labelsSyntaxValidator = $this->createMock( LabelsSyntaxValidator::class );
		$this->labelsSyntaxValidator->expects( $this->atLeastOnce() )
			->method( 'getPartiallyValidatedLabels' )
			->willReturn( new PartiallyValidatedLabels( [] ) );

		$this->labelsContentsValidator = $this->createMock( ItemLabelsContentsValidator::class );
		$this->labelsContentsValidator->expects( $this->once() )
			->method( 'getValidatedLabels' )
			->willReturn( new TermList() );

		$this->descriptionsSyntaxValidator = $this->createMock( DescriptionsSyntaxValidator::class );
		$this->descriptionsSyntaxValidator->expects( $this->atLeastOnce() )
			->method( 'getPartiallyValidatedDescriptions' )
			->willReturn( new PartiallyValidatedDescriptions( [] ) );

		$this->descriptionsContentsValidator = $this->createMock( ItemDescriptionsContentsValidator::class );
		$this->descriptionsContentsValidator->expects( $this->once() )
			->method( 'getValidatedDescriptions' )
			->willReturn( new TermList() );

		$this->itemAliasesValidator = $this->createMock( AliasesValidator::class );
		$this->itemAliasesValidator->expects( $this->once() )
			->method( 'getValidatedAliases' )
			->willReturn( new AliasGroupList() );

		$this->itemStatementsValidator = $this->createMock( StatementsValidator::class );
		$this->itemStatementsValidator->expects( $this->once() )
			->method( 'getValidatedStatements' )
			->willReturn( new StatementList() );

		$this->sitelinksValidator = $this->createMock( SitelinksValidator::class );
		$this->sitelinksValidator->method( 'getValidatedSitelinks' )->willReturn( new SiteLinkList() );

		$validator = $this->newValidator();

		$this->assertNull( $validator->validate( [] ) );
		$this->assertEquals( new Item(), $validator->getValidatedItem() );
	}

	public function testAliasesValidationError(): void {
		$invalidSerialization = [
			'labels' => [ 'en' => 'label' ],
			'aliases' => [ 'invalid' => 'aliases' ],
		];

		$expectedError = $this->createStub( ValidationError::class );
		$this->itemAliasesValidator = $this->createMock( AliasesValidator::class );
		$this->itemAliasesValidator->method( 'validate' )
			->with( $invalidSerialization[ 'aliases' ] )
			->willReturn( $expectedError );

		$this->assertEquals( $expectedError, $this->newValidator()->validate( $invalidSerialization ) );
	}

	public function testSitelinksValidationError(): void {
		$invalidSerialization = [
			'labels' => [ 'en' => 'label' ],
			'sitelinks' => [ 'invalid' => 'sitelinks' ],
		];

		$expectedError = $this->createStub( ValidationError::class );
		$this->sitelinksValidator = $this->createMock( SitelinksValidator::class );
		$this->sitelinksValidator->method( 'validate' )
			->with( null, $invalidSerialization[ 'sitelinks' ] )
			->willReturn( $expectedError );

		$this->assertEquals( $expectedError, $this->newValidator()->validate( $invalidSerialization ) );
	}

	public function testStatementsValidationError(): void {
		$invalidSerialization = [
			'labels' => [ 'en' => 'label' ],
			'statements' => [ 'invalid' => 'statement' ],
		];

		$expectedError = $this->createStub( ValidationError::class );
		$this->itemStatementsValidator = $this->createMock( StatementsValidator::class );
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
					ItemValidator::CONTEXT_FIELD => 'labels',
					ItemValidator::CONTEXT_VALUE => 'not an array',
				]
			),
			$this->newValidator()->validate( $invalidSerialization )
		);
	}

	public function testLabelSyntaxError(): void {
		$expectedValidationError = $this->createStub( ValidationError::class );
		$this->labelsSyntaxValidator = $this->createStub( LabelsSyntaxValidator::class );
		$this->labelsSyntaxValidator->method( 'validate' )->willReturn( $expectedValidationError );

		$this->assertSame( $expectedValidationError, $this->newValidator()->validate( [ 'labels' => [ 'en' => 'foo' ] ] ) );
	}

	public function testDescriptionsSyntaxError(): void {
		$expectedValidationError = $this->createStub( ValidationError::class );
		$this->descriptionsSyntaxValidator = $this->createStub( DescriptionsSyntaxValidator::class );
		$this->descriptionsSyntaxValidator->method( 'validate' )->willReturn( $expectedValidationError );

		$this->assertSame( $expectedValidationError, $this->newValidator()->validate( [ 'descriptions' => [ 'en' => 'foo' ] ] ) );
	}

	public function testLabelsContentError(): void {
		$expectedValidationError = $this->createStub( ValidationError::class );
		$this->labelsContentsValidator = $this->createStub( ItemLabelsContentsValidator::class );
		$this->labelsContentsValidator->method( 'validate' )->willReturn( $expectedValidationError );

		$this->assertSame( $expectedValidationError, $this->newValidator()->validate( [ 'labels' => [ 'en' => 'foo' ] ] ) );
	}

	public function testDescriptionsContentError(): void {
		$expectedValidationError = $this->createStub( ValidationError::class );
		$this->descriptionsContentsValidator = $this->createStub( ItemDescriptionsContentsValidator::class );
		$this->descriptionsContentsValidator->method( 'validate' )->willReturn( $expectedValidationError );

		$this->assertSame( $expectedValidationError, $this->newValidator()->validate( [ 'descriptions' => [ 'en' => 'foo' ] ] ) );
	}

	public function testGetValidatedItemCalledBeforeValidate_throws(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedItem();
	}

	private function newValidator(): ItemValidator {
		return new ItemValidator(
			$this->labelsSyntaxValidator,
			$this->labelsContentsValidator,
			$this->descriptionsSyntaxValidator,
			$this->descriptionsContentsValidator,
			$this->itemAliasesValidator,
			$this->itemStatementsValidator,
			$this->sitelinksValidator
		);
	}
}
