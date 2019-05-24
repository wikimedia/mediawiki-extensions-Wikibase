<?php

namespace Wikibase\Repo\SparqlEndpointReplicationStatus;
use MediaWiki\Http\HttpRequestFactory;
use Psr\Log\LoggerInterface;

/**
 * !TODO!
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class WikimediaPrometheusSparqlEndpointReplicationStatus implements SparqlEndpointReplicationStatus {

	/**
	 * @var HttpRequestFactory
	 */
	private $httpRequestFactory;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string[]
	 */
	private $prometheusUrls;

	/**
	 * @var string[]
	 */
	private $relevantClusters;

	/**
	 * @param HttpRequestFactory $httpRequestFactory
	 * @param LoggerInterface $logger
	 * @param string[] $prometheusUrls Prometheus URLs to query for "blazegraph_lastupdated"
	 * @param string[] $relevantClusters
	 */
	public function __construct( HttpRequestFactory $httpRequestFactory, LoggerInterface $logger, $prometheusUrls, $relevantClusters ) {
		$this->httpRequestFactory = $httpRequestFactory;
		$this->logger = $logger;
		$this->prometheusUrls = $prometheusUrls;
		$this->relevantClusters = $relevantClusters;
	}

	/**
	 * @return int|null Lag in seconds or null if the lag couldn't be determined.
	 */
	public function getLag() {
		$result = $this->fetchData();
		$lagByInstance = $this->processMetrics( $result );

		// Take the median lag
		sort( $lagByInstance );
		$i = floor( count( $lagByInstance ) / 2 );

		return $lagByInstance ? $lagByInstance[$i] : null;
	}

	private function fetchData() {
		$result = [];
		foreach ( $this->prometheusUrls as $prometheusUrl ) {
			// XXX: Custom timeout?
			$request = $this->httpRequestFactory->create(
				$prometheusUrl,
				[],
				__METHOD__
			);
			$requestStatus = $request->execute();

			if ( !$requestStatus->isOK() ) {
				$this->logger->warning(
					'{method}: Request to Prometheus API {apiUrl} failed with {error}',
					[
						'method' => __METHOD__,
						'apiUrl' => $prometheusUrl,
						'error' => $requestStatus->getMessage()->inContentLanguage()->text()
					]
				);
				continue;
			}

			$value = json_decode( $request->getContent(), true );
			if (
				!isset( $value['data'] ) ||
				!isset( $value['data']['result'] ) ||
				!is_array( $value['data']['result'] )
			) {
				// TODO: Warn bad result
				continue;
			}
			$result = array_merge( $result, $value['data']['result'] );
		}

		return $result;
	}

	private function processMetrics( array $result ) {
		$lagByInstance = [];
		foreach ( $result as $resultByInstance ) {
			if (
				!isset( $resultByInstance['metric']['cluster'] ) ||
				!isset( $resultByInstance['metric']['instance'] ) ||
				!isset( $resultByInstance['value'][1] )
			) {
				// TODO: Warn bad data
				continue;
			}

			if ( !in_array( $resultByInstance['metric']['cluster'], $this->relevantClusters ) ) {
				continue;
			}
			$lagByInstance[$resultByInstance['metric']['instance']] = time() - $resultByInstance['value'][1];
		}

		return $lagByInstance;
	}

}