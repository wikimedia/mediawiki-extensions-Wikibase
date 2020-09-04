<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Language;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;

/**
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesEntityParserOutputGenerator implements EntityParserOutputGenerator {

	/**
	 * @var EntityParserOutputGenerator
	 */
	private $inner;

	/**
	 * @var string|null
	 */
	private $languageCode;

	/**
	 * @var ApiEntityLookup
	 */
	private $apiEntityLookup;

	/**
	 * @param EntityParserOutputGenerator $inner
	 * @param Language $language
	 * @param ApiEntityLookup $apiEntityLookup
	 */
	public function __construct(
		EntityParserOutputGenerator $inner,
		Language $language,
		ApiEntityLookup $apiEntityLookup
	) {
		$this->inner = $inner;
		$this->languageCode = $language->getCode();
		$this->apiEntityLookup = $apiEntityLookup;
	}

	/**
	 * Creates the parser output for the given entity.
	 *
	 * @param EntityRevision $entityRevision
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 * @throws FederatedPropertiesError|FederatedPropertiesException
	 */
	public function getParserOutput(
		EntityRevision $entityRevision,
		$generateHtml = true
	) {
		// add wikibase styles in all cases, so we can format the link properly:
		try {
			$entity = $entityRevision->getEntity();
			$this->prefetchFederatedProperties( $entity );

			$po = $this->inner->getParserOutput( $entityRevision, $generateHtml );
			$po->setEnableOOUI( true );
			$po->addModules( 'wikibase.federatedPropertiesEditRequestFailureNotice' );
			$po->addModules( 'wikibase.federatedPropertiesLeavingSiteNotice' );

		} catch ( FederatedPropertiesException $ex ) {

			if ( $entity instanceof LabelsProvider ) {
				$ex = new FederatedPropertiesError(
					$this->languageCode,
					$entity,
					'wikibase-federated-properties-source-wiki-api-error-message'
				);
			}
			throw $ex;
		}

		return $po;
	}

	private function prefetchFederatedProperties( EntityDocument $entity ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			return;
		}

		$propertyIds = array_map( function( $snak ) {
			return $snak->getPropertyId();
		}, $entity->getStatements()->getAllSnaks() );

		if ( empty( $propertyIds ) ) {
			return;
		}

		$this->apiEntityLookup->fetchEntities( array_unique( $propertyIds ) );
	}

}
