<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use JobQueueGroup;
use LogicException;
use MediaWiki\Revision\SlotRecord;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory;
use Wikibase\Lib\StringNormalizer;

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
		$factory = new TermStoreWriterFactory(
			$entitySource,
			$this->createMock( StringNormalizer::class ),
			$this->createStub( TermsDomainDb::class ),
			$this->createMock( JobQueueGroup::class ),
			new NullLogger()
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

}
