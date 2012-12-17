<?php

namespace Wikibase;

class EntityLinkFinder {

	public function __construct() {

	}

	/**
	 * @param Entity[] $entities
	 *
	 * @return EntityId[]
	 */
	public function findEntityLinks( $entities ) {
		$foundEntities = array();

		foreach ( $entities as $entity ) {
			$foundEntities = array_merge( $foundEntities, $this->findClaimLinks( $entity->getClaims() ) );
		}

		return array_unique( $foundEntities );
	}

	/**
	 * @param Claims $claims
	 *
	 * @return EntityId[]
	 */
	public function findClaimLinks( Claims $claims ) {
		$foundEntities = array();

		/**
		 * @var Claim $claim
		 */
		foreach ( $claims as $claim ) {
			$snaks = iterator_to_array( $claim->getQualifiers() );
			$snaks[] = $claim->getMainSnak();

			$foundEntities = array_merge( $foundEntities, $this->findSnakLinks( $snaks ) );
		}

		return array_unique( $foundEntities );
	}

	/**
	 * @param Snak[] $snaks
	 *
	 * @return EntityId[]
	 */
	protected function findSnakLinks( array $snaks ) {
		$foundEntities = array();

		foreach ( $snaks as $snak ) {
			$foundEntities[] = $snak->getPropertyId();

			// TODO: snak.datatype == wikibase-item
		}

		return $foundEntities;
	}

}
