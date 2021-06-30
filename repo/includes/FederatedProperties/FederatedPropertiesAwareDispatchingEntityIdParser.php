<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;

/**
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesAwareDispatchingEntityIdParser implements EntityIdParser {

	/**
	 * @var DispatchingEntityIdParser
	 */
	private $parser;

	private $baseUriExtractor;

	/**
	 * @var EntitySourceDefinitions
	 */
	private $entitySourceDefinitions;

	/**
	 * @param DispatchingEntityIdParser $parser
	 * @param BaseUriExtractor $baseUriExtractor
	 * @param EntitySourceDefinitions $entitySourceDefinitions
	 */
	public function __construct(
		DispatchingEntityIdParser $parser,
		BaseUriExtractor $baseUriExtractor,
		EntitySourceDefinitions $entitySourceDefinitions
	) {
		$this->parser = $parser;
		$this->baseUriExtractor = $baseUriExtractor;
		$this->entitySourceDefinitions = $entitySourceDefinitions;
	}

	/**
	 * @param string $idSerialization
	 *
	 * @throws EntityIdParsingException
	 * @return EntityId
	 */
	public function parse( $idSerialization ): EntityId {
		if ( $this->looksLikeURI( $idSerialization ) ) {
			if ( !$this->checkIfBaseURIisDefinedApiEntitySource( $idSerialization ) ) {
				throw new EntityIdParsingException( 'No entity source configured for this base URI for id ' . $idSerialization );
			} else {
				return new FederatedPropertyId( $idSerialization );
			}
		} else {
			return $this->parser->parse( $idSerialization );
		}
	}

	private function looksLikeURI( $idSerialization ): bool {
		return ( filter_var( $idSerialization, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED ) !== false );
	}

	private function checkIfBaseURIisDefinedApiEntitySource( $idSerialization ): bool {
		$baseUri = $this->baseUriExtractor->getBaseUriFromSerialization( $idSerialization );
		$conceptBaseURIsToSources = array_flip( $this->entitySourceDefinitions->getConceptBaseUris() );

		if ( array_key_exists( $baseUri, $conceptBaseURIsToSources ) ) {
			$sources = $this->entitySourceDefinitions->getSources();
			foreach ( $sources as $source ) {
				if ( $source->getSourceName() === $conceptBaseURIsToSources[ $baseUri ] ) {
					return $source->getType() === EntitySource::TYPE_API;
				}
			}
		}
		return false;
	}

}
