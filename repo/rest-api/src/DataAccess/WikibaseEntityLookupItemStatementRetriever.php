<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementRetriever;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemStatementRetriever implements ItemStatementRetriever {

	private $entityLookup;

	public function __construct( EntityLookup $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	public function getStatement( StatementGuid $statementGuid ): ?Statement {
		/** @var Item $item */
		$item = $this->entityLookup->getEntity( $statementGuid->getEntityId() );
		'@phan-var Item $item';

		if ( $item === null ) {
			return null;
		}

		return $item->getStatements()->getFirstStatementWithGuid(
			$statementGuid->getSerialization()
		);
	}

}
