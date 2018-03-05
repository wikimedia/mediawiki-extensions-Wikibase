<?php

namespace Wikibase\Lib\Tests;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;

/**
 * Mock resolver, based on a MockRepository
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MockPropertyLabelResolver implements PropertyLabelResolver {

	/**
	 * @var MockRepository
	 */
	private $mockRepository;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param string $languageCode
	 * @param MockRepository $mockRepository
	 */
	public function __construct( $languageCode, MockRepository $mockRepository ) {
		$this->languageCode = $languageCode;
		$this->mockRepository = $mockRepository;
	}

	/**
	 * @param string[] $labels
	 * @param string   $recache ignored
	 *
	 * @return EntityId[] a map of strings from $labels to the corresponding entity ID.
	 */
	public function getPropertyIdsForLabels( array $labels, $recache = '' ) {
		$entityIds = [];

		foreach ( $labels as $label ) {
			$entity = $this->mockRepository->getPropertyByLabel( $label, $this->languageCode );

			if ( $entity !== null ) {
				$entityIds[$label] = $entity->getId();
			}
		}

		return $entityIds;
	}

}
