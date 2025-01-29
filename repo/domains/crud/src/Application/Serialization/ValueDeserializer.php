<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization;

use DataValues\DataValue;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\MissingFieldException;

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
