<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\Controllers;

use MediaWiki\Status\Status;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Repo\Api\EntitySearchException;
use Wikibase\Repo\Api\PropertyDataTypeSearchHelper;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearch;
use Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch\PropertyPrefixSearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Domain\Model\PropertyPrefixSearchResult;

/**
 * @license GPL-2.0-or-later
 */
class PropertyWbSearchEntitiesController implements WbSearchEntitiesController {

	public function __construct(
		private readonly PropertyPrefixSearch $propertyPrefixSearch,
		private readonly EntitySourceLookup $entitySourceLookup,
	) {
	}

	public function search( WbSearchEntitiesRequest $request ): array {
		try {
			$response = $this->propertyPrefixSearch->execute(
				new PropertyPrefixSearchRequest(
					$request->text,
					$request->searchLanguageCode,
					$request->limit,
					0,
					$request->strictLanguage,
					$request->resultLanguage,
				)
			);
		} catch ( UseCaseError $e ) {
			throw new EntitySearchException( $this->useCaseErrorToStatus( $e ) );
		}

		return array_map( $this->convertResult( ... ), iterator_to_array( $response->results ) );
	}

	private function useCaseErrorToStatus( UseCaseError $e ): Status {
		return match ( $e->getErrorCode() ) {
			UseCaseError::INVALID_QUERY_PARAMETER => Status::newFatal(
				'apierror-badparameter',
				$e->getErrorContext()[UseCaseError::CONTEXT_PARAMETER]
			),
			default => Status::newFatal( 'apierror-unknownerror' ),
		};
	}

	private function convertResult( PropertyPrefixSearchResult $result ): TermSearchResult {
		$matchedData = $result->matchedData;
		$entityId = $result->propertyId;

		$label = $result->label;
		$description = $result->description;
		$conceptUri = $this->entitySourceLookup->getEntitySourceById( $entityId )->getConceptBaseUri()
					  . wfUrlencode( $entityId->getSerialization() );

		return new TermSearchResult(
			new Term( $matchedData->getLanguageCode() ?? 'pid', $matchedData->getText() ),
			$matchedData->getType(),
			$entityId,
			$label ? new Term( $label->getLanguageCode(), $label->getText() ) : null,
			$description ? new Term( $description->getLanguageCode(), $description->getText() ) : null,
			[
				TermSearchResult::CONCEPTURI_META_DATA_KEY => $conceptUri,
				PropertyDataTypeSearchHelper::DATATYPE_META_DATA_KEY => $result->dataType,
			]
		);
	}

}
