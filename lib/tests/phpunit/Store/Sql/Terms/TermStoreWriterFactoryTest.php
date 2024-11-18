<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use JobQueueGroup;
use LogicException;
use MediaWiki\Revision\SlotRecord;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory;
use Wikibase\Lib\StringNormalizer;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermStoreWriterFactoryTest extends TestCase {

	public static function provideTestCreateWriters() {
		yield [ self::getEntitySourceWithNoEntities(), 'newItemTermStoreWriter', false ];
		yield [ self::getEntitySourceWithNoEntities(), 'newPropertyTermStoreWriter', false ];
		yield [ self::getEntitySourceWithTypes( [ 'item' ] ), 'newItemTermStoreWriter', ItemTermStoreWriter::class ];
		yield [ self::getEntitySourceWithTypes( [ 'item' ] ), 'newPropertyTermStoreWriter', false ];
		yield [ self::getEntitySourceWithTypes( [ 'property' ] ), 'newItemTermStoreWriter', false ];
		yield [ self::getEntitySourceWithTypes( [ 'property' ] ), 'newPropertyTermStoreWriter', PropertyTermStoreWriter::class ];
	}

	/**
	 * @dataProvider provideTestCreateWriters
	 */
	public function testFailsToCreateWriterWhenNotOnLocalEntitySource(
		DatabaseEntitySource $entitySource,
		string $method,
		$expected
	) {
		$databaseTypeIdsStore = $this->createMock( DatabaseTypeIdsStore::class );
		$factory = new TermStoreWriterFactory(
			$entitySource,
			$this->createMock( StringNormalizer::class ),
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$this->newStubRepoDb(),
			$this->createMock( WANObjectCache::class ),
			$this->createMock( JobQueueGroup::class ),
			$this->createMock( LoggerInterface::class )
		);

		if ( !$expected ) {
			$this->expectException( LogicException::class );
		}

		$result = $factory->$method();

		$this->assertInstanceOf( $expected, $result );
	}

	private static function getEntitySourceWithNoEntities() {
		return new DatabaseEntitySource( 'empty', false, [], '', '', '', '' );
	}

	private static function getEntitySourceWithTypes( array $types ) {
		$entityTypeData = [];
		foreach ( $types as $typeName ) {
			$entityTypeData[$typeName] = [ 'namespaceId' => 1, 'slot' => SlotRecord::MAIN ];
		}
		return new DatabaseEntitySource( 'empty', false, $entityTypeData, '', '', '', '' );
	}

	private function newStubRepoDb() {
		return $this->createStub( RepoDomainDb::class );
	}

}
