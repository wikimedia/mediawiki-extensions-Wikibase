<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemovePropertyDescription;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription\RemovePropertyDescription;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription\RemovePropertyDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription\RemovePropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription\RemovePropertyDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemovePropertyDescriptionTest extends TestCase {

	private RemovePropertyDescriptionValidator $requestValidator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->requestValidator = new TestValidatingRequestDeserializer();
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P1' );
		$languageCode = 'en';
		$descriptionToRemove = new Term( $languageCode, 'Description to remove' );
		$descriptionToKeep = new Term( 'fr', 'Description to keep' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'test comment';

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty(
			new DataModelProperty(
				$propertyId,
				new Fingerprint( null, new TermList( [ $descriptionToRemove, $descriptionToKeep ] ) ),
				'string'
			)
		);
		$this->propertyRetriever = $this->propertyUpdater = $propertyRepo;

		$this->newUseCase()->execute(
			new RemovePropertyDescriptionRequest( "$propertyId", $languageCode, $editTags, $isBot, $comment, null )
		);

		$this->assertEquals(
			new TermList( [ $descriptionToKeep ] ),
			$propertyRepo->getProperty( $propertyId )->getDescriptions()
		);
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				DescriptionEditSummary::newRemoveSummary( $comment, $descriptionToRemove )
			),
			$propertyRepo->getLatestRevisionEditMetadata( $propertyId )
		);
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-remove-description-test' );
		$this->requestValidator = $this->createStub( RemovePropertyDescriptionValidator::class );
		$this->requestValidator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( RemovePropertyDescriptionRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );

		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertPropertyExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new RemovePropertyDescriptionRequest( 'P999', 'en', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenDescriptionDoesNotExist_throws(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$language = 'en';
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[ 1 ] ];

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( new DataModelProperty( $propertyId, new Fingerprint(), 'string' ) );
		$this->propertyRetriever = $propertyRepo;

		try {
			$this->newUseCase()->execute(
				new RemovePropertyDescriptionRequest( (string)$propertyId, $language, $editTags, false, 'test', null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::DESCRIPTION_NOT_DEFINED, $e->getErrorCode() );
			$this->assertStringContainsString( (string)$propertyId, $e->getErrorMessage() );
			$this->assertStringContainsString( $language, $e->getErrorMessage() );
		}
	}

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$propertyId = new NumericPropertyId( 'P123' );

		$expectedError = new UseCaseError(
			UseCaseError::PERMISSION_DENIED,
			'You have no permission to edit this property.'
		);
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )
			->with( $propertyId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				new RemovePropertyDescriptionRequest( "$propertyId", 'en', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): RemovePropertyDescription {
		return new RemovePropertyDescription(
			$this->requestValidator,
			$this->assertPropertyExists,
			$this->assertUserIsAuthorized,
			$this->propertyRetriever,
			$this->propertyUpdater
		);
	}

}
