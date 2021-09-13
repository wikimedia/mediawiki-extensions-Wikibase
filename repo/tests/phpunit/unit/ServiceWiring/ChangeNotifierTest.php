<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\User\CentralId\CentralIdLookupFactory;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Notifications\ChangeHolder;
use Wikibase\Repo\Notifications\ChangeNotifier;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ChangeNotifierTest extends ServiceWiringTestCase {

	public function testConstructionWithoutChangesTable(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getHookContainer' );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'useChangesTable' => false,
			] ) );
		$this->mockService( 'WikibaseRepo.EntityChangeFactory',
			$this->createMock( EntityChangeFactory::class ) );
		$centralIdLookupFactory = $this->createMock( CentralIdLookupFactory::class );
		$centralIdLookupFactory->expects( $this->once() )
			->method( 'getNonLocalLookup' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getCentralIdLookupFactory' )
			->willReturn( $centralIdLookupFactory );

		$this->assertInstanceOf(
			ChangeNotifier::class,
			$this->getService( 'WikibaseRepo.ChangeNotifier' )
		);
	}

	public function testConstructionWithChangesTable(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getHookContainer' );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'useChangesTable' => true,
			] ) );
		$this->mockService( 'WikibaseRepo.EntityChangeFactory',
			$this->createMock( EntityChangeFactory::class ) );

		$this->mockService( 'WikibaseRepo.ChangeHolder',
			$this->createMock( ChangeHolder::class ) );

		$this->assertInstanceOf(
			ChangeNotifier::class,
			$this->getService( 'WikibaseRepo.ChangeNotifier' )
		);
	}

}
