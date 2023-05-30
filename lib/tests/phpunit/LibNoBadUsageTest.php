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
				'resources/Resources.php' => 2, // guarded by isRepoEnabled()
			],
			'WikibaseRepo.' => [
				/*
				 * This job only runs on the repo, and arguably its code belongs there,
				 * but moving it is tricky; see previous discussions:
				 * https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/699771#message-0710d319bfe2922bb8a6b88909fcaa037b18edfd
				 * https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/917375#message-e4fbee904f4acee047d00b8e969377995ba80f38
				 */
				'includes/Store/Sql/Terms/CleanTermsIfUnusedJob.php' => 1,
			],
			'Wikibase\\Repo\\' => [
				'resources/Resources.php' => 1, // see above
			],
			'Wikibase\\\\Repo\\\\' => [],
			'WikibaseClient::' => [
				'includes/Modules/RepoAccessModule.php' => 1, // guarded by isClientEnabled()
				'includes/WikibaseSettings.php' => true, // loads settings for both
				'resources/Resources.php' => 2, // guarded by isClientEnabled()
			],
			'WikibaseClient.' => [
				'includes/WikibaseSettings.php' => true, // loads settings for both
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
