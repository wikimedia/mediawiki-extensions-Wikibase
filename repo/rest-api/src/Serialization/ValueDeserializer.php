<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use DataValues\DataValue;

/**
 * @license GPL-2.0-or-later
 */
interface ValueDeserializer {

	/*
	 * @throws MissingFieldException
	 * @throws InvalidFieldException
	 */
	public function deserialize( string $dataTypeId, array $valueSerialization ): DataValue;

}
