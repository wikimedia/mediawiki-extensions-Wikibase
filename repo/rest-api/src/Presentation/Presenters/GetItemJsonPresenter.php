<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

use Wikibase\Repo\RestApi\Domain\Serializers\ItemDataSerializer;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResponse;

/**
 * @license GPL-2.0-or-later
 */
class GetItemJsonPresenter {

	private $itemDataSerializer;

	public function __construct( ItemDataSerializer $itemDataSerializer ) {
		$this->itemDataSerializer = $itemDataSerializer;
	}

	public function getJson( GetItemSuccessResponse $response ): string {
		return json_encode(
			$this->itemDataSerializer->serialize( $response->getItemData() )
		);
	}
}
