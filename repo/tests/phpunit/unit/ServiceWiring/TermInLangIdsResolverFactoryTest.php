<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\TermInLangIdsResolverFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermInLangIdsResolverFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.Logger',
			new NullLogger()
		);

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getMainWANObjectCache' );

		$this->mockService(
			'WikibaseRepo.TermsDomainDbFactory',
			$this->createStub( TermsDomainDbFactory::class )
		);

		$this->assertInstanceOf(
			TermInLangIdsResolverFactory::class,
			$this->getService( 'WikibaseRepo.TermInLangIdsResolverFactory' )
		);
	}

}
