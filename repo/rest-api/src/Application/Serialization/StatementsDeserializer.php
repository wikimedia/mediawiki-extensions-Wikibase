<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Statement\StatementList;

/**
 * @license GPL-2.0-or-later
 */
class StatementsDeserializer {

	private StatementDeserializer $statementDeserializer;

	public function __construct( StatementDeserializer $statementDeserializer ) {
		$this->statementDeserializer = $statementDeserializer;
	}

	public function deserialize( array $serialization ): StatementList {
		$statementList = [];
		foreach ( $serialization as $statementGroups ) {
			foreach ( $statementGroups as $statement ) {
				$statementList[] = $this->statementDeserializer->deserialize( $statement );
			}
		}

		return new StatementList( ...$statementList );
	}

}
