<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

/**
 * A {@link DomainDb} to access a repo wiki.
 *
 * Use this class to access database tables created by the WikibaseRepository extension.
 * or otherwise belonging to a repo wiki.
 * (This access may happen in repo, client, or lib.)
 *
 * @license GPL-2.0-or-later
 */
class RepoDomainDb extends DomainDb {

}
