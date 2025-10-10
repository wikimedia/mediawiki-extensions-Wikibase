<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\Statement as StatementWriteModel;

/**
 * @license GPL-2.0-or-later
 */class Rank {

	private const RANKS = [
		StatementWriteModel::RANK_DEPRECATED,
		StatementWriteModel::RANK_NORMAL,
		StatementWriteModel::RANK_PREFERRED,
	];
	private int $rank;

	public function __construct( int $rank ) {
		if ( !in_array( $rank, self::RANKS, true ) ) {
			throw new InvalidArgumentException( 'Invalid rank specified for statement: ' . var_export( $rank, true ) );
		}
		$this->rank = $rank;
	}

	public function asInt(): int {
		return $this->rank;
	}

}
