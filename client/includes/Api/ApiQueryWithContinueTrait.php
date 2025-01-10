<?php

declare( strict_types=1 );

namespace Wikibase\Client\Api;

use MediaWiki\Api\ApiBase;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * @license GPL-2.0-or-later
 */
trait ApiQueryWithContinueTrait {

	/** @see ApiBase::dieContinueUsageIf() */
	abstract protected function dieContinueUsageIf( bool $condition );

	/** @see IDatabase::select()
	 * @param string|array|IExpression $value
	 */
	abstract protected function addWhere( $value );

	protected function addContinue( string $continueParam, IReadableDatabase $db ): void {
		$continueParams = explode( '|', $continueParam, 3 );
		$this->dieContinueUsageIf( count( $continueParams ) !== 3 );
		// Not quoting the values here - they will be quoted when added to the comparison
		$pageContinue = $continueParams[0];
		$entityContinue = $continueParams[1];
		$aspectContinue = $continueParams[2];
		// Filtering out results that have been shown already and
		// starting the query from where it ended.
		$this->addWhere( $db->buildComparison( '>=', [
			'eu_page_id' => (int)$pageContinue,
			'eu_entity_id' => $entityContinue,
			'eu_aspect' => $aspectContinue,
		] ) );
	}

}
