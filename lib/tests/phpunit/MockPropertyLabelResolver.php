<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\PropertyLabelResolver;

/**
 * Mock resolver, based on a MockRepository
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockPropertyLabelResolver implements PropertyLabelResolver {

	protected $repo;

	protected $lang;

	/**
	 * @param string  $lang
	 * @param MockRepository $repo
	 */
	public function __construct( $lang, MockRepository $repo ) {
		$this->lang = $lang;
		$this->repo = $repo;
	}

	/**
	 * @param string[] $labels  the labels
	 * @param string   $recache ignored
	 *
	 * @return EntityId[] a map of strings from $labels to the corresponding entity ID.
	 */
	public function getPropertyIdsForLabels( array $labels, $recache = '' ) {
		$ids = array();

		foreach ( $labels as $label ) {
			$prop = $this->repo->getPropertyByLabel( $label, $this->lang );

			if ( $prop !== null ) {
				$ids[$label] = $prop->getId();
			}
		}

		return $ids;
	}

}
