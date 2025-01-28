<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Rdbms;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Rdbms\RepoDomainTermsDb;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;
use Wikibase\Lib\Rdbms\VirtualTermsDomainDb;
use Wikimedia\Rdbms\LBFactory;

/**
 * @covers \Wikibase\Lib\Rdbms\TermsDomainDbFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermsDomainDbFactoryTest extends \PHPUnit\Framework\TestCase {

	private bool $hasVirtualTermsDomain;
	private LBFactory $lbFactory;
	private RepoDomainDbFactory $repoDbFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->hasVirtualTermsDomain = false;
		$this->lbFactory = $this->createStub( LBFactory::class );
		$this->repoDbFactory = $this->createStub( RepoDomainDbFactory::class );
	}

	public function testGivenNoVirtualTermsDomain_newTermsDbReturnsRepoDomainTermsDb(): void {
		$this->hasVirtualTermsDomain = false;

		$this->repoDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$this->repoDbFactory->expects( $this->once() )
			->method( 'newRepoDb' )
			->willReturn( $this->createStub( RepoDomainDb::class ) );

		$this->assertInstanceOf(
			RepoDomainTermsDb::class,
			$this->newFactory()->newTermsDb()
		);
	}

	public function testGivenNoVirtualTermsDomain_newForEntitySourceReturnsRepoDomainTermsDb(): void {
		$entitySource = $this->createStub( DatabaseEntitySource::class );
		$this->repoDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$this->repoDbFactory->expects( $this->once() )
			->method( 'newForEntitySource' )
			->with( $entitySource )
			->willReturn( $this->createStub( RepoDomainDb::class ) );

		$this->assertInstanceOf(
			RepoDomainTermsDb::class,
			$this->newFactory()->newForEntitySource( $entitySource )
		);
	}

	public function testGivenVirtualTermsDomain_newTermsDbReturnsVirtualTermsDomainDb(): void {
		$this->hasVirtualTermsDomain = true;

		$this->repoDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$this->repoDbFactory->expects( $this->never() )->method( $this->anything() );

		$this->assertInstanceOf(
			VirtualTermsDomainDb::class,
			$this->newFactory()->newTermsDb()
		);
	}

	public function testGivenVirtualTermsDomain_newForEntitySourceReturnsVirtualTermsDomainDb(): void {
		$this->hasVirtualTermsDomain = true;
		$entitySource = $this->createStub( DatabaseEntitySource::class );

		$this->repoDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$this->repoDbFactory->expects( $this->never() )->method( $this->anything() );

		$this->assertInstanceOf(
			VirtualTermsDomainDb::class,
			$this->newFactory()->newForEntitySource( $entitySource )
		);
	}

	private function newFactory(): TermsDomainDbFactory {
		return new TermsDomainDbFactory( $this->hasVirtualTermsDomain, $this->lbFactory, $this->repoDbFactory );
	}

}
