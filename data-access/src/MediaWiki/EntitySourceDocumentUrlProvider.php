<?php

namespace Wikibase\DataAccess\MediaWiki;

use Title;
use Wikibase\DataAccess\EntitySourceDefinitions;

/**
 * @license GPL-2.0-or-later
 */
class EntitySourceDocumentUrlProvider {

	public function getCanonicalDocumentsUrls( EntitySourceDefinitions $sourceDefinitions ) {
		$documentUrls = [];

		$sources = $sourceDefinitions->getSources();

		foreach ( $sources as $source ) {
			$entityDataTitle = Title::makeTitle( NS_SPECIAL, 'EntityData', '', $source->getInterwikiPrefix() );
			$documentUrls[$source->getSourceName()] = $entityDataTitle->getCanonicalURL() . '/';
		}

		return $documentUrls;
	}

}
