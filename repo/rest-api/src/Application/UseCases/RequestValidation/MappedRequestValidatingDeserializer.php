<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class MappedRequestValidatingDeserializer {

	/**
	 * @var callable
	 */
	private $mapRequest;

	public function __construct( callable $mapRequest ) {
		$this->mapRequest = $mapRequest;
	}

	/**
	 * @throws UseCaseError
	 * @return mixed
	 */
	public function validateAndDeserialize( UseCaseRequest $request ) {
		return ( $this->mapRequest )( $request );
	}

}
