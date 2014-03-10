<?php

namespace Wikibase\Repo\Specials;

use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;

/**
 * Special page for setting the aliases of a Wikibase entity.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetAliases extends SpecialModifyTerm {

	/**
	 * Constructor
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetAliases' );
	}

	/**
	 * @see SpecialSetEntity::getPostedValue()
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'aliases' );
	}

	/**
	 * @see SpecialSetEntity::getValue()
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param string $language
	 *
	 * @return string
	 */
	protected function getValue( $entity, $language ) {
		return $entity === null ? '' : implode( '|', $entity->getAliases( $language ) );
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param string $language
	 * @param string $value
	 *
	 * @return Summary
	 */
	protected function setValue( $entity, $language, $value ) {
		$summary = $this->getSummary( 'wbsetaliases' );
		if ( $value === '' ) {
			$changeOp = new ChangeOpAliases( $language, $entity->getAliases( $language ), 'remove' );
		} else {
			$changeOp = new ChangeOpAliases( $language, explode( '|', $value ), 'set' );
		}
		$changeOp->apply( $entity, $summary );

		return $summary;
	}
}