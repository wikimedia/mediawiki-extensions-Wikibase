<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * Database abstraction to access terms (labels, descriptions, aliases) database tables created by the WikibaseRepository extension.
 * (This access may happen in repo, client, or lib.)
 *
 * The underlying database is either using a {@link RepoDomainDb}, or a dedicated virtual domain database.
 *
 * @license GPL-2.0-or-later
 */
interface TermsDomainDb {

	public function getWriteConnection(): IDatabase;

	public function getAutoCommitPrimaryConnection(): IDatabase;

	public function getReadConnection( ?array $groups = null ): IReadableDatabase;

	public function waitForReplicationOfAllAffectedClusters( ?int $timeout = null ): void;

}
