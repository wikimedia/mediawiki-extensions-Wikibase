<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

use DataValues\DataValue;

/**
 * @license GPL-2.0-or-later
 */
interface DataValueValidator {

	public const CODE_INVALID_DATA_VALUE = 'invalid-data-value';

	public function validate( string $dataTypeId, DataValue $dataValue ): ?ValidationError;

}
