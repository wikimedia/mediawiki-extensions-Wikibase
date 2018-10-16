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

	private static function newFromGlobalState(): self {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new self(
			$wikibaseRepo->getStore()->newEntitiesWithoutTermFinder(),
			$wikibaseRepo->getLocalEntityTypes(),
			$wikibaseRepo->getTermsLanguages(),
			new LanguageNameLookup(),
			$wikibaseRepo->getEntityFactory()
		);
	}

	public static function newSpecialEntitiesWithoutLabel(): SpecialEntitiesWithoutPage {
		return self::newFromGlobalState()->createSpecialEntitiesWithoutLabel();
	}

	public static function newSpecialEntitiesWithoutDescription(): SpecialEntitiesWithoutPage {
		return self::newFromGlobalState()->createSpecialEntitiesWithoutDescription();
	}

	private $entitiesWithoutTerm;
	private $entityTypes;
	private $termsLanguages;
	private $languageNameLookup;
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

	public function createSpecialEntitiesWithoutLabel(): SpecialEntitiesWithoutPage {
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

	public function createSpecialEntitiesWithoutDescription(): SpecialEntitiesWithoutPage {
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
			$supportedEntityTypes,
			$this->termsLanguages,
			$this->languageNameLookup
		);
	}

}
