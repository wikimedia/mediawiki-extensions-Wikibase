<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Revision\SlotRecord;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\Store\ItemTermStoreWriterAdapter;
use Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory;
use Wikibase\Lib\Store\ThrowingEntityTermStoreWriter;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemTermStoreWriterTest extends ServiceWiringTestCase {

	private function mockLocalEntityType( string $type ): void {
		$this->mockService(
			'WikibaseRepo.LocalEntitySource',
			new DatabaseEntitySource(
				'test',
				false,
				[ $type => [
					'namespaceId' => 21,
					'slot' => SlotRecord::MAIN,
				] ],
				'',
				'',
				'',
				''
			)
		);
	}

	public function testConstruction(): void {
		$this->mockLocalEntityType( 'item' );

		$this->mockService(
			'WikibaseRepo.TermStoreWriterFactory',
			$this->createMock( TermStoreWriterFactory::class )
		);

		$this->assertInstanceOf(
			ItemTermStoreWriterAdapter::class,
			$this->getService( 'WikibaseRepo.ItemTermStoreWriter' )
		);
	}

	public function testConstructionWithoutLocalItems(): void {
		$this->mockLocalEntityType( 'something' );

		$this->assertInstanceOf(
			ThrowingEntityTermStoreWriter::class,
			$this->getService( 'WikibaseRepo.ItemTermStoreWriter' )
		);
	}
}
