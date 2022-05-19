<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

use Wikibase\Repo\RestApi\Domain\Serializers\ItemDataSerializer;
use Wikibase\Repo\RestApi\Presentation\EmptyArrayToObjectConverter;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemSuccessResponse;

/**
 * @license GPL-2.0-or-later
 */
class GetItemJsonPresenter {

	private $emptyArrayToObjectConverter;
	private $itemDataSerializer;

	public function __construct( ItemDataSerializer $itemDataSerializer ) {
		$this->emptyArrayToObjectConverter = new EmptyArrayToObjectConverter( [
			'/labels',
			'/descriptions',
			'/aliases',
			'/statements',
			'/statements/*/*/qualifiers',
			'/sitelinks',
		] );
		$this->itemDataSerializer = $itemDataSerializer;
	}

	public function getJson( GetItemSuccessResponse $response ): string {
		return json_encode( $this->emptyArrayToObjectConverter->convert(
			$this->itemDataSerializer->serialize( $response->getItemData() )
		) );
	}
}
