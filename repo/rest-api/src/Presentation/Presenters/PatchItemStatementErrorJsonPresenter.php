<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

use Wikibase\Repo\RestApi\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class PatchItemStatementErrorJsonPresenter extends ErrorJsonPresenter {

	private StatementSerializer $statementSerializer;

	public function __construct( StatementSerializer $statementSerializer ) {
		$this->statementSerializer = $statementSerializer;
	}

	public function getJson( ErrorResponse $error ): string {
		$context = $error->getContext();
		if ( is_array( $context ) && array_key_exists( 'patched-statement', $context ) ) {
			$context['patched-statement'] = $this->statementSerializer->serialize( $context['patched-statement'] );
		}
		return parent::getJson( new ErrorResponse( $error->getCode(), $error->getMessage(), $context ) );
	}

}
