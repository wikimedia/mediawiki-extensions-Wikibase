<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

use Wikibase\Repo\RestApi\Presentation\EmptyArrayToObjectConverter;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemResult;

/**
 * @license GPL-2.0-or-later
 */
class GetItemJsonPresenter {

	private $emptyArrayToObjectConverter;

	public function __construct() {
		$this->emptyArrayToObjectConverter = new EmptyArrayToObjectConverter( [
			'labels',
			'descriptions',
			'aliases',
			'statements',
			'statements/*/*/qualifiers',
			'sitelinks',
		] );
	}

	public function getJsonItem( GetItemResult $result ): string {
		return json_encode( $this->emptyArrayToObjectConverter->convert( $result->getItem() ) );
	}
}
