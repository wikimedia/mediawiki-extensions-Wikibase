<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class NewStatementReadModel extends NewStatement {

	public function build(): Statement {
		$dataModelStatement = parent::build();

		return new Statement(
			WikibaseRepo::getStatementGuidParser()->parse( $dataModelStatement->getGuid() ),
			$dataModelStatement->getRank(),
			$dataModelStatement->getMainSnak(),
			$dataModelStatement->getQualifiers(),
			$dataModelStatement->getReferences()
		);
	}

	public function buildReadAndWriteModel(): array {
		return [ $this->build(), parent::build() ];
	}

}
