<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @coversNothing
 */
class LibNoBadUsageTest extends NoBadUsageTest {

	protected function getBadPatternsWithAllowedUsages(): array {
		return [
			// don’t reference repo or client in lib
			'WikibaseRepo::' => [
				'includes/WikibaseSettings.php' => true, // loads settings for both
				'resources/Resources.php' => 1, // guarded by isRepoEnabled()
			],
			'Wikibase\\Repo\\' => [
				'resources/Resources.php' => 1, // see above
			],
			'Wikibase\\\\Repo\\\\' => [],
			'WikibaseClient::' => [
				'includes/Modules/RepoAccessModule.php' => 1, // guarded by isClientEnabled()
				'includes/WikibaseSettings.php' => true, // loads settings for both
				'resources/Resources.php' => 1, // guarded by isClientEnabled()
			],
			'Wikibase\\Client\\' => [
				'includes/Modules/RepoAccessModule.php' => 1, // see above
				'resources/Resources.php' => 1, // see above
			],
			'Wikibase\\\\Client\\\\' => [],
			// don’t use MediaWiki RDBMS – use our RDBMS instead (DomainDb etc.)
			'/\b(get|I|)LBFactory(?:;)/' => [
				'includes/Rdbms/' => true,
				'tests/phpunit/GlobalStateFactoryMethodsResourceTest.php' => 1, // mock
				'tests/phpunit/Rdbms/' => true,
				'tests/phpunit/Store/Sql/Terms/Util/FakeLBFactory.php' => true,
			],
			'/\b((get)?(DB)?|I|)LoadBalancer(Factory)?(?!::|;)/' => [
				'includes/Rdbms/' => true,
				'tests/phpunit/GlobalStateFactoryMethodsResourceTest.php' => 1, // mock
				'tests/phpunit/Rdbms/' => true,
				'tests/phpunit/Store/Sql/Terms/Util/FakeLBFactory.php' => true,
				'tests/phpunit/Store/Sql/Terms/Util/FakeLoadBalancer.php' => true,
			],
			'wfGetDB' => [],
		];
	}

	protected function getBaseDir(): string {
		return __DIR__ . '/../../';
	}

	protected function getThisFile(): string {
		return __FILE__;
	}

}
