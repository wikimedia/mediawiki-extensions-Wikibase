<?php

namespace Wikibase\RDF;

/**
 * Buffer interface for RDF output.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface RdfBuffer {

	/**
	 * Flattens the buffer into a string, resets the buffer, and returns the string.
	 *
	 * @return string The RDF output
	 */
	public function drain();
}
