<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\LoggerInterface;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\TermInLangIdsResolverFactory;

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
			'WikibaseClient.Logger',
			$this->createMock( LoggerInterface::class )
		);

		$this->mockService(
			'WikibaseClient.RepoDomainDbFactory',
			$this->createStub( RepoDomainDbFactory::class )
		);

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getMainWANObjectCache' );

		$this->assertInstanceOf(
			TermInLangIdsResolverFactory::class,
			$this->getService( 'WikibaseClient.TermInLangIdsResolverFactory' )
		);
	}

}
