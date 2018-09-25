<?php

namespace Wikibase\Repo\Specials;

use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\EntityFactory;
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
			new LanguageNameLookup(),
			$wikibaseRepo->getEntityFactory()
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
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @param EntitiesWithoutTermFinder $entitiesWithoutTerm
	 * @param string[] $entityTypes
	 * @param ContentLanguages $termsLanguages
	 * @param LanguageNameLookup $languageNameLookup
	 * @param EntityFactory $entityFactory
	 */
	public function __construct(
		EntitiesWithoutTermFinder $entitiesWithoutTerm,
		array $entityTypes,
		ContentLanguages $termsLanguages,
		LanguageNameLookup $languageNameLookup,
		EntityFactory $entityFactory
	) {
		$this->entitiesWithoutTerm = $entitiesWithoutTerm;
		$this->entityTypes = $entityTypes;
		$this->termsLanguages = $termsLanguages;
		$this->languageNameLookup = $languageNameLookup;
		$this->entityFactory = $entityFactory;
	}

	/**
	 * @return SpecialEntitiesWithoutPage
	 */
	public function createSpecialEntitiesWithoutLabel() {
		$supportedEntityTypes = [];
		foreach ( $this->entityTypes as $entityType ) {
			if ( $this->entityFactory->newEmpty( $entityType ) instanceof LabelsProvider ) {
				$supportedEntityTypes[] = $entityType;
			}
		}
		return new SpecialEntitiesWithoutPage(
			'EntitiesWithoutLabel',
			TermIndexEntry::TYPE_LABEL,
			'wikibase-entitieswithoutlabel-legend',
			$this->entitiesWithoutTerm,
			$supportedEntityTypes,
			$this->termsLanguages,
			$this->languageNameLookup
		);
	}

	/**
	 * @return SpecialEntitiesWithoutPage
	 */
	public function createSpecialEntitiesWithoutDescription() {
		$supportedEntityTypes = [];
		foreach ( $this->entityTypes as $entityType ) {
			if ( $this->entityFactory->newEmpty( $entityType ) instanceof DescriptionsProvider ) {
				$supportedEntityTypes[] = $entityType;
			}
		}
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
