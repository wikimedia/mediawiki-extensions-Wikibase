<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemovePropertyLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel\RemovePropertyLabel;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel\RemovePropertyLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel\RemovePropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel\RemovePropertyLabel
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemovePropertyLabelTest extends TestCase {

	use EditMetadataHelper;

	private RemovePropertyLabelValidator $requestValidator;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->requestValidator = new TestValidatingRequestDeserializer();
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testHappyPath(): void {
		$propertyId = 'P1';
		$languageCode = 'en';
		$labelToRemove = new Term( $languageCode, 'Label to remove' );
		$labelToKeep = new Term( 'fr', 'Label to keep' );
		$propertyToUpdate = new DataModelProperty(
			new NumericPropertyId( $propertyId ),
			new Fingerprint( new TermList( [ $labelToRemove, $labelToKeep ] ) ),
			'string'
		);
		$updatedProperty = new DataModelProperty(
			new NumericPropertyId( $propertyId ),
			new Fingerprint( new TermList( [ $labelToKeep ] ) ),
			'string'
		);
		$tags = TestValidatingRequestDeserializer::ALLOWED_TAGS;

		$this->propertyRetriever = $this->createMock( PropertyRetriever::class );
		$this->propertyRetriever->expects( $this->once() )
			->method( 'getProperty' )
			->with( $propertyId )
			->willReturn( $propertyToUpdate );

		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$updatedProperty,
				$this->expectEquivalentMetadata( $tags, false, 'test', EditSummary::REMOVE_ACTION )
			);

		$request = new RemovePropertyLabelRequest( $propertyId, $languageCode, $tags, false, 'test', null );
		$this->newUseCase()->execute( $request );
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

	private function newUseCase(): RemovePropertyLabel {
		return new RemovePropertyLabel( $this->requestValidator, $this->propertyRetriever, $this->propertyUpdater );
	}

}
