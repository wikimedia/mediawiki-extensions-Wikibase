<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use Language;
use ParserOutput;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;

/**
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesEntityParserOutputGenerator implements EntityParserOutputGenerator {

	private $inner;
	private $languageCode;

	/**
	 * @param EntityParserOutputGenerator $inner
	 * @param Language $language
	 */
	public function __construct(
		EntityParserOutputGenerator $inner,
		Language $language

	) {
		$this->inner = $inner;
		$this->languageCode = $language->getCode();
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
			$po = $this->inner->getParserOutput( $entityRevision, $generateHtml );
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

		return $po;
	}

}
