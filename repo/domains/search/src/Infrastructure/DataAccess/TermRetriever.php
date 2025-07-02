<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use MediaWiki\Languages\LanguageFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;

/**
 * @license GPL-2.0-or-later
 */
class TermRetriever {

	private FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory;
	private LanguageFactory $languageFactory;

	public function __construct( FallbackLabelDescriptionLookupFactory $lookupFactory, LanguageFactory $languageFactory ) {
		$this->labelDescriptionLookupFactory = $lookupFactory;
		$this->languageFactory = $languageFactory;
	}

	public function getLabel( EntityId $entityId, string $languageCode ): ?Label {
		try {
			$label = $this->labelDescriptionLookupFactory
				->newLabelDescriptionLookup( $this->languageFactory->getLanguage( $languageCode ) )
				->getLabel( $entityId );
		} catch ( TermLookupException ) {
			// this probably means that the entity does not exist
			return null;
		}

		return $label !== null ? new Label( $label->getActualLanguageCode(), $label->getText() ) : null;
	}

	public function getDescription( EntityId $entityId, string $languageCode ): ?Description {
		try {
			$description = $this->labelDescriptionLookupFactory
				->newLabelDescriptionLookup( $this->languageFactory->getLanguage( $languageCode ) )
				->getDescription( $entityId );
		} catch ( TermLookupException ) {
			// this probably means that the entity does not exist
			return null;
		}

		return $description !== null ? new Description( $description->getActualLanguageCode(), $description->getText() ) : null;
	}

}
