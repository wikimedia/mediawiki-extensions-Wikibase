<?php

namespace Wikibase\Repo\Specials;

use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\Store\EntitiesWithoutTermFinder;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermIndexEntry;

/**
 * Factory to create special pages.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialEntitiesWithoutPageFactory {

	private static function newFromGlobalState() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$entityTypes = $wikibaseRepo->getSettings()->getSetting(
			'supportedEntityTypesForEntitiesWithoutTermListings'
		);

		if ( $entityTypes === null ) {
			$entityTypes = $wikibaseRepo->getLocalEntityTypes();
		}

		return new self(
			$wikibaseRepo->getStore()->newEntitiesWithoutTermFinder(),
			$entityTypes,
			$wikibaseRepo->getTermsLanguages(),
			new LanguageNameLookup()
		);
	}

	public static function newSpecialEntitiesWithoutLabel() {
		return self::newFromGlobalState()->createSpecialEntitiesWithoutLabel();
	}

	public static function newSpecialEntitiesWithoutDescription() {
		return self::newFromGlobalState()->createSpecialEntitiesWithoutDescription();
	}

	/**
	 * @var EntitiesWithoutTermFinder
	 */
	private $entitiesWithoutTerm;

	/**
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param EntitiesWithoutTermFinder $entitiesWithoutTerm
	 * @param string[] $entityTypes
	 * @param ContentLanguages $termsLanguages
	 * @param LanguageNameLookup $languageNameLookup
	 */
	public function __construct(
		EntitiesWithoutTermFinder $entitiesWithoutTerm,
		array $entityTypes,
		ContentLanguages $termsLanguages,
		LanguageNameLookup $languageNameLookup
	) {
		$this->entitiesWithoutTerm = $entitiesWithoutTerm;
		$this->entityTypes = $entityTypes;
		$this->termsLanguages = $termsLanguages;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * @return SpecialEntitiesWithoutPage
	 */
	public function createSpecialEntitiesWithoutLabel() {
		return new SpecialEntitiesWithoutPage(
			'EntitiesWithoutLabel',
			TermIndexEntry::TYPE_LABEL,
			'wikibase-entitieswithoutlabel-legend',
			$this->entitiesWithoutTerm,
			$this->entityTypes,
			$this->termsLanguages,
			$this->languageNameLookup
		);
	}

	/**
	 * @return SpecialEntitiesWithoutPage
	 */
	public function createSpecialEntitiesWithoutDescription() {
		return new SpecialEntitiesWithoutPage(
			'EntitiesWithoutDescription',
			TermIndexEntry::TYPE_DESCRIPTION,
			'wikibase-entitieswithoutdescription-legend',
			$this->entitiesWithoutTerm,
			$this->entityTypes,
			$this->termsLanguages,
			$this->languageNameLookup
		);
	}

}
