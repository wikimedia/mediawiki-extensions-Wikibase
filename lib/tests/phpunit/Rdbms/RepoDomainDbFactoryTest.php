<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Rdbms\DomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikimedia\Rdbms\ILBFactory;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \Wikibase\Lib\Rdbms\RepoDomainDbFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RepoDomainDbFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var MockObject|ILBFactory
	 */
	private $lbFactory;

	/**
	 * @var string
	 */
	private $repoDomainId;

	/**
	 * @var MockObject|EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	protected function setUp(): void {
		parent::setUp();

		$this->lbFactory = $this->createStub( ILBFactory::class );
		$this->repoDomainId = 'repo';
		$this->entitySourceDefinitions = $this->createStub( EntitySourceDefinitions::class );
	}

	public function testNewRepoDb() {
		$repoDomainId = 'someRepoDomain';
		$this->lbFactory = $this->createMock( ILBFactory::class );
		$this->repoDomainId = $repoDomainId;

		$factory = $this->newFactory();

		$this->assertInstanceOf(
			DomainDb::class,
			$factory->newRepoDb()
		);
	}

	public function testNewForEntityType() {
		$expectedDbName = 'itemRepoDb';

		$itemSource = $this->createMock( EntitySource::class );
		$itemSource->expects( $this->once() )
			->method( 'getDatabaseName' )
			->willReturn( $expectedDbName );

		$this->entitySourceDefinitions = $this->createMock( EntitySourceDefinitions::class );
		$this->entitySourceDefinitions->expects( $this->once() )
			->method( 'getSourceForEntityType' )
			->with( Item::ENTITY_TYPE )
			->willReturn( $itemSource );

		$this->lbFactory = $this->newMockLBFactoryForDomain( $expectedDbName );

		$repoDb = $this->newFactory()->newForEntityType( ITEM::ENTITY_TYPE );

		$repoDb->connections();
	}

	public function testDomainMustNotBeEmpty() {
		$this->repoDomainId = '';

		$this->expectException( InvalidArgumentException::class );

		$this->newFactory();
	}

	private function newMockLBFactoryForDomain( string $domain ) {
		$mock = $this->createMock( ILBFactory::class );
		$mock->expects( $this->once() )
			->method( 'getMainLB' )
			->with( $domain )
			->willReturn( $this->createStub( ILoadBalancer::class ) );
		return $mock;
	}

	private function newFactory() {
		return new RepoDomainDbFactory(
			$this->lbFactory,
			$this->repoDomainId,
			$this->entitySourceDefinitions
		);
	}

}
