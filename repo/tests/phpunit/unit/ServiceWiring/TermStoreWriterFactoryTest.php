<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermStoreWriterFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseRepo.LocalEntitySource',
			$this->createMock( DatabaseEntitySource::class ) );
		$this->mockService( 'WikibaseRepo.StringNormalizer',
			new StringNormalizer() );
		$dbFactory = $this->createStub( TermsDomainDbFactory::class );
		$dbFactory->method( 'newTermsDb' )
			->willReturn( $this->createStub( TermsDomainDb::class ) );
		$this->mockService( 'WikibaseRepo.TermsDomainDbFactory', $dbFactory );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getJobQueueGroup' );
		$this->mockService( 'WikibaseRepo.Logger',
			new NullLogger() );

		$this->assertInstanceOf(
			TermStoreWriterFactory::class,
			$this->getService( 'WikibaseRepo.TermStoreWriterFactory' )
		);
	}

}
