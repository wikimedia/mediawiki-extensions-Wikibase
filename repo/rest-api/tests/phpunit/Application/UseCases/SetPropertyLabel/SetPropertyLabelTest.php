<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetPropertyLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel\SetPropertyLabel;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel\SetPropertyLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel\SetPropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel\SetPropertyLabel
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class SetPropertyLabelTest extends TestCase {

	use EditMetadataHelper;

	private SetPropertyLabelValidator $validator;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
	}

	public function testAddLabel(): void {
		$propertyId = 'P1';
		$langCode = 'en';
		$newLabelText = 'New label';
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = "{$this->getName()} Comment";
		$revisionId = 432;
		$lastModified = '20231006040506';
		$property = new DataModelProperty( new NumericPropertyId( $propertyId ), null, 'string' );

		$this->propertyRetriever = $this->createMock( PropertyRetriever::class );
		$this->propertyRetriever->expects( $this->once() )->method( 'getProperty' )->with( $propertyId )->willReturn( $property );

		$updatedProperty = new Property(
			new Labels( new Label( $langCode, $newLabelText ) ),
			new Descriptions(),
			new Aliases(),
			new StatementList()
		);
		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->expects( $this->once() )->method( 'update' )
			->with(
				$this->callback(
					fn( DataModelProperty $property ) => $property->getLabels()->toTextArray() === [ $langCode => $newLabelText ]
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::ADD_ACTION )
			)
			->willReturn( new PropertyRevision( $updatedProperty, $lastModified, $revisionId ) );

		$request = new SetPropertyLabelRequest( $propertyId, $langCode, $newLabelText, $editTags, $isBot, $comment, null );
		$response = $this->newUseCase()->execute( $request );

		$this->assertEquals( new Label( $langCode, $newLabelText ), $response->getLabel() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
	}

	public function testReplaceLabel(): void {
		$propertyId = 'P1';
		$langCode = 'en';
		$updatedLabelText = 'Replaced label';
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = "{$this->getName()} Comment";
		$revisionId = 432;
		$lastModified = '20231006040506';
		$property = new DataModelProperty(
			new NumericPropertyId( $propertyId ),
			new Fingerprint( new TermList( [ new Term( 'en', 'Label to replace' ) ] ) ),
			'string'
		);

		$this->propertyRetriever = $this->createMock( PropertyRetriever::class );
		$this->propertyRetriever->expects( $this->once() )->method( 'getProperty' )->with( $propertyId )->willReturn( $property );

		$updatedProperty = new Property(
			new Labels( new Label( $langCode, $updatedLabelText ) ),
			new Descriptions(),
			new Aliases(),
			new StatementList()
		);
		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->expects( $this->once() )->method( 'update' )
			->with(
				$this->callback(
					fn( DataModelProperty $property ) => $property->getLabels()->toTextArray() === [ $langCode => $updatedLabelText ]
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::REPLACE_ACTION )
			)
			->willReturn( new PropertyRevision( $updatedProperty, $lastModified, $revisionId ) );

		$request = new SetPropertyLabelRequest( $propertyId, $langCode, $updatedLabelText, $editTags, $isBot, $comment, null );
		$response = $this->newUseCase()->execute( $request );

		$this->assertEquals( new Label( $langCode, $updatedLabelText ), $response->getLabel() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
	}

	public function testGivenInvalidRequest_throwsUseCaseException(): void {
		$expectedException = new UseCaseException( 'invalid-label-test' );
		$this->validator = $this->createStub( SetPropertyLabelValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute(
				new SetPropertyLabelRequest( 'P123', 'en', 'label', [], false, null, null )
			);
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
				new SetPropertyLabelRequest( 'P999', 'en', 'test label', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$propertyId = new NumericPropertyId( 'P123' );

		$expectedError = new UseCaseError(
			UseCaseError::PERMISSION_DENIED,
			'You have no permission to edit this item.'
		);
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )
			->with( $propertyId, null )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				new SetPropertyLabelRequest( "$propertyId", 'en', 'test label', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): SetPropertyLabel {
		return new SetPropertyLabel(
			$this->validator,
			$this->propertyRetriever,
			$this->propertyUpdater,
			$this->assertPropertyExists,
			$this->assertUserIsAuthorized
		);
	}

}
