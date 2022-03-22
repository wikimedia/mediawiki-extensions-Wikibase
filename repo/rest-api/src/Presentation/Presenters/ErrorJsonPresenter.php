<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

use Wikibase\Repo\RestApi\Domain\Model\ErrorReporter;

/**
 * @license GPL-2.0-or-later
 */
class ErrorJsonPresenter {

	public function getErrorJson( ErrorReporter $error ): string {
		return json_encode( [ 'code' => $error->getCode(), 'message' => $error->getMessage() ] );
	}

}
