<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Reporter\ErrorReporter;
use Psr\Container\ContainerInterface;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearch;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\InLabelSearchEngine;
use Wikibase\Repo\RestApi\Middleware\UnexpectedErrorHandlerMiddleware;

/**
 * @license GPL-2.0-or-later
 */
class WbSearch {

	public static function getErrorReporter( ?ContainerInterface $services = null ): ErrorReporter {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbSearch.ErrorReporter' );
	}

	public static function getUnexpectedErrorHandlerMiddleware( ?ContainerInterface $services = null ): UnexpectedErrorHandlerMiddleware {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbSearch.UnexpectedErrorHandlerMiddleware' );
	}

	public static function getInLabelSearchEngine( ?ContainerInterface $services = null ): InLabelSearchEngine {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbSearch.InLabelSearchEngine' );
	}

	public static function getSimplePropertySearch( ?ContainerInterface $services = null ): SimplePropertySearch {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WbSearch.SimplePropertySearch' );
	}

}
