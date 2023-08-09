<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\DataModel\Statement\StatementList as DataModelStatementList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterPropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterPropertyUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterPropertyUpdaterTest extends TestCase {

	use StatementReadModelHelper;

	private EntityUpdater $entityUpdater;
	private StatementReadModelConverter $statementReadModelConverter;

	protected function setUp(): void {
		parent::setUp();

		$this->statementReadModelConverter = $this->newStatementReadModelConverter();
		$this->entityUpdater = $this->createStub( EntityUpdater::class );
	}

	public function testUpdate(): void {
		$expectedRevisionId = 234;
		$expectedRevisionTimestamp = '20221111070707';
		$editMetaData = new EditMetadata( [], true, $this->createStub( EditSummary::class ) );
		[ $propertyToUpdate, $expectedResultingProperty ] = $this->newEquivalentWriteAndReadModelProperty();

		$this->entityUpdater = $this->createMock( EntityUpdater::class );
		$this->entityUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $propertyToUpdate, $editMetaData )
			->willReturn( new EntityRevision(
				$propertyToUpdate,
				$expectedRevisionId,
				$expectedRevisionTimestamp
			) );

		$propertyRevision = $this->newPropertyUpdater()->update(
			$propertyToUpdate,
			$editMetaData
		);

		$this->assertEquals( $expectedResultingProperty, $propertyRevision->getProperty() );
		$this->assertSame( $expectedRevisionId, $propertyRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $propertyRevision->getLastModified() );
	}

	private function newEquivalentWriteAndReadModelProperty(): array {
		$writeModelStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();
		$readModelStatement = NewStatementReadModel::someValueFor( 'P123' )
			->withGuid( 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();

		return [
			new DataModelProperty(
				null,
				null,
				'string',
				new DataModelStatementList( $writeModelStatement )
			),
			new Property(
				new StatementList( $readModelStatement )
			),
		];
	}

	private function newPropertyUpdater(): EntityUpdaterPropertyUpdater {
		return new EntityUpdaterPropertyUpdater( $this->entityUpdater, $this->statementReadModelConverter );
	}

}
