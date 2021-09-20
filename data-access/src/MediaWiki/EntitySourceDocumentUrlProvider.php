<?php

namespace Wikibase\DataAccess\MediaWiki;

use TitleFactory;
use Wikibase\DataAccess\EntitySourceDefinitions;

/**
 * @license GPL-2.0-or-later
 */
class EntitySourceDocumentUrlProvider {

	/** @var TitleFactory */
	private $titleFactory;

	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
	}

	public function getCanonicalDocumentsUrls( EntitySourceDefinitions $sourceDefinitions ) {
		$documentUrls = [];

		$sources = $sourceDefinitions->getSources();

		foreach ( $sources as $source ) {

			if ( $source->getInterwikiPrefix() === '' ) {
				$entityDataTitle = $this->titleFactory->makeTitle(
					NS_SPECIAL,
					'EntityData',
					'',
					$source->getInterwikiPrefix()
				);
			} else {
				$entityDataTitle = $this->titleFactory->makeTitle(
					NS_MAIN,
					'Special:EntityData',
					'',
					$source->getInterwikiPrefix()
				);
			}
			$documentUrls[$source->getSourceName()] = $entityDataTitle->getCanonicalURL() . '/';
		}

		return $documentUrls;
	}

}
