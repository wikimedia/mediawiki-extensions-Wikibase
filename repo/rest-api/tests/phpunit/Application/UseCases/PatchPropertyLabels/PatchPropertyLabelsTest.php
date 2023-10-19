<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabels;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabelsRequest;
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

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabels
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyLabelsTest extends TestCase {

	private PropertyLabelsRetriever $labelsRetriever;
	private LabelsSerializer $labelsSerializer;
	private JsonPatcher $patcher;
	private LabelsDeserializer $labelsDeserializer;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->labelsRetriever = $this->createStub( PropertyLabelsRetriever::class );
		$this->labelsSerializer = new LabelsSerializer();
		$this->patcher = new JsonDiffJsonPatcher();
		$this->labelsDeserializer = new LabelsDeserializer();
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P42' );
		$property = new DataModelProperty( $propertyId, null, 'string' );

		$newLabelText = 'pomme de terre';
		$newLabelLanguage = 'fr';

		$this->labelsRetriever = $this->createStub( PropertyLabelsRetriever::class );
		$this->labelsRetriever->method( 'getLabels' )->willReturn( new Labels() );

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyRetriever->method( 'getProperty' )->willReturn( $property );

		$revisionId = 657;
		$lastModified = '20221212040506';
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
				]
			)
		);

		$this->assertSame( $response->getLabels(), $updatedProperty->getLabels() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
	}

	private function newUseCase(): PatchPropertyLabels {
		return new PatchPropertyLabels(
			$this->labelsRetriever,
			$this->labelsSerializer,
			$this->patcher,
			$this->labelsDeserializer,
			$this->propertyRetriever,
			$this->propertyUpdater
		);
	}

}
