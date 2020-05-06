<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use JobQueueGroup;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use WANObjectCache;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory;
use Wikibase\Lib\StringNormalizer;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermStoreWriterFactoryTest extends TestCase {

	public function provideTestCreateWriters() {
		yield [ $this->getEntitySourceWithNoEntities(), 'newItemTermStoreWriter', false ];
		yield [ $this->getEntitySourceWithNoEntities(), 'newPropertyTermStoreWriter', false ];
		yield [ $this->getEntitySourceWithTypes( [ 'item' ] ), 'newItemTermStoreWriter', ItemTermStoreWriter::class ];
		yield [ $this->getEntitySourceWithTypes( [ 'item' ] ), 'newPropertyTermStoreWriter', false ];
		yield [ $this->getEntitySourceWithTypes( [ 'property' ] ), 'newItemTermStoreWriter', false ];
		yield [ $this->getEntitySourceWithTypes( [ 'property' ] ), 'newPropertyTermStoreWriter', PropertyTermStoreWriter::class ];
	}

	/**
	 * @dataProvider provideTestCreateWriters
	 */
	public function testFailsToCreateWriterWhenNotOnLocalEntitySource(
		EntitySource $entitySource,
		string $method,
		$expected
	) {
		$factory = new TermStoreWriterFactory(
			$entitySource,
			$this->createMock( StringNormalizer::class ),
			$this->getBasicStubLBFactory(),
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

	private function getEntitySourceWithNoEntities() {
		return new EntitySource( 'empty', false, [], '', '', '', '' );
	}

	private function getEntitySourceWithTypes( array $types ) {
		$entityTypeData = [];
		foreach ( $types as $typeName ) {
			$entityTypeData[$typeName] = [ 'namespaceId' => 1, 'slot' => 'main' ];
		}
		return new EntitySource( 'empty', false, $entityTypeData, '', '', '', '' );
	}

	private function getBasicStubLBFactory() {
		$lbFactory = $this->createStub( LBFactory::class );
		$lbFactory->method( 'getMainLB' )->willReturn(
			$this->createMock( LoadBalancer::class )
		);
		return $lbFactory;
	}

}
