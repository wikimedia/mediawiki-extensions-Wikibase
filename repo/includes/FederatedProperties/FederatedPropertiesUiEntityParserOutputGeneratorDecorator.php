<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Language;
use ParserOutput;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;

/**
 * Wraps an EntityParserOutputGenerator and adds Federated Properties UI modules and error handling.
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesUiEntityParserOutputGeneratorDecorator implements EntityParserOutputGenerator {

	/**
	 * @var EntityParserOutputGenerator
	 */
	private $inner;

	/**
	 * @var string|null
	 */
	private $languageCode;

	public function __construct(
		FederatedPropertiesPrefetchingEntityParserOutputGeneratorDecorator $inner,
		Language $language
	) {
		$this->inner = $inner;
		$this->languageCode = $language->getCode();
	}

	/**
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
			$parserOutput = $this->inner->getParserOutput( $entityRevision, $generateHtml );
			$parserOutput->setEnableOOUI( true );
			$parserOutput->addModules( [
				'wikibase.federatedPropertiesEditRequestFailureNotice',
				'wikibase.federatedPropertiesLeavingSiteNotice',
			] );

		} catch ( FederatedPropertiesException $ex ) {
			$entity = $entityRevision->getEntity();

			if ( $entity instanceof LabelsProvider ) {
				$ex = new FederatedPropertiesError(
					$this->languageCode,
					$entity,
					'wikibase-federated-properties-source-wiki-api-error-message'
				);
			}
			throw $ex;
		}

		return $parserOutput;
	}

}
