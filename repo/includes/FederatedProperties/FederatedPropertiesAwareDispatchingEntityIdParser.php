<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataAccess\ApiEntitySource;
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
			$entitySource = $this->getEntitySourceForConceptURI( $idSerialization );
			if ( $entitySource === null ) {
				throw new EntityIdParsingException( 'No entity source configured for this base URI for id ' . $idSerialization );
			}

			return new FederatedPropertyId(
				$idSerialization,
				$this->getSerializationWithoutConceptBaseURI( $idSerialization, $entitySource )
			);
		}

		return $this->parser->parse( $idSerialization );
	}

	private function looksLikeURI( $idSerialization ): bool {
		return ( filter_var( $idSerialization, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED ) !== false );
	}

	private function getEntitySourceForConceptURI( $idSerialization ): ?ApiEntitySource {
		$baseUri = $this->baseUriExtractor->getBaseUriFromSerialization( $idSerialization );
		$conceptBaseURIsToSources = array_flip( $this->entitySourceDefinitions->getConceptBaseUris() );

		if ( array_key_exists( $baseUri, $conceptBaseURIsToSources ) ) {
			$sources = $this->entitySourceDefinitions->getSources();
			foreach ( $sources as $source ) {
				if ( $source->getSourceName() === $conceptBaseURIsToSources[ $baseUri ] ) {
					return $source->getType() === ApiEntitySource::TYPE ? $source : null;
				}
			}
		}

		return null;
	}

	private function getSerializationWithoutConceptBaseURI( string $idSerialization, ApiEntitySource $entitySource ) {
		return substr( $idSerialization, strlen( $entitySource->getConceptBaseUri() ) );
	}

}
