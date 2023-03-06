<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemDataBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLinks;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\ItemDataBuilder
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\ItemData
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemDataBuilderTest extends TestCase {

	public function testId(): void {
		$id = new ItemId( 'Q123' );
		$itemData = ( new ItemDataBuilder( $id, [] ) )
			->build();
		$this->assertSame( $id, $itemData->getId() );
	}

	public function testType(): void {
		$type = Item::ENTITY_TYPE;

		$itemData = $this->newBuilderWithSomeId( [ ItemData::FIELD_TYPE ] )
			->setType( $type )
			->build();
		$this->assertSame( $type, $itemData->getType() );
	}

	public function testLabels(): void {
		$labels = new Labels( new Label( 'en', 'potato' ) );
		$itemData = $this->newBuilderWithSomeId( [ ItemData::FIELD_LABELS ] )
			->setLabels( $labels )
			->build();
		$this->assertSame( $labels, $itemData->getLabels() );
	}

	public function testDescriptions(): void {
		$descriptions = new Descriptions( new Description( 'en', 'root vegetable' ) );
		$itemData = $this->newBuilderWithSomeId( [ ItemData::FIELD_DESCRIPTIONS ] )
			->setDescriptions( $descriptions )
			->build();
		$this->assertSame( $descriptions, $itemData->getDescriptions() );
	}

	public function testAliases(): void {
		$aliases = new Aliases();
		$itemData = $this->newBuilderWithSomeId( [ ItemData::FIELD_ALIASES ] )
			->setAliases( $aliases )
			->build();
		$this->assertSame( $aliases, $itemData->getAliases() );
	}

	public function testStatements(): void {
		$statements = new StatementList();
		$itemData = $this->newBuilderWithSomeId( [ ItemData::FIELD_STATEMENTS ] )
			->setStatements( $statements )
			->build();
		$this->assertSame( $statements, $itemData->getStatements() );
	}

	public function testSiteLinks(): void {
		$siteLinks = new SiteLinks();
		$itemData = $this->newBuilderWithSomeId( [ ItemData::FIELD_SITELINKS ] )
			->setSiteLinks( $siteLinks )
			->build();
		$this->assertSame( $siteLinks, $itemData->getSiteLinks() );
	}

	public function testAll(): void {
		$type = Item::ENTITY_TYPE;
		$labels = new Labels( new Label( 'en', 'potato' ) );
		$descriptions = new Descriptions( new Description( 'en', 'root vegetable' ) );
		$aliases = new Aliases();
		$statements = new StatementList();
		$siteLinks = new SiteLinks();

		$itemData = $this->newBuilderWithSomeId( ItemData::VALID_FIELDS )
			->setType( $type )
			->setLabels( $labels )
			->setDescriptions( $descriptions )
			->setAliases( $aliases )
			->setStatements( $statements )
			->setSiteLinks( $siteLinks )
			->build();

		$this->assertSame( $type, $itemData->getType() );
		$this->assertSame( $labels, $itemData->getLabels() );
		$this->assertSame( $descriptions, $itemData->getDescriptions() );
		$this->assertSame( $aliases, $itemData->getAliases() );
		$this->assertSame( $statements, $itemData->getStatements() );
		$this->assertSame( $siteLinks, $itemData->getSiteLinks() );
	}

	/**
	 * @dataProvider nonRequiredFields
	 *
	 * @param mixed $param
	 */
	public function testNonRequiredField( string $field, string $setterFunction, $param ): void {
		$builder = $this->newBuilderWithSomeId( [] );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( "cannot set unrequested ItemData field '$field'" );
		$builder->$setterFunction( $param )->build();
	}

	public function nonRequiredFields(): Generator {
		yield 'type' => [
			ItemData::FIELD_TYPE,
			'setType',
			'item',
		];

		yield 'labels' => [
			ItemData::FIELD_LABELS,
			'setLabels',
			new Labels( new Label( 'en', 'potato' ) ),
		];

		yield 'descriptions' => [
			ItemData::FIELD_DESCRIPTIONS,
			'setDescriptions',
			new Descriptions( new Description( 'en', 'root vegetable' ) ),
		];

		yield 'aliases' => [
			ItemData::FIELD_ALIASES,
			'setAliases',
			new Aliases(),
		];

		yield 'statements' => [
			ItemData::FIELD_STATEMENTS,
			'setStatements',
			new StatementList(),
		];

		yield 'sitelinks' => [
			ItemData::FIELD_SITELINKS,
			'setSiteLinks',
			new SiteLinks(),
		];
	}

	private function newBuilderWithSomeId( array $requestedFields ): ItemDataBuilder {
		return ( new ItemDataBuilder( new ItemId( 'Q666' ), $requestedFields ) );
	}
}
