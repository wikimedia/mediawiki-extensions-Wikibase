<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedItemIdRequest;

/**
 * @license GPL-2.0-or-later
 */
class DeserializedRequestAdapter implements DeserializedItemIdRequest {
	private array $deserializedRequest;

	public function __construct( array $deserializedRequest ) {
		$this->deserializedRequest = $deserializedRequest;
	}

	public function getItemId(): ItemId {
		return $this->getRequestField( ItemIdRequestValidatingDeserializer::DESERIALIZED_VALUE );
	}

	/**
	 * @return mixed
	 */
	private function getRequestField( string $field ) {
		if ( !isset( $this->deserializedRequest[$field] ) ) {
			throw new LogicException( "'$field' is not part of the request" );
		}

		return $this->deserializedRequest[$field];
	}
}
