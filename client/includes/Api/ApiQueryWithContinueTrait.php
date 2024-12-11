<?php

declare( strict_types=1 );

namespace Wikibase\Client\Api;

use Wikimedia\Message\MessageSpecifier;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\IReadableDatabase;

/**
 * @license GPL-2.0-or-later
 */
trait ApiQueryWithContinueTrait {

	/**
	 * @see ApiQuery::addWarning()
	 * @param string|array|MessageSpecifier $msg See ApiErrorFormatter::addWarning()
	 * @param string|null $code See ApiErrorFormatter::addWarning()
	 * @param array|null $data See ApiErrorFormatter::addWarning()
	 */
	abstract public function addWarning( $msg, $code = null, $data = null );

	/**
	 * @return string
	 */
	abstract public function getModulePrefix();

	/** @see IDatabase::select()
	 * @param string|array|IExpression $value
	 */
	abstract protected function addWhere( $value );

	protected function addContinue( string $continueParam, IReadableDatabase $db ): void {
		$continueParams = explode( '|', $continueParam, 3 );
		if ( count( $continueParams ) !== 3 ) {
			$this->addWarning( [
				'apiwarn-ignoring-invalid-continue-parameter',
				$this->getModulePrefix() . 'continue',
				$continueParam,
			] );
			return;
		}
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
