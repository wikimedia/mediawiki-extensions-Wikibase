<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemParts;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemPartsBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\SiteLinks;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\ItemPartsBuilder
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\ItemParts
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemPartsBuilderTest extends TestCase {

	public function testId(): void {
		$id = new ItemId( 'Q123' );
		$itemParts = ( new ItemPartsBuilder( $id, [] ) )
			->build();
		$this->assertSame( $id, $itemParts->getId() );
	}

	public function testLabels(): void {
		$labels = new Labels( new Label( 'en', 'potato' ) );
		$itemParts = $this->newBuilderWithSomeId( [ ItemParts::FIELD_LABELS ] )
			->setLabels( $labels )
			->build();
		$this->assertSame( $labels, $itemParts->getLabels() );
	}

	public function testDescriptions(): void {
		$descriptions = new Descriptions( new Description( 'en', 'root vegetable' ) );
		$itemParts = $this->newBuilderWithSomeId( [ ItemParts::FIELD_DESCRIPTIONS ] )
			->setDescriptions( $descriptions )
			->build();
		$this->assertSame( $descriptions, $itemParts->getDescriptions() );
	}

	public function testAliases(): void {
		$aliases = new Aliases();
		$itemParts = $this->newBuilderWithSomeId( [ ItemParts::FIELD_ALIASES ] )
			->setAliases( $aliases )
			->build();
		$this->assertSame( $aliases, $itemParts->getAliases() );
	}

	public function testStatements(): void {
		$statements = new StatementList();
		$itemParts = $this->newBuilderWithSomeId( [ ItemParts::FIELD_STATEMENTS ] )
			->setStatements( $statements )
			->build();
		$this->assertSame( $statements, $itemParts->getStatements() );
	}

	public function testSiteLinks(): void {
		$siteLinks = new SiteLinks();
		$itemParts = $this->newBuilderWithSomeId( [ ItemParts::FIELD_SITELINKS ] )
			->setSiteLinks( $siteLinks )
			->build();
		$this->assertSame( $siteLinks, $itemParts->getSiteLinks() );
	}

	public function testAll(): void {
		$labels = new Labels( new Label( 'en', 'potato' ) );
		$descriptions = new Descriptions( new Description( 'en', 'root vegetable' ) );
		$aliases = new Aliases();
		$statements = new StatementList();
		$siteLinks = new SiteLinks();

		$itemParts = $this->newBuilderWithSomeId( ItemParts::VALID_FIELDS )
			->setLabels( $labels )
			->setDescriptions( $descriptions )
			->setAliases( $aliases )
			->setStatements( $statements )
			->setSiteLinks( $siteLinks )
			->build();

		$this->assertSame( $labels, $itemParts->getLabels() );
		$this->assertSame( $descriptions, $itemParts->getDescriptions() );
		$this->assertSame( $aliases, $itemParts->getAliases() );
		$this->assertSame( $statements, $itemParts->getStatements() );
		$this->assertSame( $siteLinks, $itemParts->getSiteLinks() );
	}

	/**
	 * @dataProvider nonRequiredFields
	 *
	 * @param string $field
	 * @param string $setterFunction
	 * @param mixed $param
	 */
	public function testNonRequiredField( string $field, string $setterFunction, $param ): void {
		$builder = $this->newBuilderWithSomeId( [] );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'cannot set unrequested ' . ItemParts::class . " field '$field'" );
		$builder->$setterFunction( $param )->build();
	}

	public function nonRequiredFields(): Generator {
		yield 'labels' => [
			ItemParts::FIELD_LABELS,
			'setLabels',
			new Labels( new Label( 'en', 'potato' ) ),
		];

		yield 'descriptions' => [
			ItemParts::FIELD_DESCRIPTIONS,
			'setDescriptions',
			new Descriptions( new Description( 'en', 'root vegetable' ) ),
		];

		yield 'aliases' => [
			ItemParts::FIELD_ALIASES,
			'setAliases',
			new Aliases(),
		];

		yield 'statements' => [
			ItemParts::FIELD_STATEMENTS,
			'setStatements',
			new StatementList(),
		];

		yield 'sitelinks' => [
			ItemParts::FIELD_SITELINKS,
			'setSiteLinks',
			new SiteLinks(),
		];
	}

	private function newBuilderWithSomeId( array $requestedFields ): ItemPartsBuilder {
		return ( new ItemPartsBuilder( new ItemId( 'Q666' ), $requestedFields ) );
	}
}
