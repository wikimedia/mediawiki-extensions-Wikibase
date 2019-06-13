<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\DataAccessSettings;

/**
 * Todo: factory methods should return partially mocked objects or mock builder instances instead
 * using PHPUnit\Framework\MockObject\Generator or PHPUnit\Framework\MockObject\MockBuilder.
 */
class DataAccessSettingsFactory {

	public static function anySettings(): DataAccessSettings {
		return self::repositoryPrefixBasedFederation();
	}

	public static function repositoryPrefixBasedFederation(): DataAccessSettings {
		return new DataAccessSettings(
			100,
			true,
			false,
			DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION,
			DataAccessSettings::PROPERTY_TERMS_UNNORMALIZED
		);
	}

	public static function entitySourceBasedFederation(): DataAccessSettings {
		return new DataAccessSettings(
			100,
			true,
			false,
			DataAccessSettings::USE_ENTITY_SOURCE_BASED_FEDERATION,
			DataAccessSettings::PROPERTY_TERMS_UNNORMALIZED
		);
	}

}
