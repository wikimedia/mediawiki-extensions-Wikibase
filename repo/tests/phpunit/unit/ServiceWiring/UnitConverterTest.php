<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnitConverterTest extends ServiceWiringTestCase {

	private function mockServices( $settingsArray = [] ) {
		$this->mockService(
			'WikibaseRepo.Settings',
			new SettingsArray( $settingsArray )
		);
	}

	public function testConstruction(): void {
		$this->mockServices(
			[
				'unitStorage' => [
					'class' => '\\Wikibase\\Lib\\Units\\JsonUnitStorage',
					'args' => [ 'somedir' ],
				],
			]
		);
		$this->mockService( 'WikibaseRepo.ItemVocabularyBaseUri', 'url' );
		$this->assertInstanceOf(
			UnitConverter::class,
			$this->getService( 'WikibaseRepo.UnitConverter' )
		);
	}

	public function testServiceReturnNull(): void {
		$this->mockServices();
		$this->assertNull( $this->getService( 'WikibaseRepo.UnitConverter' ) );
	}
}
