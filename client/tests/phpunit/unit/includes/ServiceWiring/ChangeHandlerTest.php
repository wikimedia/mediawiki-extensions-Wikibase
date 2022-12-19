<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\LoggerInterface;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\Hooks\WikibaseClientHookRunner;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ChangeHandlerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.EntityRevisionLookup',
			$this->createMock( EntityRevisionLookup::class )
		);

		$this->mockService(
			'WikibaseClient.AffectedPagesFinder',
			$this->createMock( AffectedPagesFinder::class )
		);

		$this->mockService(
			'WikibaseClient.Logger',
			$this->createMock( LoggerInterface::class )
		);

		$this->mockService(
			'WikibaseClient.Settings',
			// Setting Defaults
			new SettingsArray( [
				'purgeCacheBatchSize' => 300,
				'recentChangesBatchSize' => 300,
				'siteGlobalID' => 'test',
				'injectRecentChanges' => true,
			] )
		);

		$this->mockService(
			'WikibaseClient.EntityChangeFactory',
			$this->createMock( EntityChangeFactory::class )
		);

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getJobQueueGroup' );

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getStatsdDataFactory' );

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getTitleFactory' );

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getPageStore' );

		$this->mockService(
			'WikibaseClient.HookRunner',
			$this->createMock( WikibaseClientHookRunner::class )
		);

		$this->assertInstanceOf(
			ChangeHandler::class,
			$this->getService( 'WikibaseClient.ChangeHandler' )
		);
	}

}
