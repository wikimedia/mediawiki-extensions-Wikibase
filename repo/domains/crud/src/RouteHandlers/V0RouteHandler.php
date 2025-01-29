<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\RouteHandlers;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class V0RouteHandler extends Handler {
	private const ITEM_ID_PATH_PARAM = 'item_id';
	private const PROPERTY_ID_PATH_PARAM = 'property_id';
	private const STATEMENT_ID_PATH_PARAM = 'statement_id';
	private const LANGUAGE_CODE_PATH_PARAM = 'language_code';
	private const SITE_ID_PATH_PARAM = 'site_id';

	public static function factory(): Handler {
		return new self();
	}

	public function execute(): Response {
		return ( new ResponseFactory() )->newErrorResponse(
			UseCaseError::RESOURCE_NOT_FOUND,
			"v0 has been removed, please modify your routes to v1 such as '/rest.php/wikibase/v1'"
		);
	}

	public function getParamSettings(): array {
		return [
			self::ITEM_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			self::PROPERTY_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			self::STATEMENT_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			self::LANGUAGE_CODE_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			self::SITE_ID_PATH_PARAM => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
		];
	}
}
