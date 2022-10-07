<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit;

use Wikibase\Lib\Tests\NoBadUsageTest;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @coversNothing
 */
class RepoNoBadUsageTest extends NoBadUsageTest {

	protected function getBadPatternsWithAllowedUsages(): array {
		return [
			// don’t reference client in repo
			'WikibaseClient::' => [
				'includes/ChangeModification/DispatchChangesJob.php' => 1, // guarded by isClientEnabled()
			],
			'Wikibase\\Client\\' => [
				'includes/ChangeModification/DispatchChangesJob.php' => 1, // see above
			],
			'Wikibase\\\\Client\\\\' => [],
			// don’t use MediaWiki RDBMS – use our RDBMS instead (DomainDb etc.)
			'/\b(get|I|)LBFactory(?:;)/' => [
				'tests/phpunit/includes/GlobalStateFactoryMethodsResourceTest.php' => 1, // mock
				'tests/phpunit/unit/ServiceWiringTestCase.php' => true, // mock
			],
			'/\b((get)?(DB)?|I|)LoadBalancer(Factory)?(?!::|;)/' => [
				'WikibaseRepo.ServiceWiring.php' => 1, // RepoDomainDbFactory service wiring
				'tests/phpunit/includes/GlobalStateFactoryMethodsResourceTest.php' => 1, // mock
				'tests/phpunit/includes/Store/Sql/WikiPageEntityMetaDataLookupTest.php' => true, // mock
				'tests/phpunit/unit/ServiceWiringTestCase.php' => true, // mock
			],
			'wfGetDB' => [],
		];
	}

	protected function getBaseDir(): string {
		return __DIR__ . '/../../../';
	}

	protected function getThisFile(): string {
		return __FILE__;
	}

}
