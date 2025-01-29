<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as PropertyWriteModel;
use Wikibase\DataModel\Statement\StatementList as StatementListWriteModel;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditSummary;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Property;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Crud\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdaterPropertyUpdater;
use Wikibase\Repo\Tests\Domains\Crud\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdaterPropertyUpdater
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

	public function testCreate(): void {
		$expectedRevisionId = 234;
		$expectedRevisionTimestamp = '20221111070707';
		$editMetaData = new EditMetadata( [], true, $this->createStub( EditSummary::class ) );
		[ $propertyToCreate, $expectedResultingProperty ] = $this->newEquivalentWriteAndReadModelProperty();
		$propertyToCreate->setId( null );

		$this->entityUpdater = $this->createMock( EntityUpdater::class );
		$this->entityUpdater->expects( $this->once() )
			->method( 'create' )
			->with( $propertyToCreate, $editMetaData )
			->willReturnCallback( function () use ( $propertyToCreate, $expectedRevisionId, $expectedRevisionTimestamp ) {
				$propertyToCreate->setId( new NumericPropertyId( 'P123' ) );
				return new EntityRevision( $propertyToCreate, $expectedRevisionId, $expectedRevisionTimestamp );
			} );

		$propertyRevision = $this->newPropertyUpdater()->create( $propertyToCreate, $editMetaData );

		$this->assertEquals( $expectedResultingProperty, $propertyRevision->getProperty() );
		$this->assertSame( $expectedRevisionId, $propertyRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $propertyRevision->getLastModified() );
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

		$propertyRevision = $this->newPropertyUpdater()->update( $propertyToUpdate, $editMetaData );

		$this->assertEquals( $expectedResultingProperty, $propertyRevision->getProperty() );
		$this->assertSame( $expectedRevisionId, $propertyRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $propertyRevision->getLastModified() );
	}

	private function newEquivalentWriteAndReadModelProperty(): array {
		$propertyId = new NumericPropertyId( 'P123' );
		$writeModelStatement = NewStatement::someValueFor( $propertyId )
			->withGuid( 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();
		$readModelStatement = NewStatementReadModel::someValueFor( $propertyId )
			->withGuid( 'P123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();

		return [
			new PropertyWriteModel(
				$propertyId,
				new Fingerprint(
					new TermList( [ new Term( 'en', 'English Label' ) ] ),
					new TermList( [ new Term( 'en', 'English Description' ) ] )
				),
				'string',
				new StatementListWriteModel( $writeModelStatement )
			),
			new Property(
				$propertyId,
				'string',
				new Labels( new Label( 'en', 'English Label' ) ),
				new Descriptions( new Description( 'en', 'English Description' ) ),
				new Aliases(),
				new StatementList( $readModelStatement )
			),
		];
	}

	private function newPropertyUpdater(): EntityUpdaterPropertyUpdater {
		return new EntityUpdaterPropertyUpdater( $this->entityUpdater, $this->statementReadModelConverter );
	}

}
