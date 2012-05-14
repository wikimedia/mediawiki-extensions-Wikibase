<?php

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

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : dirname( __FILE__ ) . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class WikibasePollForChanges extends Maintenance {

	/**
	 * @var ORMTable
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
		$this->changes = WikibaseChanges::singleton();

		$this->lastChangeId = (int)$this->getArg( 'startid', 0 );
		$this->startTime = (int)$this->getArg( 'starttime', 0 );
		$this->pollLimit = (int)$this->getArg( 'polllimit', WBLSettings::get( 'pollDefaultLimit' ) );
		$this->sleepInterval = (int)$this->getArg( 'sleepinterval', WBLSettings::get( 'pollDefaultInterval' ) );
		$this->continueInterval = (int)$this->getArg( 'continueinterval', WBLSettings::get( 'pollContinueInterval' ) );

		$this->doPoll();
	}

	/**
	 * Do a poll operation, finding all new changes.
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

		if ( $changes->count() !== 0 ) {
			wfRunHooks( 'WikibasePollBeforeHandle', array( $changes ) );

			foreach ( $changes as /* WikibaseChange */ $change ) {
				wfRunHooks( 'WikibasePollHandle', array( $change ) );
			}

			$this->lastChangeId = $change->getId();

			wfRunHooks( 'WikibasePollAfterHandle', array( $changes ) );
		}

		sleep( $changes->count() === $this->pollLimit ? $this->continueInterval : $this->sleepInterval );

		$this->doPoll();
	}

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

}

$maintClass = 'WikibasePollForChanges';
require_once( RUN_MAINTENANCE_IF_MAIN );
