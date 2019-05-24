<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use PHPUnit4And6Compat;
use Psr\Log\NullLogger;
use Status;
use Wikibase\Repo\SparqlEndpointReplicationStatus\WikimediaPrometheusSparqlEndpointReplicationStatus;

/**
 * @covers \Wikibase\Repo\SparqlEndpointReplicationStatus\WikimediaPrometheusSparqlEndpointReplicationStatus
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class WikimediaPrometheusSparqlEndpointReplicationStatusTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider getLagProvider
	 */
	public function testGetLag(
		$expectedLag,
		HttpRequestFactory $httpRequestFactory,
		array $prometheusUrls,
		array $relevantClusters
	) {
		$replicationStatus = new WikimediaPrometheusSparqlEndpointReplicationStatus(
			$httpRequestFactory,
			new NullLogger(),
			$prometheusUrls,
			$relevantClusters
		);
		$actualLag = $replicationStatus->getLag();

		if ( is_int( $expectedLag ) && is_int( $actualLag ) ) {
			// Due to the time it takes to run this after the creation of the fake responses, allow for some difference
			$this->assertTrue(
				abs( $expectedLag - $actualLag ) < 3,
				"abs( $expectedLag - $actualLag ) < 3"
			);
		} else {
			$this->assertSame( $expectedLag, $actualLag );
		}
	}

	private function newMWHttpRequestMock( $getContentCallback ) {
		$request = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();
		$request->expects( $this->any() )
			->method( 'execute' )
			->will( $this->returnValue( Status::newGood() ) );
		$request->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnCallback( $getContentCallback ) );

		return $request;
	}
	
	public function getLagProvider() {
		$json = file_get_contents( __DIR__ . '/PrometheusQueryBlazegraphLastupdated.json' );
		$laggedJson = file_get_contents( __DIR__ . '/PrometheusQueryBlazegraphLastupdated-lag.json' );

		// Replace all @time-n@ in a given string with the value of (time() - n)
		$timeDummyReplace = function ( $str, $multiplier = 1 ) {
			return preg_replace_callback(
				'/@time(-(\d+.?\d?))?@/',
				function( $match ) use ( $multiplier ) {
					return time() - ( isset( $match[2] ) ? $match[2] * $multiplier : 0 );
				},
				$str
			);
		};

		$failingRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();
		$failingRequest->expects( $this->any() )
			->method( 'execute' )
			->will( $this->returnValue( Status::newFatal( 'foo' ) ) );

		$noLagRequest = $this->newMWHttpRequestMock( function () use ( $timeDummyReplace, $json ) {
			return $timeDummyReplace( $json );
		} );
		$laggedRequest = $this->newMWHttpRequestMock( function () use ( $timeDummyReplace, $laggedJson ) {
			return $timeDummyReplace( $laggedJson );
		} );
		$heavilyLaggedRequest = $this->newMWHttpRequestMock( function () use ( $timeDummyReplace, $laggedJson ) {
			return $timeDummyReplace( $laggedJson, 2 );
		} );

		$requestFailingHttpRequestFactory = $this->getMock( HttpRequestFactory::class );
		$requestFailingHttpRequestFactory->expects( $this->once() )
			->method( 'create' )
			->will( $this->returnValue( $failingRequest ) );

		$noLagHttpRequestFactory = $this->getMock( HttpRequestFactory::class );
		$noLagHttpRequestFactory->expects( $this->once() )
			->method( 'create' )
			->will( $this->returnValue( $noLagRequest ) );

		$laggedHttpRequestFactory = $this->getMock( HttpRequestFactory::class );
		$laggedHttpRequestFactory->expects( $this->any() )
			->method( 'create' )
			->will( $this->returnValue( $laggedRequest ) );

		$multiRequestHttpRequestFactory = $this->getMock( HttpRequestFactory::class );
		$multiRequestHttpRequestFactory->expects( $this->any() )
			->method( 'create' )
			->will( $this->returnCallback( function() use ( $laggedRequest, $heavilyLaggedRequest ) {
				static $c = 0;
				$c++;
				if ( $c === 1) {
					return $heavilyLaggedRequest;
				} elseif ( $c === 2) {
					return $laggedRequest;
				} else {
					$this->fail( 'HttpRequestFactory::create should have been only called twice.' );
				}
			} ) );

		return [
			'empty prometheus URL array' => [
				null,
				$this->getMock( HttpRequestFactory::class ),
				[],
				[ 'foo' ]
			],
			'failing request' => [
				null,
				$requestFailingHttpRequestFactory,
				[ 'http://prometheus.svc.eqiad.wmnet/ops/api/v1/query?query=blazegraph_lastupdated' ],
				[ 'wdqs' ]
			],
			'good request, no lag' => [
				0,
				$noLagHttpRequestFactory,
				[ 'http://prometheus.svc.eqiad.wmnet/ops/api/v1/query?query=blazegraph_lastupdated' ],
				[ 'wdqs' ]
			],
			'good request, some lag' => [
				90,
				$laggedHttpRequestFactory,
				[ 'http://prometheus.svc.eqiad.wmnet/ops/api/v1/query?query=blazegraph_lastupdated' ],
				[ 'wdqs', 'wdqs-internal' ]
			],
			'good request, bad lag' => [
				500,
				$laggedHttpRequestFactory,
				[ 'http://prometheus.svc.eqiad.wmnet/ops/api/v1/query?query=blazegraph_lastupdated' ],
				[ 'wdqs', 'wdqs-internal', 'test' ]
			],
			'good request, nothing in group' => [
				null,
				$laggedHttpRequestFactory,
				[ 'http://prometheus.svc.eqiad.wmnet/ops/api/v1/query?query=blazegraph_lastupdated' ],
				[ 'blah' ]
			],
			'multiple requests' => [
				90,
				$laggedHttpRequestFactory,
				[
					'http://prometheus.svc.eqiad.wmnet/ops/api/v1/query?query=blazegraph_lastupdated',
					'http://prometheus.svc.codfw.wmnet/ops/api/v1/query?query=blazegraph_lastupdated',
				],
				[ 'wdqs', 'wdqs-internal' ]
			],
		];
	}

	/**
	 * @dataProvider getLagInvalidJSONProvider
	 */
	public function testGetLag_invalidJson(
		HttpRequestFactory $httpRequestFactory,
		array $prometheusUrls,
		array $relevantClusters
	) {
		$this->markTestIncomplete('TODO');
		$replicationStatus = new WikimediaPrometheusSparqlEndpointReplicationStatus(
			$httpRequestFactory,
			new NullLogger(),
			$prometheusUrls,
			$relevantClusters
		);

		$this->assertSame( null, $replicationStatus->getLag() );
	}
	
}
