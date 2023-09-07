<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedLanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedPropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedPropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedStatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;

/**
 * @license GPL-2.0-or-later
 */
class DeserializedRequestAdapter implements
	DeserializedItemIdRequest,
	DeserializedPropertyIdRequest,
	DeserializedStatementIdRequest,
	DeserializedPropertyIdFilterRequest,
	DeserializedLanguageCodeRequest
{
	private array $deserializedRequest;

	public function __construct( array $deserializedRequest ) {
		$this->deserializedRequest = $deserializedRequest;
	}

	public function getItemId(): ItemId {
		return $this->getRequestField( ItemIdRequest::class );
	}

	public function getPropertyId(): NumericPropertyId {
		return $this->getRequestField( PropertyIdRequest::class );
	}

	public function getStatementId(): StatementGuid {
		return $this->getRequestField( StatementIdRequest::class );
	}

	public function getPropertyIdFilter(): ?PropertyId {
		return $this->getRequestField( PropertyIdFilterRequest::class );
	}

	public function getLanguageCode(): string {
		return $this->getRequestField( LanguageCodeRequest::class );
	}

	/**
	 * @return mixed
	 */
	private function getRequestField( string $field ) {
		if ( !array_key_exists( $field, $this->deserializedRequest ) ) {
			throw new LogicException( "'$field' is not part of the request" );
		}

		return $this->deserializedRequest[$field];
	}
}
