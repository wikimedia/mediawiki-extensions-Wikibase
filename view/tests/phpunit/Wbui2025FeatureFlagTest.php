<?php

declare( strict_types = 1 );

namespace Wikibase\View\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Lib\SettingsArray;
use Wikibase\View\Wbui2025FeatureFlag;

/**
 * @covers \Wikibase\View\Wbui2025FeatureFlag
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 */
class Wbui2025FeatureFlagTest extends TestCase {

	public static function provideOptionCombinations() {
		yield 'expect wbui2025 == true if global feature active' => [ true, true ];
		yield 'expect wbui2025 == false if feature inactive' => [ false, false ];
	}

	/**
	 * @dataProvider provideOptionCombinations
	 */
	public function testWbui2025IfGlobalFeatureActive(
		$wbui2025Enabled,
		$wbuiFlagValue
	) {
		$flag = new Wbui2025FeatureFlag(
			new SettingsArray( [
				'tmpMobileEditingUI' => $wbui2025Enabled,
			] ),
		);
		$this->assertSame( $wbuiFlagValue, $flag->shouldRenderAsWbui2025() );
	}

}
