<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\Rdbms\DomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikimedia\Rdbms\ILBFactory;

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

	protected function setUp(): void {
		parent::setUp();

		$this->lbFactory = $this->createStub( ILBFactory::class );
		$this->repoDomainId = 'repo';
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

	public function testNewForEntitySource() {
		$expectedDbName = 'itemRepoDb';

		$itemSource = $this->createMock( DatabaseEntitySource::class );
		$itemSource->expects( $this->once() )
			->method( 'getDatabaseName' )
			->willReturn( $expectedDbName );

		$this->lbFactory = $this->createStub( ILBFactory::class );

		$db = $this->newFactory()->newForEntitySource( $itemSource );

		$this->assertSame( $expectedDbName, $db->domain() );
	}

	public function testDomainMustNotBeEmpty() {
		$this->repoDomainId = '';

		$this->expectException( InvalidArgumentException::class );

		$this->newFactory();
	}

	private function newFactory() {
		return new RepoDomainDbFactory(
			$this->lbFactory,
			$this->repoDomainId
		);
	}

}
