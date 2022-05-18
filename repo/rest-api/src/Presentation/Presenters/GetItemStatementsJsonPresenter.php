<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\Repo\RestApi\UseCases\GetItemStatements\GetItemStatementsSuccessResponse;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsJsonPresenter {

	private $serializer;

	/**
	 * @param StatementListSerializer $serializer Should have $useObjectsForMaps (e.g. for qualifiers) set to true.
	 */
	public function __construct( StatementListSerializer $serializer ) {
		$this->serializer = $serializer;
	}

	public function getJson( GetItemStatementsSuccessResponse $response ): string {
		return json_encode( $this->serializer->serialize( $response->getStatements() ) );
	}
}
