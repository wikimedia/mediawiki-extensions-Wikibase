<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemDescriptionRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemLabelRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyDescriptionRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyDescriptionsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyLabelRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class TermLookupEntityTermsRetriever implements
	ItemLabelRetriever,
	ItemLabelsRetriever,
	ItemDescriptionRetriever,
	ItemDescriptionsRetriever,
	PropertyLabelRetriever,
	PropertyLabelsRetriever,
	PropertyDescriptionRetriever,
	PropertyDescriptionsRetriever
{

	private TermLookup $termLookup;
	private ContentLanguages $termLanguages;

	public function __construct( TermLookup $termLookup, ContentLanguages $termLanguages ) {
		$this->termLookup = $termLookup;
		$this->termLanguages = $termLanguages;
	}

	public function getLabel( EntityId $entityId, string $languageCode ): ?Label {
		try {
			$labelText = $this->termLookup->getLabel( $entityId, $languageCode );
		} catch ( TermLookupException ) {
			// this probably means that the entity does not exist, which should be checked prior to calling this method
			return null;
		}

		return $labelText !== null ? new Label( $languageCode, $labelText ) : null;
	}

	public function getLabels( EntityId $entityId ): ?Labels {
		try {
			$labels = $this->termLookup->getLabels( $entityId, $this->termLanguages->getLanguages() );
		} catch ( TermLookupException ) {
			// this probably means that the entity does not exist, which should be checked prior to calling this method
			return null;
		}

		return new Labels( ...array_map(
			fn( $text, $language ) => new Label( $language, $text ),
			$labels,
			array_keys( $labels )
		) );
	}

	public function getDescription( EntityId $entityId, string $languageCode ): ?Description {
		try {
			$descriptionText = $this->termLookup->getDescription( $entityId, $languageCode );
		} catch ( TermLookupException ) {
			// this probably means that the entity does not exist, which should be checked prior to calling this method
			return null;
		}
		return $descriptionText !== null ? new Description( $languageCode, $descriptionText ) : null;
	}

	public function getDescriptions( EntityId $entityId ): ?Descriptions {
		try {
			$descriptions = $this->termLookup->getDescriptions( $entityId, $this->termLanguages->getLanguages() );
		} catch ( TermLookupException ) {
			// this probably means that the entity does not exist, which should be checked prior to calling this method
			return null;
		}

		return new Descriptions( ...array_map(
			fn( $text, $language ) => new Description( $language, $text ),
			$descriptions,
			array_keys( $descriptions )
		) );
	}

}
