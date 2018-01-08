<?php

namespace Wikibase\Repo\Maintenance;

use MWHttpRequest;

/**
 * Simple SPARQL client
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class SPARQLClient {

	/**
	 * Query timeout
	 */
	const TIMEOUT = 300;

	/**
	 * @var string
	 */
	private $endpoint;

	/**
	 * @var string
	 */
	private $baseURL;

	/**
	 * @param string $url SPARQL Endpoint
	 * @param string $baseURL RDF base URL - common prefix in entity URIs
	 */
	public function __construct( $url, $baseURL ) {
		$this->endpoint = $url;
		$this->baseURL = $baseURL;
	}

	/**
	 * Query SPARQL endpoint
	 *
	 * @param string $sparql query
	 * @param bool   $rawData Whether to return only values or full data objects
	 *
	 * @return array List of results, one row per array element
	 *               Each row will contain fields indexed by variable name.
	 * @throws SPARQLException
	 */
	public function query( $sparql, $rawData = false ) {
		$url = $this->endpoint . '?' . http_build_query( [ "query" => $sparql, "format" => "json" ] );
		$options = [ 'method' => 'GET', 'timeout' => self::TIMEOUT ];
		$request = MWHttpRequest::factory( $url, $options, __METHOD__ );
		$status = $request->execute();
		if ( !$status->isOK() ) {
			throw new SPARQLException( "HTTP error: {$status->getWikiText()}" );
		}
		$result = $request->getContent();
		$data = json_decode( $result, true );
		if ( !$data ) {
			throw new SPARQLException( "HTTP request failed, response:\n$result" );
		}

		return $this->extractData( $data, $rawData );
	}

	/**
	 * Get list of IDs satisfying the query
	 *
	 * @param string $sparql query
	 * @param string $item variable name designating the needed element
	 *
	 * @return string[]|false List of IDs from query
	 */
	public function getIDs( $sparql, $item ) {
		$data = $this->query( $sparql, false );
		if ( $data ) {
			return array_map( function ( $row ) use ( $item ) {
				return str_replace( $this->baseURL, '', $row[$item] );
			}, $data );
		}
		return [];
	}

	/**
	 * Extract data from SPARQL response format
	 *
	 * @param array $data SPARQL result
	 * @param bool  $rawData Whether to return only values or full data objects
	 *
	 * @return array List of results, one row per element.
	 */
	private function extractData( $data, $rawData = false ) {
		$result = [];
		if ( $data && !empty( $data['results'] ) ) {
			$vars = $data['head']['vars'];
			$resrow = [];
			foreach ( $data['results']['bindings'] as $row ) {
				foreach ( $vars as $var ) {
					if ( !isset( $row[$var] ) ) {
						$resrow[$var] = null;
						continue;
					}
					if ( $rawData ) {
						$resrow[$var] = $row[$var];
					} else {
						$resrow[$var] = $row[$var]['value'];
					}
				}
				$result[] = $resrow;
			}
		}
		return $result;
	}

}
