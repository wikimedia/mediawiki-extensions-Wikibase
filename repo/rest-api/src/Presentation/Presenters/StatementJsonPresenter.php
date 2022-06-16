<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Domain\Serializers\StatementSerializer;

/**
 * @license GPL-2.0-or-later
 */
class StatementJsonPresenter {

	private $serializer;

	public function __construct( StatementSerializer $serializer ) {
		$this->serializer = $serializer;
	}

	public function getJson( Statement $statement ): string {
		return json_encode( $this->serializer->serialize( $statement ) );
	}
}
