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

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var MockRepository
	 */
	private $mockRepository;

	/**
	 * @param string  $languageCode
	 * @param MockRepository $mockRepository
	 */
	public function __construct( $languageCode, MockRepository $mockRepository ) {
		$this->languageCode = $languageCode;
		$this->mockRepository = $mockRepository;
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
			$prop = $this->mockRepository->getPropertyByLabel( $label, $this->languageCode );

			if ( $prop !== null ) {
				$ids[$label] = $prop->getId();
			}
		}

		return $ids;
	}

}
