<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
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

	public function getWriteConnection( int $flags = 0 ): IDatabase;

	public function getReadConnection( ?array $groups = null, int $flags = 0 ): IReadableDatabase;

	public function waitForReplicationOfAllAffectedClusters( ?int $timeout = null ): void;

	/**
	 * @deprecated Don't use this unless it needs to be passed to a service we don't control
	 */
	public function loadBalancer(): ILoadBalancer;

	/**
	 * @deprecated Don't use this unless it needs to be passed to a service we don't control
	 */
	public function domain(): string;

}
