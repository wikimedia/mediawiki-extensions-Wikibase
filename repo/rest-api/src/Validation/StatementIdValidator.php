<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;

/**
 * @license GPL-2.0-or-later
 */
class StatementIdValidator {

	private $entityIdParser;

	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
	}

	public function validate( string $statementId, string $source ): ?ValidationError {
		$statementGuidValidator = new StatementGuidValidator( $this->entityIdParser );
		if ( !$statementGuidValidator->validate( $statementId ) ) {
			return new ValidationError( $statementId, $source );
		}

		return null;
	}
}
