<?php

namespace Wikibase\Repo\SeaHorse;

use \Wikibase\Repo\Content\EntityHolder;
use \Wikibase\Repo\Content\EntityContent;

class SeaHorseContent extends EntityContent {

	private $holder;

	public function __construct(
		EntityHolder $holder
	) {
		parent::__construct( SeaHorseSaddle::CONTENT_ID);
		$this->holder = $holder;
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @throws MWException when it's a redirect (targets will never be resolved)
	 * @throws LogicException if the content object is empty and does not contain an entity.
	 * @return Item
	 */
	public function getEntity() {
		return $this->holder->getEntity();
	}

	/**
	 * @see EntityContent::getEntityHolder
	 *
	 * @return EntityHolder|null
	 */
	public function getEntityHolder() {
		return $this->holder;
	}

	/**
	 * @inheritDoc
	 */
	public function getTextForSearchIndex() {
		return $this->holder->getEntity()->getContent();
	}

	/**
	 * @see EntityContent::isEmpty
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return ( !$this->holder || $this->getEntity()->isEmpty() );
	}

	public function getIgnoreKeysForFilters() {
		return ['id', 'type'];
	}
}
