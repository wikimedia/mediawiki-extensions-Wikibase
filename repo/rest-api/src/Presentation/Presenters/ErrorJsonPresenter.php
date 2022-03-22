<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

use Wikibase\Repo\RestApi\UseCases\ErrorResult;

/**
 * @license GPL-2.0-or-later
 */
class ErrorJsonPresenter {

	public function getJson( ErrorResult $error ): string {
		return json_encode( [ 'code' => $error->getCode(), 'message' => $error->getMessage() ] );
	}

}
