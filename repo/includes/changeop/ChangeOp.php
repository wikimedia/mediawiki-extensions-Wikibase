<?php

namespace Wikibase;

use InvalidArgumentException;

/**
 * Base class for change operations.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
abstract class ChangeOp {

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @return bool
	 *
	 * @throws ChangeOpException
	 */
	abstract public function apply( Entity $entity, Summary $summary = null );

	/**
	 * @since 0.4
	 *
	 * @param Summary $summary
	 * @param string $action
	 * @param string $language
	 * @param string|array $args
	 *
	 * @throws InvalidArgumentException
	 */
	protected function updateSummary( $summary, $action, $language = '', $args = '' ) {
		if ( $summary !== null && !$summary instanceof Summary ) {
			throw new InvalidArgumentException( '$summary needs to be an instance of Summary or null' );
		}

		if ( $summary !== null ) {
			$summary->setAction( $action );
			$summary->setLanguage( $language );
			$summary->addAutoSummaryArgs( $args );
		}
	}

}
