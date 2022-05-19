<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Model;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
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
		$itemData = ( new ItemDataBuilder() )
			->setId( $id )
			->build();
		$this->assertSame( $id, $itemData->getId() );
	}

	public function testType(): void {
		$type = Item::ENTITY_TYPE;
		$itemData = $this->newBuilderWithSomeId()
			->setType( $type )
			->build();
		$this->assertSame( $type, $itemData->getType() );
	}

	public function testLabels(): void {
		$labels = new TermList( [ new Term( 'en', 'potato' ) ] );
		$itemData = $this->newBuilderWithSomeId()
			->setLabels( $labels )
			->build();
		$this->assertSame( $labels, $itemData->getLabels() );
	}

	public function testDescriptions(): void {
		$descriptions = new TermList( [ new Term( 'en', 'root vegetable' ) ] );
		$itemData = $this->newBuilderWithSomeId()
			->setDescriptions( $descriptions )
			->build();
		$this->assertSame( $descriptions, $itemData->getDescriptions() );
	}

	public function testAliases(): void {
		$aliases = new AliasGroupList();
		$itemData = $this->newBuilderWithSomeId()
			->setAliases( $aliases )
			->build();
		$this->assertSame( $aliases, $itemData->getAliases() );
	}

	public function testStatements(): void {
		$statements = new StatementList();
		$itemData = $this->newBuilderWithSomeId()
			->setStatements( $statements )
			->build();
		$this->assertSame( $statements, $itemData->getStatements() );
	}

	public function testSiteLinks(): void {
		$siteLinks = new SiteLinkList();
		$itemData = $this->newBuilderWithSomeId()
			->setSiteLinks( $siteLinks )
			->build();
		$this->assertSame( $siteLinks, $itemData->getSiteLinks() );
	}

	private function newBuilderWithSomeId(): ItemDataBuilder {
		return ( new ItemDataBuilder() )->setId( new ItemId( 'Q666' ) );
	}

}
