<?php

namespace Wikibase\Repo\SparqlEndpointReplicationStatus;
use BagOStuff;
use InvalidArgumentException;
use Job;
use JobQueueGroup;

/**
 * !TODO!
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class SparqlEndpointReplicationStatusStateHandler implements SparqlEndpointReplicationStatus {

	/**
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var JobQueueGroup
	 */
	private $jobQueueGroup;

	/**
	 * @var string
	 */
	private $cacheKeyVariation;

	/**
	 * @var int
	 */
	private $refreshInterval;

	/**
	 * @var int
	 */
	private $refreshWindow;

	/**
	 * @param BagOStuff $cache
	 * @param int $refreshInterval Amount of seconds after which the lag should be refreshed (roughly)
	 * @param int $refreshWindow Timeframe around $refreshInterval in which the refreshing shall be
	 *    started. This will exponentially increase the refreshing likelihood from 0 to 1 in
	 *    [ $refreshInterval - $refreshWindow / 2, $refreshInterval + $refreshWindow / 2]
	 * @param string $cacheKeyVariation
	 */
	public function __construct(
		BagOStuff $cache,
		array $sparqlEndpointReplicationStatusIdentifiers,
		$refreshInterval,
		$refreshWindow,
		$cacheKeyVariation = ''
	) {
		$this->cache = $cache;
		$this->refreshInterval = $refreshInterval;
		$this->refreshWindow = $refreshWindow;
		$this->cacheKeyVariation = $cacheKeyVariation;
	}

	/**
	 * @return int|null Lag in seconds or null if the lag couldn't be determined.
	 */
	public function getLag() {
		list( $lastRefresh, $lastRefreshRequest, $lag ) = $this->cache->getMulti( [
			$this->makeCacheKey( 'lastRefresh' ),
			$this->makeCacheKey( 'lastRefreshRequest' ),
			$this->makeCacheKey( 'lag' ),
		] );

		if ( $this->shouldRefreshData( $lastRefresh, $lastRefreshRequest ) ) {
			$this->scheduleRefreshData();
		}

		return is_int( $lag ) ? $lag : null;
	}

	public function setLag( $lag, $lastRefreshTime ) {
		if ( $lastRefreshTime > time() ) {
			throw new InvalidArgumentException( '$lastRefreshTime must not be in the future.' );
		}
		if ( $lag !== null && is_int( $lag ) && $lag < 0 ) {
			throw new InvalidArgumentException( '$lag must be null or a non-negative integer.' );
		}

		$this->cache->setMulti( [
			$this->makeCacheKey( 'lag' ) => $lag,
			$this->makeCacheKey( 'lastRefresh' ) => $lastRefreshTime
		] );

		// If possible, already submit the Job for next time
		$hasDelayedJobs = $this->jobQueueGroup->get( SparqlEndpointReplicationStatusRefreshJob::command )
			->delayedJobsEnabled();

		if ( $hasDelayedJobs ) {
			$nextRefreshTime = $lastRefreshTime + $this->refreshInterval;
			$params = [ 'jobReleaseTimestamp' => $nextRefreshTime ];

			$job = Job::factory( SparqlEndpointReplicationStatusRefreshJob::command, $params );
			$this->jobQueueGroup->push( $job );
			$this->cache->set(
				$this->makeCacheKey( 'lastRefreshRequest' ),
				$nextRefreshTime
			);
		}
	}

	private function scheduleRefreshData() {
		$this->cache->set(
			$this->makeCacheKey( 'lastRefreshRequest' ),
			time()
		);

		$job = Job::factory( SparqlEndpointReplicationStatusRefreshJob::command );
		$this->jobQueueGroup->push( $job );
	}
	
	/**
	 * @param int|bool $lastRefresh
	 * @param int|bool $lastRefreshRequest
	 * @return bool true if the data should be refreshed.
	 */
	private function shouldRefreshData( $lastRefresh, $lastRefreshRequest ) {
		if ( !$lastRefresh || !$lastRefreshRequest ) {
			// Pretend the data is just about to outdate, so that we will throttle the
			// job scheduling just like below.
			$this->cache->setMulti( [
				$this->makeCacheKey( 'lastRefreshRequest' ) => 0,
				$this->makeCacheKey( 'lastRefresh' ) =>
					time() - $this->refreshInterval - $this->refreshWindow / 2
			] );
			return true;
		}
		if ( $lastRefreshRequest <= $lastRefresh ) {
			$dataAge = time() - $lastRefresh;
			$refreshIntervalStart = $this->refreshInterval - $this->refreshWindow / 2;
			if ( $refreshIntervalStart < $dataAge ) {
				return false;
			}
			return $this->throttleScheduleRefreshData( $dataAge - $refreshIntervalStart );
		}
		if ( $lastRefreshRequest <= time() - 5 * $this->refreshWindow ) {
			// The data is significantly outdated, so schedule a refresh, just in case.

			// Throttle this by pretending that $lastRefreshRequest + 5 * $this->refreshWindow
			// is the time when our refresh interval started.
			$refreshTime = time() - $lastRefreshRequest - 5 * $this->refreshWindow;
			return $this->throttleScheduleRefreshData( $refreshTime );
		}

		return false;
	}

	/**
	 * @param int $refreshTime Time which passed since we entered the refreshInterval.
	 * @return bool true if the data should be refreshed.
	 */
	private function throttleScheduleRefreshData( $refreshTime ) {
		// Choose $exponentFactor so that exp($exponentFactor * $refreshWindow) = $refreshWindow
		$exponentFactor = log( $this->refreshWindow ) / $this->refreshWindow;

		$cutoff = exp( $exponentFactor * $refreshTime ) / $this->refreshWindow;
		return ( mt_rand() / mt_getrandmax() ) < $cutoff;
	}

	/**
	 * @param string $type
	 * @return string
	 */
	private function makeCacheKey( $type ) {
		return $this->cache->makeKey( 'SparqlEndpointReplicationStatus', $type, $this->cacheKeyVariation );
	}

}
