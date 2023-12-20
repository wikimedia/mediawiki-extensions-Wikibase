<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemovePropertyLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel\RemovePropertyLabel;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel\RemovePropertyLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel\RemovePropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel\RemovePropertyLabel
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemovePropertyLabelTest extends TestCase {

	private RemovePropertyLabelValidator $requestValidator;
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
		$labelToRemove = new Term( $languageCode, 'Label to remove' );
		$labelToKeep = new Term( 'fr', 'Label to keep' );
		$propertyToUpdate = new Property(
			$propertyId,
			new Fingerprint( new TermList( [ $labelToRemove, $labelToKeep ] ) ),
			'string'
		);
		$tags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'test';

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( $propertyToUpdate );
		$this->propertyRetriever = $propertyRepo;
		$this->propertyUpdater = $propertyRepo;

		$request = new RemovePropertyLabelRequest( "$propertyId", $languageCode, $tags, $isBot, $comment, null );
		$this->newUseCase()->execute( $request );

		$this->assertEquals(
			$propertyRepo->getProperty( $propertyId )->getLabels(),
			new TermList( [ $labelToKeep ] )
		);
		$this->assertEquals(
			new EditMetadata( $tags, $isBot, LabelEditSummary::newRemoveSummary(
				$comment,
				$labelToRemove
			) ),
			$propertyRepo->getLatestRevisionEditMetadata( $propertyId )
		);
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-remove-label-test' );
		$this->requestValidator = $this->createStub( RemovePropertyLabelValidator::class );
		$this->requestValidator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( RemovePropertyLabelRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertPropertyExists->method( 'execute' )
			->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute(
				new RemovePropertyLabelRequest( 'P999', 'en', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenLabelDoesNotExist_throws(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$language = 'en';
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[ 1 ] ];

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyRetriever->method( 'getProperty' )->willReturn(
			new Property( $propertyId, new Fingerprint(), 'string' )
		);

		try {
			$request = new RemovePropertyLabelRequest( (string)$propertyId, $language, $editTags, false, 'test', null );
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::LABEL_NOT_DEFINED, $e->getErrorCode() );
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
			$this->newUseCase()->execute( new RemovePropertyLabelRequest( "$propertyId", 'en', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): RemovePropertyLabel {
		return new RemovePropertyLabel(
			$this->requestValidator,
			$this->assertPropertyExists,
			$this->assertUserIsAuthorized,
			$this->propertyRetriever,
			$this->propertyUpdater
		);
	}

}
