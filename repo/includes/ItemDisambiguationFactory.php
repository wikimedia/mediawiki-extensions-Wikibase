<?php

declare( strict_types = 1 );

namespace Wikibase\Repo;

use Language;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Factory service to create {@link ItemDisambiguation} instances for a particular language.
 *
 * @license GPL-2.0-or-later
 */
class ItemDisambiguationFactory {

	private EntityTitleLookup $entityTitleLookup;
	private LanguageNameLookupFactory $languageNameLookupFactory;

	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		LanguageNameLookupFactory $languageNameLookupFactory
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->languageNameLookupFactory = $languageNameLookupFactory;
	}

	public function getForLanguage( Language $language ): ItemDisambiguation {
		return new ItemDisambiguation(
			$this->entityTitleLookup,
			$this->languageNameLookupFactory->getForLanguage( $language )
		);
	}

}
