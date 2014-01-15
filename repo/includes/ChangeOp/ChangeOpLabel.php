<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;

/**
 * Class for label change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpLabel extends ChangeOpBase {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * @since 0.4
	 *
	 * @var string|null
	 */
	protected $label;

	/**
	 * @since 0.4
	 *
	 * @param string $language
	 * @param string|null $label
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $language, $label ) {
		if ( !is_string( $language ) ) {
			throw new InvalidArgumentException( '$language needs to be a string' );
		}

		$this->language = $language;
		$this->label = $label;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		if ( $this->label === null ) {
			$this->updateSummary( $summary, 'remove', $this->language, $entity->getLabel( $this->language ) );
			$entity->removeLabel( $this->language );
		} else {
			$entity->getLabel( $this->language ) === false ? $action = 'add' : $action = 'set';
			$this->updateSummary( $summary, $action, $this->language, $this->label );
			$entity->setLabel( $this->language, $this->label );
		}
		return true;
	}
}
