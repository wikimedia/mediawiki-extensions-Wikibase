<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\SimpleHandler;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\WbRestApi;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRouteHandler extends SimpleHandler {

	/**
	 * @var GetItem
	 */
	private $getItem;

	public function __construct( GetItem $getItem ) {
		$this->getItem = $getItem;
	}

	public static function factory(): Handler {
		return WbRestApi::getRouteHandlerFeatureToggle()->useHandlerIfEnabled(
			new self( WbRestApi::getGetItem() )
		);
	}

	public function run( string $id ): array {
		$result = $this->getItem->execute( new GetItemRequest( $id ) );
		return $result->getItem();
	}

	public function getParamSettings(): array {
		return [
			'id' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

}
