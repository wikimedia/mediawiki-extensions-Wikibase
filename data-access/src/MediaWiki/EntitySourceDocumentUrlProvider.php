<?php

namespace Wikibase\DataAccess\MediaWiki;

use MediaWiki\Title\TitleFactory;
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

	public function getCanonicalDocumentsUrls( EntitySourceDefinitions $sourceDefinitions ): array {
		$documentUrls = [];

		$sources = $sourceDefinitions->getSources();

		foreach ( $sources as $source ) {
			// NOTE: Force the unlocalized title 'Special:EntityData' even for
			//       local pages (T263427).
			$entityDataTitle = $this->titleFactory->makeTitle(
				NS_MAIN,
				'Special:EntityData',
				'',
				$source->getInterwikiPrefix()
			);

			$documentUrls[$source->getSourceName()] = $entityDataTitle->getCanonicalURL() . '/';
		}

		return $documentUrls;
	}

}
