<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * @license GPL-2.0-or-later
 */
class EntityIdValidator {

	public const CODE_INVALID = 'invalid-entity-id';
	public const CONTEXT_VALUE = 'entity-id-value';

	private EntityIdParser $entityIdParser;

	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
	}

	public function validate( string $entityId ): ?ValidationError {
		try {
			$this->entityIdParser->parse( $entityId );
		} catch ( EntityIdParsingException $e ) {
			return new ValidationError( self::CODE_INVALID, [ self::CONTEXT_VALUE => $entityId ] );
		}

		return null;
	}

}
