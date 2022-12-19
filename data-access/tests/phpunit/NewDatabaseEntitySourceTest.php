<?php
declare( strict_types=1 );

namespace Wikibase\DataAccess\Tests;

use MediaWiki\Revision\SlotRecord;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\DatabaseEntitySource;

/**
 * @covers \Wikibase\DataAccess\MultipleEntitySourceServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class NewDatabaseEntitySourceTest extends TestCase {
	public function testCreate(): void {
		$this->assertInstanceOf(
			DatabaseEntitySource::class,
			NewDatabaseEntitySource::create()->build()
		);
	}

	public function testWithName(): void {
		$name = 'meep';
		$source = NewDatabaseEntitySource::create()
			->withName( $name )
			->build();

		$this->assertSame(
			$name,
			$source->getSourceName()
		);
	}

	public function testWithDbName(): void {
		$db = 'some db';
		$source = NewDatabaseEntitySource::create()
			->withDbName( $db )
			->build();

		$this->assertSame(
			$db,
			$source->getDatabaseName()
		);
	}

	public function testWithEntityNamespaceIdsAndSlots(): void {
		$source = NewDatabaseEntitySource::create()
			->withEntityNamespaceIdsAndSlots( [
				'item' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ],
			] )
			->build();

		$this->assertEquals(
			[ 'item' ],
			$source->getEntityTypes()
		);
		$this->assertEquals(
			[ 'item' => 100 ],
			$source->getEntityNamespaceIds()
		);
		$this->assertEquals(
			[ 'item' => SlotRecord::MAIN ],
			$source->getEntitySlotNames()
		);
	}

	public function testWithConceptBaseUri(): void {
		$conceptUri = 'http://wikidata.org/entity/';
		$source = NewDatabaseEntitySource::create()
			->withConceptBaseUri( $conceptUri )
			->build();

		$this->assertSame(
			$conceptUri,
			$source->getConceptBaseUri()
		);
	}

	public function testWithRdfNodeNamespacePrefix(): void {
		$rdfNodePrefix = 'wd';
		$source = NewDatabaseEntitySource::create()
			->withRdfNodeNamespacePrefix( $rdfNodePrefix )
			->build();

		$this->assertSame(
			$rdfNodePrefix,
			$source->getRdfNodeNamespacePrefix()
		);
	}

	public function testWithRdfPredicateNamespacePrefix(): void {
		$rdfPredicatePrefix = 'wdp';
		$source = NewDatabaseEntitySource::create()
			->withRdfPredicateNamespacePrefix( $rdfPredicatePrefix )
			->build();

		$this->assertSame(
			$rdfPredicatePrefix,
			$source->getRdfPredicateNamespacePrefix()
		);
	}

	public function testWithInterwikiPrefix(): void {
		$interwikiPrefix = 'wd';
		$source = NewDatabaseEntitySource::create()
			->withInterwikiPrefix( $interwikiPrefix )
			->build();

		$this->assertSame(
			$interwikiPrefix,
			$source->getInterwikiPrefix()
		);
	}

	public function testType(): void {
		$sourceType = DatabaseEntitySource::TYPE;
		$source = NewDatabaseEntitySource::create()
			->withType( $sourceType )
			->build();

		$this->assertSame(
			$sourceType,
			$source->getType()
		);
	}
}
