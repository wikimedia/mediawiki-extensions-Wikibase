<?php

namespace Wikibase\Repo\Specials;

use Wikibase\EntityFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Term;

/**
 * Factory to create special pages.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialEntitiesWithoutPageFactory {

	private static function newFromGlobalState() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new self(
			$wikibaseRepo->getStore()->newEntityPerPage(),
			$wikibaseRepo->getEntityFactory(),
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
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	public function __construct(
		EntityPerPage $entityPerPage,
		EntityFactory $entityFactory,
		ContentLanguages $termsLanguages
	) {
		$this->entityPerPage = $entityPerPage;
		$this->entityFactory = $entityFactory;
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
			Term::TYPE_LABEL,
			'wikibase-entitieswithoutlabel-legend',
			$this->entityPerPage,
			$this->entityFactory,
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
			Term::TYPE_DESCRIPTION,
			'wikibase-entitieswithoutdescription-legend',
			$this->entityPerPage,
			$this->entityFactory,
			$this->termsLanguages
		);
	}

}
