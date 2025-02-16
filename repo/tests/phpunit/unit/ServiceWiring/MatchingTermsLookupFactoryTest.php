<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;
use Wikibase\Lib\Store\MatchingTermsLookupFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MatchingTermsLookupFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityIdComposer',
			$this->createMock( EntityIdComposer::class )
		);

		$this->mockService(
			'WikibaseRepo.Logger',
			new NullLogger()
		);

		$this->mockService(
			'WikibaseRepo.TermsDomainDbFactory',
			$this->createStub( TermsDomainDbFactory::class )
		);

		$this->assertInstanceOf(
			MatchingTermsLookupFactory::class,
			$this->getService( 'WikibaseRepo.MatchingTermsLookupFactory' )
		);
	}

}
