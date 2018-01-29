<?php

namespace Wikibase\Lib;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;

/**
 * I am the class with a really silly name
 */
class LabelDescriptionEntityPresenter implements EntityPresenter {

	/**
	 * @var LabelDescriptionLookup
	 */
	private $lookup;

	public function __construct( LabelDescriptionLookup $lookup ) {
		$this->lookup = $lookup;
	}

	public function getDisplayLabel( EntityId $id ) {
		return $this->lookup->getLabel( $id )->getText();
	}

	public function getSecondaryLabel( EntityId $id ) {
		return $this->lookup->getDescription( $id )->getText();
	}

}
