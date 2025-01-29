<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization;

use ArrayObject;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;

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

		foreach ( $statementList as $statement ) {
			$propertyId = $statement->getProperty()->getId()->getSerialization();
			$serialization[$propertyId][] = $this->statementSerializer->serialize( $statement );
		}

		return $serialization;
	}

}
