<?php

namespace Wikibase\Repo\Specials;

use Wikibase\ChangeOpAliases;
use Wikibase\Summary;

/**
 * Special page for setting the aliases of a Wikibase entity.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */

class SpecialSetAliases extends SpecialSetEntity {

	/**
	 * Constructor
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetAliases', 'alias-update' );
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
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $language
	 *
	 * @return string
	 */
	protected function getValue( $entityContent, $language ) {
		return $entityContent === null ? '' : implode( '|', $entityContent->getEntity()->getAliases( $language ) );
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 *
	 * @since 0.4
	 *
	 * @param \Wikibase\EntityContent $entityContent
	 * @param string $language
	 * @param string $value
	 *
	 * @return Summary
	 */
	protected function setValue( $entityContent, $language, $value ) {
		$summary = $this->getSummary( 'wbsetaliases' );
		$entity = $entityContent->getEntity();
		if ( $value === '' ) {
			$changeOp = new ChangeOpAliases( $language, $entity->getAliases( $language ), 'remove' );
		} else {
			$changeOp = new ChangeOpAliases( $language, explode( '|', $value ), 'set' );
		}
		$changeOp->apply( $entity, $summary );

		return $summary;
	}
}