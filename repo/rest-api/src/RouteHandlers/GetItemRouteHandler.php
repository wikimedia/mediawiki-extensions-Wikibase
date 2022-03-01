<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\SimpleHandler;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemRetriever;
use Wikibase\Repo\RestApi\Domain\Serializers\ItemSerializer;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItem;
use Wikibase\Repo\RestApi\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\WikibaseRepo;
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
		$useCase = new GetItem(
			new WikibaseEntityLookupItemRetriever(
				WikibaseRepo::getEntityLookup()
			),
			new ItemSerializer( WikibaseRepo::getBaseDataModelSerializerFactory()->newItemSerializer() )
		);

		// creation of the feature toggle should move to service wiring in the future
		return ( new RouteHandlerFeatureToggle(
			WikibaseRepo::getSettings()->getSetting( 'restApiEnabled' ),
			new ApiNotEnabledRouteHandler()
		) )->useHandlerIfEnabled( new self( $useCase ) );
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
