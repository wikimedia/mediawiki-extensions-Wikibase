<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\Statement as DataModelStatement;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement as ReadModelStatement;

/**
 * @license GPL-2.0-or-later
 */
class StatementReadModelConverter {

	private StatementGuidParser $statementIdParser;

	public function __construct( StatementGuidParser $statementIdParser ) {
		$this->statementIdParser = $statementIdParser;
	}

	public function convert( DataModelStatement $inputStatement ): ReadModelStatement {
		return new ReadModelStatement(
			$this->statementIdParser->parse( $inputStatement->getGuid() ),
			$inputStatement->getRank(),
			$inputStatement->getMainSnak(),
			$inputStatement->getQualifiers(),
			$inputStatement->getReferences()
		);
	}

}
