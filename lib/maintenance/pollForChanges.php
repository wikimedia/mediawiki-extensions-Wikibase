<?php

namespace Wikibase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : dirname( __FILE__ ) . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script that polls for Wikibase changes in the shared wb_changes table
 * and triggers a hook to invoke the code that needs to handle these changes.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PollForChanges extends \Maintenance {

	/**
	 * @var Changes
	 */
	protected $changes;

	/**
	 * @var integer
	 */
	protected $lastChangeId;

	/**
	 * @var integer
	 */
	protected $pollLimit;

	/**
	 * @var integer
	 */
	protected $startTime;

	/**
	 * @var integer
	 */
	protected $sleepInterval;

	/**
	 * @var integer
	 */
	protected $continueInterval;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->mDescription =
			'Maintenance script that polls for Wikibase changes in the shared wb_changes table
			and triggers a hook to invoke the code that needs to handle these changes.';

		parent::__construct();
	}

	/**
	 * Maintenance script entry point.
	 */
	public function execute() {
		$this->changes = Changes::singleton();

		$this->lastChangeId = (int)$this->getArg( 'startid', 0 );
		$this->startTime = (int)$this->getArg( 'starttime', 0 );
		$this->pollLimit = (int)$this->getArg( 'polllimit', \WBLSettings::get( 'pollDefaultLimit' ) );
		$this->sleepInterval = (int)$this->getArg( 'sleepinterval', \WBLSettings::get( 'pollDefaultInterval' ) );
		$this->continueInterval = (int)$this->getArg( 'continueinterval', \WBLSettings::get( 'pollContinueInterval' ) );

		while ( true ) {
			usleep( $this->doPoll() * 1000 );
		}
	}

	/**
	 * Do a poll operation, finding all new changes.
	 *
	 * @return integer The amount of milliseconds the script should sleep before doing the next poll.
	 */
	protected function doPoll() {
		$changes = $this->changes->select(
			null,
			$this->getContinuationConds(),
			array(
				'LIMIT' => $this->pollLimit,
				'ORDER BY ' . $this->changes->getPrefixedField( 'id' ) . ' ASC'
			),
			__METHOD__
		);

		if ( $changes->count() === 0 ) {
			$this->msg( 'No new changes where found' );
		}
		else {
			$this->msg( $changes->count() . ' new changes where found' );

			wfRunHooks( 'WikibasePollBeforeHandle', array( $changes ) );

			foreach ( $changes as /* WikibaseChange */ $change ) {
				$this->msg( 'Handling change with id ' . $change->getId() );
				wfRunHooks( 'WikibasePollHandle', array( $change ) );
			}

			$this->lastChangeId = $change->getId();

			wfRunHooks( 'WikibasePollAfterHandle', array( $changes ) );
		}

		return $changes->count() === $this->pollLimit ? $this->continueInterval : $this->sleepInterval;
	}

	/**
	 * @return array
	 */
	protected function getContinuationConds() {
		$conds = array();

		if ( $this->lastChangeId === 0 && $this->startTime !== 0 ) {
			$conds[] = 'time < ' . wfGetDB( DB_SLAVE )->addQuotes( wfTimestamp( TS_MW, $this->startTime ) );
		}

		if ( $this->lastChangeId !== false ) {
			$conds[] = 'id > ' . $this->lastChangeId;
		}

		return $conds;
	}

	/**
	 * Handle a message (ie display and logging)
	 *
	 * @param string $message
	 */
	protected function msg( $message ) {
		echo date( 'H:i:s' ) . ' ' . $message . "\n";
	}

}

$maintClass = 'Wikibase\PollForChanges';
require_once( RUN_MAINTENANCE_IF_MAIN );
