<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\SimpleHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRouteHandler extends SimpleHandler {

	public static function factory(): Handler {
		// creation of the feature toggle should move to service wiring in the future
		return ( new RouteHandlerFeatureToggle(
			WikibaseRepo::getSettings()->getSetting( 'restApiEnabled' ),
			new ApiNotEnabledRouteHandler()
		) )->useHandlerIfEnabled( new self() );
	}

	public function run(): array {
		return [ 'hello' => 'world' ];
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
