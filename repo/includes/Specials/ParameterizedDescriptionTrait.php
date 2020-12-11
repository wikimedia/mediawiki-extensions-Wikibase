<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Specials;

use Wikibase\Repo\WikibaseRepo;

/**
 * Trait for parameterized entity descriptions on SpecialPages.
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
trait ParameterizedDescriptionTrait {

	/**
	 * @var array|null
	 */
	private $editableEntities;

	/**
	 * @var string|null
	 */
	private $descriptionParameters;

	/**
	 * @return string
	 * @see SpecialPage::getName()
	 */
	abstract public function getName();

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @return string
	 */
	public function getDescription() {
		return wfMessage(
			'special-' . strtolower( $this->getName() . '-parameterized' ),
			$this->getDescriptionParameters()
		)->text();
	}

	private function getDescriptionParameters() {
		if ( $this->descriptionParameters === null ) {

			if ( $this->editableEntities === null ) {
				$this->setEditableEntities();
			}

			$this->descriptionParameters = implode(
				wfMessage( 'special-parameterized-description-separator' )->text(),
				array_map(
					function( $item ) {
						return wfMessage( 'wikibase-entity-' . $item )->text();
					},
					$this->editableEntities
				)
			);
		}
		return $this->descriptionParameters;
	}

	private function unsetKey( string $type, array &$entities ) {
		$index = array_search( $type, $entities );
		if ( $index !== false ) {
			unset( $entities[$index] );
		}
	}

	protected function setEditableEntities( array $entities = [ 'item', 'property' ] ) {
		$settings = WikibaseRepo::getSettings();

		if ( $settings->getSetting( 'federatedPropertiesEnabled' ) ) {
			$this->unsetKey( 'property', $entities );
		}

		$this->editableEntities = $entities;
	}
}
