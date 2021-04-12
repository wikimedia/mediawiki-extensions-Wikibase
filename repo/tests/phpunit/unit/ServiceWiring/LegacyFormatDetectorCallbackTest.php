<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LegacyFormatDetectorCallbackTest extends ServiceWiringTestCase {

	public function testWithTransform(): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'transformLegacyFormatOnExport' => true,
			] ) );

		$this->assertIsCallable(
			$this->getService( 'WikibaseRepo.LegacyFormatDetectorCallback' )
		);
	}

	public function testWithoutTransform(): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'transformLegacyFormatOnExport' => false,
			] ) );

		$this->assertNull(
			$this->getService( 'WikibaseRepo.LegacyFormatDetectorCallback' )
		);
	}

}
