<?php

namespace Wikibase;

use Wikibase\Client\WikibaseClient;

/**
 * Job for notifying a client wiki of a batch of changes on the repository.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeNotificationJob extends \Job {

	/**
	 * @var Change[] $changes: initialized lazily by getChanges().
	 */
	private $changes = null;

	/**
	 * @var ChangeHandler|null
	 */
	private $changeHandler = null;

	/**
	 * Creates a ChangeNotificationJob representing the given changes.
	 *
	 * @param array       $changes The list of changes to be processed
	 * @param string      $repo The name of the repository the changes come from (default: "").
	 * @param array|bool  $params extra job parameters, see Job::__construct (default: false).
	 *
	 * @return \Wikibase\ChangeNotificationJob: the job
	 */
	public static function newFromChanges( array $changes, $repo = '', $params = false ) {
		static $dummyTitle = null;
		wfProfileIn( __METHOD__ );

		// Note: we don't really care about the title and will use a dummy
		if ( $dummyTitle === null ) {
			// The Job class wants a Title object for some reason. Supply a dummy.
			$dummyTitle = \Title::newFromText( "ChangeNotificationJob", NS_SPECIAL );
		}

		// get the list of change IDs
		$changeIds = array_map(
			function ( Change $change ) {
				return $change->getId();
			},
			$changes
		);

		if ( $params === false ) {
			$params = array();
		}

		$params['repo'] = $repo;
		$params['changeIds'] = $changeIds;

		$job = new ChangeNotificationJob( $dummyTitle, $params );

		wfProfileOut( __METHOD__ );
		return $job;
	}

	/**
	 * Constructs a ChangeNotificationJob representing the changes given by $changeIds.
	 *
	 * @note: This is for use by Job::factory, don't call it directly;
	 *           use newFromChanges() instead.
	 *
	 * @note: the constructor's signature is dictated by Job::factory, so we'll have to
	 *           live with it even though it's rather ugly for our use case.
	 *
	 * @see      Job::factory.
	 *
	 * @param \Title $title ignored
	 * @param  $params array|bool
	 * @param  $id     int
	 */
	public function __construct( \Title $title, $params = false, $id = 0 ) {
		parent::__construct( 'ChangeNotification', $title, $params, $id );
	}

	/**
	 * Returns the batch of changes that should be processed.
	 *
	 * Change objects are loaded using a ChangesTable instance.
	 *
	 * @return Change[] the changes to process.
	 */
	public function getChanges() {
		if ( $this->changes === null ) {
			wfProfileIn( __METHOD__ . '#load' );

			$params = $this->getParams();
			$ids = $params['changeIds'];

			wfDebugLog( __CLASS__, __FUNCTION__ . ": loading " . count( $ids ) . " changes." );

			// load actual change records from the changes table
			// TODO: allow mock store for testing!
			// FIXME: This only works when executed on the client! check WBC_VERSION first!
			$table = WikibaseClient::getDefaultInstance()->getStore()->newChangesTable();
			$this->changes = $table->selectObjects( null, array( 'id' => $ids ), array(), __METHOD__ );

			wfDebugLog( __CLASS__, __FUNCTION__ . ": loaded " . count( $this->changes )
				. " of " . count( $ids ) . " changes." );

			if ( count( $this->changes ) != count( $ids ) ) {
				trigger_error( "Number of changes loaded mismatches the number of change IDs provided: "
					. count( $this->changes ) . " != " . count( $ids ) . ". "
					. " Some changes were lost, possibly due to premature pruning.",
					E_USER_WARNING );
			}

			wfProfileOut( __METHOD__ . '#load' );
		}

		return $this->changes;
	}

	/**
	 * Run the job
	 *
	 * @return boolean success
	 */
	public function run() {
		$changes = $this->getChanges();

		$changeHandler = $this->getChangeHandler();
		$changeHandler->handleChanges( $changes );

		if ( $changes ) {
			/* @var Change $last */
			$n = count( $changes );
			$last = end( $changes );

			wfDebugLog( __CLASS__, __METHOD__ . ": processed $n notifications, "
						. "up to " . $last->getId() . ", timestamp " . $last->getTime() . "; "
						. "Lag is " . $last->getAge() . " seconds." );
		} else {
			wfDebugLog( __CLASS__, __METHOD__ . ": processed no notifications." );
		}

		return true;
	}

	/**
	 * @return ChangeHandler
	 */
	private function getChangeHandler() {
		if ( !$this->changeHandler ) {
			$this->changeHandler = WikibaseClient::getDefaultInstance()->getChangeHandler();
		}

		return $this->changeHandler;
	}
}
