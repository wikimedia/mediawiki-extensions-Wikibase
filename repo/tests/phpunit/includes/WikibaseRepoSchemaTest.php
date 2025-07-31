<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests;

use MediaWiki\Tests\Structure\AbstractSchemaTestBase;

/**
 * @group Wikibase
 * @coversNothing
 * @license GPL-2.0-or-later
 */
class WikibaseRepoSchemaTest extends AbstractSchemaTestBase {

	protected static function getSchemasDirectory(): string {
		return __DIR__ . '/../../../sql/abstract/';
	}

	protected static function getSchemaChangesDirectory(): string {
		return __DIR__ . '/../../../sql/abstractSchemaChanges/';
	}

	protected static function getSchemaSQLDirs(): array {
		return [
			'mysql' => __DIR__ . '/../../../sql/mysql/',
			'sqlite' => __DIR__ . '/../../../sql/sqlite',
			'postgres' => __DIR__ . '/../../../sql/postgres',
		];
	}

	protected static function getSchemaChangesSQLDirs(): array {
		return [
			'mysql' => __DIR__ . '/../../../sql/mysql/archives',
			'sqlite' => __DIR__ . '/../../../sql/sqlite/archives',
			'postgres' => __DIR__ . '/../../../sql/postgres/archives',
		];
	}
}
