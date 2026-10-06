<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\View\Wbui2025FeatureFlag;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class Wbui2025FeatureFlagTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'tmpMobileEditingUI' => false,
			] ) );

		/** @var Wbui2025FeatureFlag $wbui2025FeatureFlag */
		$wbui2025FeatureFlag = $this->getService( 'WikibaseRepo.Wbui2025FeatureFlag' );

		$this->assertInstanceOf( Wbui2025FeatureFlag::class, $wbui2025FeatureFlag );
	}

}
