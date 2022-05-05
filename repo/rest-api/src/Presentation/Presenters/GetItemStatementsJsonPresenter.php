<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

use Wikibase\Repo\RestApi\Presentation\EmptyArrayToObjectConverter;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsSuccessResponse;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsJsonPresenter {

	private $emptyArrayToObjectConverter;

	public function __construct() {
		$this->emptyArrayToObjectConverter = new EmptyArrayToObjectConverter(
			[ '/', '/*/*/qualifiers' ]
		);
	}

	public function getJson( GetItemStatementsSuccessResponse $response ): string {
		return json_encode( $this->emptyArrayToObjectConverter->convert( $response->getStatements() ) );
	}
}
