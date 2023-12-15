<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\StatementReadModelHelper;

/**
 * @license GPL-2.0-or-later
 */
class NewStatementReadModel extends NewStatement {
	use StatementReadModelHelper;

	public function build(): Statement {
		return $this->newStatementReadModelConverter()->convert( parent::build() );
	}

	public function buildReadAndWriteModel(): array {
		return [ $this->build(), parent::build() ];
	}

}
