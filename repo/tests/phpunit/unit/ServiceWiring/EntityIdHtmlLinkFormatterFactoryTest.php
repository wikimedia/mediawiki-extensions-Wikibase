<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityIdHtmlLinkFormatterFactory;
use Wikibase\Repo\FederatedProperties\WrappingEntityIdFormatterFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdHtmlLinkFormatterFactoryTest extends ServiceWiringTestCase {

	/** @dataProvider settingsProvider */
	public function testConstruction( $settings, $expectedClass ): void {
		$this->mockService(
			'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] )
		);

		$this->mockService(
			'WikibaseRepo.Settings',
			new SettingsArray( $settings )
		);

		$this->assertInstanceOf(
			$expectedClass,
			$this->getService( 'WikibaseRepo.EntityIdHtmlLinkFormatterFactory' )
		);
	}

	public function settingsProvider(): iterable {
		yield 'federated properties disabled' => [ [
			'federatedPropertiesEnabled' => false,
		], EntityIdHtmlLinkFormatterFactory::class ];

		yield 'federated properties enabled' => [ [
			'federatedPropertiesEnabled' => true,
		], WrappingEntityIdFormatterFactory::class ];
	}

}
