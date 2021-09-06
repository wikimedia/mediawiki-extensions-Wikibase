<?php
declare( strict_types=1 );

namespace Wikibase\DataAccess\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySource;

/**
 * @covers \Wikibase\DataAccess\MultipleEntitySourceServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class NewEntitySourceTest extends TestCase {

	public function testCreate(): void {
		$this->assertInstanceOf(
			EntitySource::class,
			NewEntitySource::create()->build()
		);
	}

	public function testWithName(): void {
		$name = 'meep';
		$source = NewEntitySource::create()
			->withName( $name )
			->build();

		$this->assertSame(
			$name,
			$source->getSourceName()
		);
	}

	public function testWithDbName(): void {
		$db = 'some db';
		$source = NewEntitySource::create()
			->withDbName( $db )
			->build();

		$this->assertSame(
			$db,
			$source->getDatabaseName()
		);
	}

	public function testWithEntityTypes(): void {
		$entityTypes = [ 'some type' ];
		$source = NewEntitySource::create()
			->withEntityTypes( $entityTypes )
			->build();

		$this->assertSame(
			$entityTypes,
			$source->getEntityTypes()
		);
	}

	public function testWithEntityNamespaceIdsAndSlots(): void {
		$source = NewEntitySource::create()
			->withEntityNamespaceIdsAndSlots( [
				'item' => [ 'namespaceId' => 100, 'slot' => 'main', ],
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
			[ 'item' => 'main' ],
			$source->getEntitySlotNames()
		);
	}

	public function testWithConceptBaseUri(): void {
		$conceptUri = 'http://wikidata.org/entity/';
		$source = NewEntitySource::create()
			->withConceptBaseUri( $conceptUri )
			->build();

		$this->assertSame(
			$conceptUri,
			$source->getConceptBaseUri()
		);
	}

	public function testWithRdfNodeNamespacePrefix(): void {
		$rdfNodePrefix = 'wd';
		$source = NewEntitySource::create()
			->withRdfNodeNamespacePrefix( $rdfNodePrefix )
			->build();

		$this->assertSame(
			$rdfNodePrefix,
			$source->getRdfNodeNamespacePrefix()
		);
	}

	public function testWithRdfPredicateNamespacePrefix(): void {
		$rdfPredicatePrefix = 'wdp';
		$source = NewEntitySource::create()
			->withRdfPredicateNamespacePrefix( $rdfPredicatePrefix )
			->build();

		$this->assertSame(
			$rdfPredicatePrefix,
			$source->getRdfPredicateNamespacePrefix()
		);
	}

	public function testWithInterwikiPrefix(): void {
		$interwikiPrefix = 'wd';
		$source = NewEntitySource::create()
			->withInterwikiPrefix( $interwikiPrefix )
			->build();

		$this->assertSame(
			$interwikiPrefix,
			$source->getInterwikiPrefix()
		);
	}

	public function testType(): void {
		$apiSourceType = ApiEntitySource::TYPE;
		$apiSource = NewEntitySource::create()
			->withType( $apiSourceType )
			->build();

		$dbSourceType = DatabaseEntitySource::TYPE;
		$dbSource = NewEntitySource::create()
			->withType( $dbSourceType )
			->build();

		$this->assertSame(
			$dbSourceType,
			$dbSource->getType()
		);
		$this->assertSame(
			$apiSourceType,
			$apiSource->getType()
		);
	}

}
