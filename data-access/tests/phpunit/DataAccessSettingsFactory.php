<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\DataAccessSettings;

/**
 * Todo: factory methods should return partially mocked objects or mock builder instances instead
 * using PHPUnit\Framework\MockObject\Generator or PHPUnit\Framework\MockObject\MockBuilder.
 * @license GPL-2.0-or-later
 */
class DataAccessSettingsFactory {

	public static function anySettings(): DataAccessSettings {
		return new DataAccessSettings(
			100
		);
	}

}
