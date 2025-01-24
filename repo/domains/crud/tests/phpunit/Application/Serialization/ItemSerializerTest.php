<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Serialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ItemSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Item;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelinks;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Serialization\ItemSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemSerializerTest extends TestCase {

	private StatementListSerializer $statementsSerializer;
	private SitelinksSerializer $sitelinksSerializer;

	protected function setUp(): void {
		parent::setUp();

		$this->statementsSerializer = $this->createStub( StatementListSerializer::class );
		$this->sitelinksSerializer = $this->createStub( SitelinksSerializer::class );
	}

	public function testSerialize(): void {
		$itemId = new ItemId( 'Q123' );
		$item = new Item(
			$itemId,
			new Labels( new Label( 'en', 'en label' ) ),
			new Descriptions( new Description( 'en', 'en description' ) ),
			new Aliases( new AliasesInLanguage( 'en', [ 'en alias' ] ) ),
			new Sitelinks(),
			new StatementList()
		);

		$statementSerialization = new ArrayObject();
		$sitelinksSerialization = new ArrayObject();

		$expectedSerialization = [
			'id' => "$itemId",
			'type' => 'item',
			'labels' => new ArrayObject( [ 'en' => 'en label' ] ),
			'descriptions' => new ArrayObject( [ 'en' => 'en description' ] ),
			'aliases' => new ArrayObject( [ 'en' => [ 'en alias' ] ] ),
			'sitelinks' => $sitelinksSerialization,
			'statements' => $statementSerialization,
		];

		$this->statementsSerializer = $this->createStub( StatementListSerializer::class );
		$this->statementsSerializer->method( 'serialize' )->willReturn( $statementSerialization );

		$this->sitelinksSerializer = $this->createStub( SitelinksSerializer::class );
		$this->sitelinksSerializer->method( 'serialize' )->willReturn( $sitelinksSerialization );

		$this->assertEquals( $expectedSerialization, $this->newItemSerializer()->serialize( $item ) );
	}

	private function newItemSerializer(): ItemSerializer {
		return new ItemSerializer(
			new LabelsSerializer(),
			new DescriptionsSerializer(),
			new AliasesSerializer(),
			$this->statementsSerializer,
			$this->sitelinksSerializer
		);
	}

}
