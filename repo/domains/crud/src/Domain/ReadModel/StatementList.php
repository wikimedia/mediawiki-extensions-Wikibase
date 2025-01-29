<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use ArrayIterator;
use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @license GPL-2.0-or-later
 */
class StatementList extends ArrayIterator {

	public function __construct( Statement ...$statements ) {
		parent::__construct( $statements );
	}

	public function getStatementById( StatementGuid $id ): ?Statement {
		foreach ( $this as $statement ) {
			if ( $id->equals( $statement->getGuid() ) ) {
				return $statement;
			}
		}

		return null;
	}

}
