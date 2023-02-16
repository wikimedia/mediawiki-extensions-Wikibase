<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Validators;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * @license GPL-2.0-or-later
 */
class EntityUriValidator implements ValueValidator {

	private EntityIdParser $entityIdParser;
	private string $prefix;
	private ?string $entityType;

	public function __construct(
		EntityIdParser $entityIdParser,
		string $prefix,
		?string $entityType = null
	) {
		$this->entityIdParser = $entityIdParser;
		$this->prefix = $prefix;
		$this->entityType = $entityType;
	}

	public function validate( $value ): Result {
		if ( !is_string( $value ) ) {
			throw new InvalidArgumentException( 'Expected a string' );
		}

		if ( !str_starts_with( $value, $this->prefix ) ) {
			return Result::newError( [ Error::newError(
				'Value did not begin with ' . $this->prefix,
				null,
				'bad-prefix', // corresponding message: wikibase-validator-bad-prefix
				[ $value, $this->prefix ]
			) ] );
		}

		$entityIdPart = substr( $value, strlen( $this->prefix ) );

		try {
			$entityId = $this->entityIdParser->parse( $entityIdPart );
		} catch ( EntityIdParsingException $e ) {
			return Result::newError( [ Error::newError(
				'Malformed ID',
				null,
				'bad-entity-id', // corresponding message: wikibase-validator-bad-entity-id
				[ $entityIdPart ]
			) ] );
		}

		if ( $entityId->getSerialization() !== $entityIdPart ) {
			// e.g. "q1" or ":Q1" vs. "Q1"
			return Result::newError( [ Error::newError(
				'Malformed ID',
				null,
				'bad-entity-id', // corresponding message: wikibase-validator-bad-entity-id
				[ $entityIdPart ]
			) ] );
		}

		if ( $this->entityType !== null && $this->entityType !== $entityId->getEntityType() ) {
			return Result::newError( [ Error::newError(
				'Unexpected entity type',
				null,
				'bad-entity-type', // corresponding message: wikibase-validator-bad-entity-type
				[ $entityId->getEntityType() ]
			) ] );
		}

		return Result::newSuccess();
	}

}
