<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use DataValues\DataValue;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;

/**
 * @license GPL-2.0-or-later
 */
interface ValueDeserializer {

	/**
	 * @throws MissingFieldException
	 * @throws InvalidFieldException
	 */
	public function deserialize( string $dataTypeId, array $valueSerialization, string $basePath = '' ): DataValue;

}
