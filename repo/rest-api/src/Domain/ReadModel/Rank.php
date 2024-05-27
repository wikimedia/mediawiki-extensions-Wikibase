<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\Statement as StatementWriteModel;

/**
 * @license GPL-2.0-or-later
 */
class Rank {

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

	public static function deprecated(): self {
		return new self( StatementWriteModel::RANK_DEPRECATED );
	}

	public static function normal(): self {
		return new self( StatementWriteModel::RANK_NORMAL );
	}

	public static function preferred(): self {
		return new self( StatementWriteModel::RANK_PREFERRED );
	}

	public function asInt(): int {
		return $this->rank;
	}

}
