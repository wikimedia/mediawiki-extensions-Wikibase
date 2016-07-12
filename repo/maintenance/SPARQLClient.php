<?php

namespace Wikibase\Repo\Maintenance;

/**
 * Simple SPARQL client
 */
class SPARQLClient {

	/**
	 * Construct.
	 * @param string $url SPARQL Endpoint
	 * @param string $baseURL RDF base URL - common prefix in entity URIs
	 */
	public function __construct( $url, $baseURL ) {
		$this->endpoint = $url;
		$this->baseURL = $baseURL;
	}

	/**
	 * Query SPARQL endpoint
	 * @param string $sparql query
	 * @param bool   $rawData Whether to return only values or full data objects
	 * @return array|false
	 */
	public function query( $sparql, $rawData = false ) {
		$result =
			Http::get( $this->endpoint . '?' .
			           http_build_query( [ "query" => $sparql, "format" => "json" ] ), [ ],
				__METHOD__ );
		if ( !$result ) {
			return false;
		}
		$data = json_decode( $result, true );
		if ( !$data ) {
			return false;
		}

		return $this->extractData( $data, $rawData );
	}

	/**
	 * Get list of IDs satisfying the query
	 * @param string $sparql query
	 * @param string $item variable name designating the needed element
	 * @return array|false
	 */
	public function getIDs( $sparql, $item ) {
		$data = $this->query( $sparql, false );
		if ( $data ) {
			return array_map( function ( $row ) use ( $item ) {
				return str_replace( $this->baseURL, '', $row[$item] );
			}, $data );
		}
		return [ ];
	}

	/**
	 * Extract data from SPARQL response format
	 * @param array $data SPARQL result
	 * @param bool  $rawData Whether to return only values or full data objects
	 * @return array
	 */
	private function extractData( $data, $rawData = false ) {
		$result = [ ];
		if ( $data && !empty( $data['results'] ) ) {
			$vars = $data['head']['vars'];
			$resrow = [ ];
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
