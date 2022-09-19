<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Serialization;

use Deserializers\Deserializer;
use Wikibase\DataModel\Deserializers\StatementDeserializer as LegacyStatementDeserializer;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StatementDeserializer implements Deserializer {

	private $statementDeserializer;

	public function __construct( LegacyStatementDeserializer $statementDeserializer ) {

		$this->statementDeserializer = $statementDeserializer;
	}

	/**
	 * @param mixed $serialization
	 */
	public function deserialize( $serialization ): Statement {
		$serialization['type'] = 'statement';
		return $this->statementDeserializer->deserialize( $serialization );
	}
}
