<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabels;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabels
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyLabelsTest extends TestCase {

	use EditMetadataHelper;

	private PropertyLabelsRetriever $labelsRetriever;
	private LabelsSerializer $labelsSerializer;
	private JsonPatcher $patcher;
	private LabelsDeserializer $labelsDeserializer;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;
	private PatchPropertyLabelsValidator $validator;
	private AssertPropertyExists $assertPropertyExists;

	protected function setUp(): void {
		parent::setUp();

		$this->labelsRetriever = $this->createStub( PropertyLabelsRetriever::class );
		$this->labelsSerializer = new LabelsSerializer();
		$this->patcher = new JsonDiffJsonPatcher();
		$this->labelsDeserializer = new LabelsDeserializer();
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P31' );
		$property = new DataModelProperty( $propertyId, null, 'string' );

		$newLabelText = 'nature de l’élément';
		$newLabelLanguage = 'fr';

		$this->labelsRetriever = $this->createStub( PropertyLabelsRetriever::class );
		$this->labelsRetriever->method( 'getLabels' )->willReturn( new Labels() );

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyRetriever->method( 'getProperty' )->willReturn( $property );

		$revisionId = 657;
		$lastModified = '20221212040506';

		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'labels replaced by ' . __method__;

		$updatedProperty = new Property(
			new Labels( new Label( $newLabelLanguage, $newLabelText ) ),
			new Descriptions(),
			new Aliases(),
			new StatementList()
		);

		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback(
					fn( DataModelProperty $property ) => $property->getLabels()->getByLanguage( $newLabelLanguage )->getText()
														 === $newLabelText
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, LabelsEditSummary::PATCH_ACTION )
			)
			->willReturn( new PropertyRevision( $updatedProperty, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new PatchPropertyLabelsRequest(
				"$propertyId",
				[
					[
						'op' => 'add',
						'path' => "/$newLabelLanguage",
						'value' => $newLabelText,
					],
				],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $response->getLabels(), $updatedProperty->getLabels() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
	}

	public function testGivenInvalidRequest_throws(): void {
		$expectedException = new UseCaseException( 'invalid-label-patch-test' );
		$this->validator = $this->createStub( PatchPropertyLabelsValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $this->createStub( PatchPropertyLabelsRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$request = new PatchPropertyLabelsRequest( 'P999999', [], [], false, null, null );
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertPropertyExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): PatchPropertyLabels {
		return new PatchPropertyLabels(
			$this->labelsRetriever,
			$this->labelsSerializer,
			$this->patcher,
			$this->labelsDeserializer,
			$this->propertyRetriever,
			$this->propertyUpdater,
			$this->validator,
			$this->assertPropertyExists
		);
	}

}
