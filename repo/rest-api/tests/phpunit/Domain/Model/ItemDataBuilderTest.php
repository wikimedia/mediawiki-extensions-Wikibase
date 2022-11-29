<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Model;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Domain\Model\ItemData;
use Wikibase\Repo\RestApi\Domain\Model\ItemDataBuilder;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Model\ItemDataBuilder
 * @covers \Wikibase\Repo\RestApi\Domain\Model\ItemData
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
		$labels = new TermList( [ new Term( 'en', 'potato' ) ] );
		$itemData = $this->newBuilderWithSomeId( [ ItemData::FIELD_LABELS ] )
			->setLabels( $labels )
			->build();
		$this->assertSame( $labels, $itemData->getLabels() );
	}

	public function testDescriptions(): void {
		$descriptions = new TermList( [ new Term( 'en', 'root vegetable' ) ] );
		$itemData = $this->newBuilderWithSomeId( [ ItemData::FIELD_DESCRIPTIONS ] )
			->setDescriptions( $descriptions )
			->build();
		$this->assertSame( $descriptions, $itemData->getDescriptions() );
	}

	public function testAliases(): void {
		$aliases = new AliasGroupList();
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
		$siteLinks = new SiteLinkList();
		$itemData = $this->newBuilderWithSomeId( [ ItemData::FIELD_SITELINKS ] )
			->setSiteLinks( $siteLinks )
			->build();
		$this->assertSame( $siteLinks, $itemData->getSiteLinks() );
	}

	public function testAll(): void {
		$type = Item::ENTITY_TYPE;
		$labels = new TermList( [ new Term( 'en', 'potato' ) ] );
		$descriptions = new TermList( [ new Term( 'en', 'root vegetable' ) ] );
		$aliases = new AliasGroupList();
		$statements = new StatementList();
		$siteLinks = new SiteLinkList();

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
			'item'
		];

		yield 'labels' => [
			ItemData::FIELD_LABELS,
			'setLabels',
			new TermList( [ new Term( 'en', 'potato' ) ] )
		];

		yield 'descriptions' => [
			ItemData::FIELD_DESCRIPTIONS,
			'setDescriptions',
			new TermList( [ new Term( 'en', 'root vegetable' ) ] )
		];

		yield 'aliases' => [
			ItemData::FIELD_ALIASES,
			'setAliases',
			new AliasGroupList()
		];

		yield 'statements' => [
			ItemData::FIELD_STATEMENTS,
			'setStatements',
			new StatementList()
		];

		yield 'sitelinks' => [
			ItemData::FIELD_SITELINKS,
			'setSiteLinks',
			new SiteLinkList()
		];
	}

	private function newBuilderWithSomeId( array $requestedFields ): ItemDataBuilder {
		return ( new ItemDataBuilder( new ItemId( 'Q666' ), $requestedFields ) );
	}
}
