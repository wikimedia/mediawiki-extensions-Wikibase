<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit;

use Wikibase\Lib\Tests\NoBadUsageTestBase;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @coversNothing
 */
class ClientNoBadUsageTest extends NoBadUsageTestBase {

	protected static function getBadPatternsWithAllowedUsages(): array {
		return [
			// don’t reference repo in client
			'WikibaseRepo::' => [
				'config/WikibaseClient.default.php' => 2, // all guarded by thisWikiIsTheRepo
				'tests/phpunit/integration/includes/Usage/ParsoidUsageTrackingTest.php' => true, // to test T255706
			],
			'WikibaseRepo.' => [],
			'Wikibase\\Repo\\' => [
				'config/WikibaseClient.default.php' => 1, // see above
				'tests/phpunit/integration/includes/Usage/ParsoidUsageTrackingTest.php' => true, // to test T255706
			],
			'Wikibase\\\\Repo\\\\' => [],
			// don’t use MediaWiki RDBMS – use our RDBMS instead (DomainDb etc.)
			'/\b(get|I|)LBFactory(?:;)/' => [
				'tests/phpunit/unit/includes/ServiceWiringTestCase.php' => true, // mock
			],
			'/\b((get)?(DB)?|I|)LoadBalancer(Factory)?(?!::|;)/' => [
				'WikibaseClient.ServiceWiring.php' => 3, // RepoDomainDbFactory+ClientDomainDbFactory+TermsDomainDbFactory service wiring
				'tests/phpunit/integration/includes/RecentChanges/RecentChangesFinderTest.php' => true, // TODO migrate?
				'tests/phpunit/integration/includes/Usage/Sql/SqlSubscriptionManagerTest.php' => true, // TODO migrate?
				'tests/phpunit/integration/includes/Usage/Sql/SqlUsageTrackerTest.php' => true, // TODO migrate?
				'tests/phpunit/integration/includes/ChangeModification/ChangeDeletionNotificationJobTest.php' => 1, // make DomainDb
				'tests/phpunit/integration/includes/ChangeModification/ChangeVisibilityNotificationJobTest.php' => 1, // make DomainDb
				'tests/phpunit/unit/includes/ServiceWiringTestCase.php' => true, // mock
			],
		];
	}

	protected static function getBaseDir(): string {
		return __DIR__ . '/../../../../';
	}

	protected static function getThisFile(): string {
		return __FILE__;
	}

}
