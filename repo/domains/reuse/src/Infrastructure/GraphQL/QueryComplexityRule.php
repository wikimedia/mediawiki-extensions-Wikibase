<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\Validator\Rules\QueryComplexity;

/**
 * @license GPL-2.0-or-later
 */
class QueryComplexityRule extends QueryComplexity {

	public static function maxQueryComplexityErrorMessage( int $max, int $count ): string {
		$percentageOverMax = ceil( $count / $max * 100 ) - 100;
		return "The query complexity is $percentageOverMax% over the limit.";
	}
}
