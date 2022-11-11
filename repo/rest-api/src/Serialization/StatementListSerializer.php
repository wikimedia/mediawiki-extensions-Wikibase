<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use ArrayObject;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @license GPL-2.0-or-later
 */
class StatementListSerializer {

	private StatementSerializer $statementSerializer;

	public function __construct( StatementSerializer $statementSerializer ) {
		$this->statementSerializer = $statementSerializer;
	}

	public function serialize( StatementList $statementList ): ArrayObject {
		$serialization = new ArrayObject();

		foreach ( $statementList->toArray() as $statement ) {
			$idSerialization = $statement->getPropertyId()->getSerialization();

			if ( !$serialization->offsetExists( $idSerialization ) ) {
				$serialization[$idSerialization] = [];
			}

			$serialization[$idSerialization][] = $this->statementSerializer->serialize( $statement );
		}

		return $serialization;
	}

}
