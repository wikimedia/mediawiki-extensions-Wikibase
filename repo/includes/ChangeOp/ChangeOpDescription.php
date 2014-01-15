<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;

/**
 * Class for description change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpDescription extends ChangeOpBase {

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
	protected $description;

	/**
	 * @since 0.4
	 *
	 * @param string $language
	 * @param string|null $description
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $language, $description ) {
		if ( !is_string( $language ) ) {
			throw new InvalidArgumentException( '$language needs to be a string' );
		}

		$this->language = $language;
		$this->description = $description;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		if ( $this->description === null ) {
			$this->updateSummary( $summary, 'remove', $this->language, $entity->getDescription( $this->language ) );
			$entity->removeDescription( $this->language );
		} else {
			$entity->getDescription( $this->language ) === false ? $action = 'add' : $action = 'set';
			$this->updateSummary( $summary, $action, $this->language, $this->description );
			$entity->setDescription( $this->language, $this->description );
		}
		return true;
	}
}
