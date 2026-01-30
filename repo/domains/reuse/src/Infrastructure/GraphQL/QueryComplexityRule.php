<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\Validator\QueryValidationContext;
use GraphQL\Validator\Rules\QueryComplexity;

/**
 * @license GPL-2.0-or-later
 */
class QueryComplexityRule extends QueryComplexity {
	private bool $wasChecked = false;

	public function getVisitor( QueryValidationContext $context ): array {
		$this->wasChecked = true;
		return parent::getVisitor( $context );
	}

	/**
	 * This method is used in order to check whether getQueryComplexity() can be called,
	 * since getQueryComplexity() errors when it is called and the rule was not used.
	 */
	public function wasChecked(): bool {
		return $this->wasChecked;
	}

	public static function maxQueryComplexityErrorMessage( int $max, int $count ): string {
		$percentageOverMax = ceil( $count / $max * 100 ) - 100;
		return "The query complexity is $percentageOverMax% over the limit.";
	}
}
