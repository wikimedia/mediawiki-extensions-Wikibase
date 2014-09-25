<?php

namespace Wikibase\Repo\Specials;

use Wikibase\EntityFactory;
use Wikibase\EntityPerPage;
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
	 * @var EntityPerPage
	 */
	private $entityPerPage;

	/**
	 * @var EntityFactory
	 */
	private $entityFactory;

	public function __construct( EntityPerPage $entityPerPage, EntityFactory $entityFactory ) {
		$this->entityPerPage = $entityPerPage;
		$this->entityFactory = $entityFactory;
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
			$this->entityFactory
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
			$this->entityFactory
		);
	}

}
