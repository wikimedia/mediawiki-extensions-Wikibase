<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterItemUpdater;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterItemUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterItemUpdaterTest extends TestCase {

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
		[ $itemToUpdate, $expectedResultingItem ] = $this->newEquivalentWriteAndReadModelItem();

		$this->entityUpdater = $this->createMock( EntityUpdater::class );

		$this->entityUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $itemToUpdate, $editMetaData )
			->willReturn( new EntityRevision( $itemToUpdate, $expectedRevisionId, $expectedRevisionTimestamp ) );

		$itemRevision = $this->newItemUpdater()->update( $itemToUpdate, $editMetaData );

		$this->assertEquals( $expectedResultingItem, $itemRevision->getItem() );
		$this->assertSame( $expectedRevisionId, $itemRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $itemRevision->getLastModified() );
	}

	private function newEquivalentWriteAndReadModelItem(): array {
		$writeModelStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();
		$readModelStatement = NewStatementReadModel::someValueFor( 'P123' )
			->withGuid( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();

		return [
			NewItem::withId( 'Q123' )
				->andLabel( 'en', 'English Label' )
				->andDescription( 'en', 'English Description' )
				->andAliases( 'en', [ 'English alias', 'alias in English' ] )
				->andStatement( $writeModelStatement )
				->build(),
			new Item(
				new Labels( new Label( 'en', 'English Label' ) ),
				new Descriptions( new Description( 'en', 'English Description' ) ),
				new Aliases( new AliasesInLanguage( 'en', [ 'English alias', 'alias in English' ] ) ),
				new StatementList( $readModelStatement )
			),
		];
	}

	private function newItemUpdater(): EntityUpdaterItemUpdater {
		return new EntityUpdaterItemUpdater( $this->entityUpdater, $this->statementReadModelConverter );
	}

}
