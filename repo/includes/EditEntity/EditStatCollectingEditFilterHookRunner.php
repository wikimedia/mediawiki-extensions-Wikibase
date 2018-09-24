<?php

namespace Wikibase\Repo\EditEntity;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Status;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Repo\Hooks\EditFilterHookRunner;

/**
 * EditFilterHookRunning that collects stats for edits.
 * @license GPL-2.0-or-later
 */
class EditStatCollectingEditFilterHookRunner implements EditFilterHookRunner {

	private $hookRunner;
	private $timeRecorder;

	public function __construct(
		EditFilterHookRunner $hookRunner,
		ExecutionTimeRecorder $timeRecorder
	) {
		$this->hookRunner = $hookRunner;
		$this->timeRecorder = $timeRecorder;
	}

	/**
	 * @param null|EntityDocument|EntityRedirect $new
	 * @param User $user
	 * @param string $summary
	 * @return Status
	 */
	public function run( $new, User $user, $summary ) {
		$arguments = func_get_args();

		return $this->timeRecorder->recordTiming(
			$new->getType() . '.EditFilterHookRunner.run',
			function() use ( $arguments ) {
				return $this->hookRunner->run( ...$arguments );
			}
		);
	}

}

class ExecutionTimeRecorder {

	private $stats;
	private $baseKey;

	public function __construct( StatsdDataFactoryInterface $stats, $baseKey = '' ) {
		$this->stats = $stats;
		$this->baseKey = $baseKey;
	}

	public function recordTiming( string $key, callable $function ) {
		$startTime = microtime( true ); // TODO: use Clock
		$returnValue = $function();
		$endTime = microtime( true );

		$this->recordToStatsd( $key, $endTime - $startTime );

		return $returnValue;
	}

	private function recordToStatsd( $key, $microSeconds ) {
		$this->stats->timing(
			$this->joinKeys( $this->baseKey, $key ),
			$microSeconds * 1000
		);
	}

	private function joinKeys( $baseKey, $key ) {
		if ( $baseKey === '' ) {
			return $key;
		}

		return $baseKey . '.' . $key;
	}

	public function createSubRecorder( $baseKey ) {
		return new self(
			$this->stats,
			$this->joinKeys( $this->baseKey, $baseKey )
		);
	}

}
