<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use MediaWiki\Languages\LanguageFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemDescriptionWithFallbackRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemLabelWithFallbackRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyDescriptionWithFallbackRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyLabelWithFallbackRetriever;

/**
 * @license GPL-2.0-or-later
 */
class FallbackLookupFactoryTermsRetriever implements
	ItemLabelWithFallbackRetriever,
	PropertyLabelWithFallbackRetriever,
	ItemDescriptionWithFallbackRetriever,
	PropertyDescriptionWithFallbackRetriever
{

	private FallbackLabelDescriptionLookupFactory $lookupFactory;
	private LanguageFactory $languageFactory;

	public function __construct(
		LanguageFactory $languageFactory,
		FallbackLabelDescriptionLookupFactory $lookupFactory
	) {
		$this->languageFactory = $languageFactory;
		$this->lookupFactory = $lookupFactory;
	}

	public function getLabel( EntityId $entityId, string $languageCode ): ?Label {
		try {
			$labelFallback = $this->lookupFactory
				->newLabelDescriptionLookup( $this->languageFactory->getLanguage( $languageCode ) )
				->getLabel( $entityId );
		} catch ( LabelDescriptionLookupException $e ) {
			// this probably means that the entity does not exist, which should be checked prior to calling this method
			return null;
		}

		return $labelFallback !== null ?
			new Label( $labelFallback->getActualLanguageCode(), $labelFallback->getText() ) :
			null;
	}

	public function getDescription( EntityId $entityId, string $languageCode ): ?Description {
		try {
			$descriptionFallback = $this->lookupFactory
				->newLabelDescriptionLookup( $this->languageFactory->getLanguage( $languageCode ) )
				->getDescription( $entityId );
		} catch ( LabelDescriptionLookupException $e ) {
			// this probably means that the entity does not exist, which should be checked prior to calling this method
			return null;
		}

		return $descriptionFallback !== null ?
			new Description( $descriptionFallback->getActualLanguageCode(), $descriptionFallback->getText() ) :
			null;
	}

}
