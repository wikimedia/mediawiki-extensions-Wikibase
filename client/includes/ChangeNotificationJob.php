<?php

namespace Wikibase\Client;

use Job;
use Title;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\EntityChange;
use Wikimedia\Assert\Assert;

/**
 * Job for notifying a client wiki of a batch of changes on the repository.
 *
 * @see docs/change-propagation.wiki for an overview of the change propagation mechanism.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeNotificationJob extends Job {

	/**
	 * @var EntityChange[]|null Initialized lazily by getChanges.
	 */
	private $changes = null;

	/**
	 * @var ChangeHandler|null
	 */
	private $changeHandler = null;

	/**
	 * Constructs a ChangeNotificationJob representing the changes given by $changeIds.
	 *
	 * @note: the constructor's signature is dictated by Job::factory, so we'll have to
	 *           live with it even though it's rather ugly for our use case.
	 *
	 * @see      Job::factory.
	 *
	 * @param Title $title
	 * @param array|bool $params Needs to have two keys: "repo": the id of the repository,
	 *     "changeIds": array of change ids.
	 */
	public function __construct( Title $title, $params = false ) {
		parent::__construct( 'ChangeNotification', $title, $params );

		Assert::parameterType( 'array', $params, '$params' );
		Assert::parameter(
			isset( $params['repo'] ),
			'$params',
			'$params[\'repo\'] not set.'
		);
		Assert::parameter(
			isset( $params['changeIds'] ) && is_array( $params['changeIds'] ),
			'$params',
			'$params[\'changeIds\'] not set or not an array.'
		);
	}

	/**
	 * Returns the batch of changes that should be processed.
	 *
	 * EntityChange objects are loaded using a EntityChangeLookup.
	 *
	 * @return EntityChange[] the changes to process.
	 */
	private function getChanges() {
		if ( $this->changes === null ) {
			$params = $this->getParams();
			$ids = $params['changeIds'];

			wfDebugLog( __CLASS__, __FUNCTION__ . ": loading " . count( $ids ) . " changes." );

			// load actual change records from the changes table
			// TODO: allow mock store for testing!
			$changeLookup = WikibaseClient::getDefaultInstance()->getStore()->getEntityChangeLookup();
			$this->changes = $changeLookup->loadByChangeIds( $ids );

			wfDebugLog( __CLASS__, __FUNCTION__ . ": loaded " . count( $this->changes )
				. " of " . count( $ids ) . " changes." );

			if ( count( $this->changes ) != count( $ids ) ) {
				trigger_error( "Number of changes loaded mismatches the number of change IDs provided: "
					. count( $this->changes ) . " != " . count( $ids ) . ". "
					. " Some changes were lost, possibly due to premature pruning.",
					E_USER_WARNING );
			}
		}

		return $this->changes;
	}

	/**
	 * @return bool success
	 */
	public function run() {
		$changes = $this->getChanges();

		$changeHandler = $this->getChangeHandler();
		$changeHandler->handleChanges( $changes, $this->getRootJobParams() );

		if ( $changes ) {
			/** @var EntityChange $last */
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
