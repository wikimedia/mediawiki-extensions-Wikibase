<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi;

use RuntimeException;

/**
 * Joins self-contained, dereferenced OpenAPI doc fragments into a base
 * OpenAPI document.
 *
 * Fragments contribute only their paths and tags: they are dereferenced, so
 * their components are redundant, and their info describes the fragment
 * rather than the joined document. Of each fragment, only the paths in the
 * joiner's routable set are joined; a fragment with no routable paths
 * contributes nothing, tags included.
 *
 * @license GPL-2.0-or-later
 */
class OpenApiDocFragmentJoiner {

	private string $baseJson;
	private array $routablePaths;
	private ?array $doc = null;

	/**
	 * @param string $baseJson the base OpenAPI document, encoded
	 * @param string[] $routablePaths the joinable paths
	 */
	public function __construct( string $baseJson, array $routablePaths ) {
		$this->baseJson = $baseJson;
		$this->routablePaths = $routablePaths;
	}

	/**
	 * @param array $fragment decoded, self-contained OpenAPI doc fragment
	 * @param string $sourceName names the fragment's origin in error messages
	 *
	 * @throws RuntimeException if the fragment redefines an existing path
	 */
	public function join( array $fragment, string $sourceName ): void {
		$paths = array_intersect_key( $fragment['paths'] ?? [], array_flip( $this->routablePaths ) );
		if ( $paths === [] ) {
			return;
		}

		$doc = $this->getDocument();

		foreach ( $paths as $path => $pathSpec ) {
			if ( array_key_exists( $path, $doc['paths'] ) ) {
				throw new RuntimeException( "OpenAPI doc fragment '$sourceName' redefines path '$path'" );
			}
			$doc['paths'][$path] = $pathSpec;
		}

		$existingTagNames = array_column( $doc['tags'] ?? [], 'name' );
		foreach ( $fragment['tags'] ?? [] as $tag ) {
			if ( !in_array( $tag['name'], $existingTagNames, true ) ) {
				$doc['tags'][] = $tag;
			}
		}

		$this->doc = $doc;
	}

	/**
	 * The joined document, encoded. Byte-identical to the base document if no
	 * fragment contributed anything.
	 */
	public function getDocumentJson(): string {
		if ( $this->doc === null ) {
			return $this->baseJson;
		}

		return json_encode( $this->doc, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function getDocument(): array {
		if ( $this->doc !== null ) {
			return $this->doc;
		}

		$doc = json_decode( $this->baseJson, true );
		if ( !is_array( $doc ) ) {
			throw new RuntimeException( 'The base OpenAPI document is not valid JSON' );
		}

		return $doc;
	}

}
