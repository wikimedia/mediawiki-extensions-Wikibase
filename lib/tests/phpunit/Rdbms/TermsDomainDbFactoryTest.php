<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Rdbms;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Rdbms\RepoDomainTermsDb;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;

/**
 * @covers \Wikibase\Lib\Rdbms\TermsDomainDbFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermsDomainDbFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testNewTermsDb(): void {
		$repoDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$repoDbFactory->expects( $this->once() )
			->method( 'newRepoDb' )
			->willReturn( $this->createStub( RepoDomainDb::class ) );

		$this->assertInstanceOf(
			RepoDomainTermsDb::class,
			( new TermsDomainDbFactory( $repoDbFactory ) )->newTermsDb()
		);
	}

	public function testNewForEntitySource(): void {
		$entitySource = $this->createStub( DatabaseEntitySource::class );
		$repoDbFactory = $this->createMock( RepoDomainDbFactory::class );
		$repoDbFactory->expects( $this->once() )
			->method( 'newForEntitySource' )
			->with( $entitySource )
			->willReturn( $this->createStub( RepoDomainDb::class ) );

		$this->assertInstanceOf(
			RepoDomainTermsDb::class,
			( new TermsDomainDbFactory( $repoDbFactory ) )->newForEntitySource( $entitySource )
		);
	}

}
