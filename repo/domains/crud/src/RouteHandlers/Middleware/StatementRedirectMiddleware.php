<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers\Middleware;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\StatementSubjectRetriever;

/**
 * @license GPL-2.0-or-later
 */
class StatementRedirectMiddleware implements Middleware {

	private StatementSubjectRetriever $statementSubjectRetriever;
	private EntityIdParser $entityIdParser;
	private string $statementIdPathParameter;
	private ?string $subjectIdPathParameter;

	public function __construct(
		EntityIdParser $entityIdParser,
		StatementSubjectRetriever $statementSubjectRetriever,
		string $statementIdPathParameter,
		?string $subjectIdPathParameter
	) {
		$this->statementSubjectRetriever = $statementSubjectRetriever;
		$this->entityIdParser = $entityIdParser;
		$this->statementIdPathParameter = $statementIdPathParameter;
		$this->subjectIdPathParameter = $subjectIdPathParameter;
	}

	public function run( Handler $routeHandler, callable $runNext ): Response {
		$response = $runNext();

		if ( !$this->isStatementNotFoundResponse( $response ) ) {
			return $response;
		}

		$requestedSubjectId = $this->subjectIdPathParameter === null ? null :
			$routeHandler->getRequest()->getPathParam( $this->subjectIdPathParameter );
		$requestedStatementId = $routeHandler->getRequest()->getPathParam( $this->statementIdPathParameter );
		$statementSubject = $this->getStatementSubject( $requestedSubjectId, $requestedStatementId );

		if ( $statementSubject === null ) {
			return $response;
		}

		foreach ( $statementSubject->getStatements() as $statement ) {
			$statementId = $statement->getGuid();
			if ( strtolower( $statementId ) === strtolower( $requestedStatementId ) ) {
				$response = $routeHandler->getResponseFactory()->create();
				$response->setStatus( 308 );
				$response->setHeader(
					'Location',
					str_replace( $requestedStatementId, $statementId, (string)$routeHandler->getRequest()->getUri() )
				);
				return $response;
			}

		}

		return $response;
	}

	private function isStatementNotFoundResponse( Response $response ): bool {
		$responseBody = json_decode( (string)$response->getBody(), true );
		return $response->getStatusCode() === 404 &&
			is_array( $responseBody ) &&
			$responseBody['code'] === UseCaseError::RESOURCE_NOT_FOUND &&
			$responseBody['context']['resource_type'] === 'statement';
	}

	private function getStatementSubject( ?string $subjectId, string $statementId ): ?StatementListProvidingEntity {
		$statementIdPrefix = substr( $statementId, 0, strpos( $statementId, '$' ) ?: 0 );

		// mismatch between requested statement subject and requested statement ID
		if ( $subjectId && strtolower( $subjectId ) !== strtolower( $statementIdPrefix ) ) {
			return null;
		}

		return $this->statementSubjectRetriever->getStatementSubject(
			$this->entityIdParser->parse( $statementIdPrefix )
		);
	}

}
