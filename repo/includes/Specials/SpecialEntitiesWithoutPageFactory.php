<?php

namespace Wikibase\Repo\Specials;

use Wikibase\EntityFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermIndexEntry;

/**
 * Factory to create special pages.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialEntitiesWithoutPageFactory {

	private static function newFromGlobalState() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new self(
			$wikibaseRepo->getStore()->newEntityPerPage(),
			$wikibaseRepo->getEnabledEntityTypes(),
			$wikibaseRepo->getTermsLanguages()
		);
	}

	public static function newSpecialEntitiesWithoutLabel() {
		return self::newFromGlobalState()->createSpecialEntitiesWithoutLabel();
	}

	public static function newSpecialEntitiesWithoutDescription() {
		return self::newFromGlobalState()->createSpecialEntitiesWithoutDescription();
	}

	/**
	 * @var EntityPerPage
	 */
	private $entityPerPage;

	/**
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @param EntityPerPage $entityPerPage
	 * @param string[] $entityTypes
	 * @param ContentLanguages $termsLanguages
	 */
	public function __construct(
		EntityPerPage $entityPerPage,
		array $entityTypes,
		ContentLanguages $termsLanguages
	) {
		$this->entityPerPage = $entityPerPage;
		$this->entityTypes = $entityTypes;
		$this->termsLanguages = $termsLanguages;
	}

	/**
	 * @since 0.5
	 *
	 * @return SpecialEntitiesWithoutPage
	 */
	public function createSpecialEntitiesWithoutLabel() {
		return new SpecialEntitiesWithoutPage(
			'EntitiesWithoutLabel',
			TermIndexEntry::TYPE_LABEL,
			'wikibase-entitieswithoutlabel-legend',
			$this->entityPerPage,
			$this->entityTypes,
			$this->termsLanguages
		);
	}

	/**
	 * @since 0.5
	 *
	 * @return SpecialEntitiesWithoutPage
	 */
	public function createSpecialEntitiesWithoutDescription() {
		return new SpecialEntitiesWithoutPage(
			'EntitiesWithoutDescription',
			TermIndexEntry::TYPE_DESCRIPTION,
			'wikibase-entitieswithoutdescription-legend',
			$this->entityPerPage,
			$this->entityTypes,
			$this->termsLanguages
		);
	}

}
