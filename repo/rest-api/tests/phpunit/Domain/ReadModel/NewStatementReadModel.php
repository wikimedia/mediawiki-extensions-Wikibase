<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class NewStatementReadModel extends NewStatement {

	public function build(): Statement {
		return ( new StatementReadModelConverter( WikibaseRepo::getStatementGuidParser() ) )
			->convert( parent::build() );
	}

	public function buildReadAndWriteModel(): array {
		return [ $this->build(), parent::build() ];
	}

}
