<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\Statement as StatementDataModel;

/**
 * @license GPL-2.0-or-later
 */
class Statements {

	private array $statements;

	public function __construct( Statement ...$statements ) {
		$this->statements = $statements;
	}

	/**
	 * @return Statement[]
	 */
	public function getStatementsByPropertyId( PropertyId $id ): array {
		return array_values( array_filter(
			$this->statements,
			fn( Statement $s ) => $s->property->id->equals( $id )
		) );
	}

	public function getBestStatementsByPropertyId( PropertyId $id ): array {
		$statements = $this->getStatementsByPropertyId( $id );
		$preferred = array_values( array_filter(
			$statements,
			fn( Statement $s ) => $s->rank->asInt() === StatementDataModel::RANK_PREFERRED
		) );
		if ( count( $preferred ) > 0 ) {
			return $preferred;
		}
		return array_values( array_filter(
			$statements,
			fn( Statement $s ) => $s->rank->asInt() === StatementDataModel::RANK_NORMAL
		) );
	}
}
