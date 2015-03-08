<?php

namespace Wikibase\RDF;

/**
 * Emitter interface for RDF output. RdfEmitter instances generally stateful, but should
 * be implemented to operate in a stream-like manner with a minimum of state.
 *
 * Caveats:
 * - no inline blank nodes
 * - no comments
 * - no collections
 * - no automatic xsd types
 * - no automatic prefixing
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface RdfEmitter {

	/**
	 * @param string $prefix
	 * @param string $name
	 *
	 * @return mixed An reference container (implementation specific, may be an object or a string)
	 */
	public function qname( $prefix, $name );

	/**
	 * @param string $uriString
	 *
	 * @return mixed An reference container (implementation specific, may be an object or a string)
	 */
	public function uri( $uriString );

	/**
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return mixed A reference container (implementation specific, may be an object or a string)
	 */
	public function blank( $label = null );

	/**
	 * Emit a document header. Must be paired with a later call to finish().
	 */
	public function start();

	/**
	 * Emit a document footer. Must be paired with a prior call to start().
	 */
	public function finish();

	/**
	 * Emit a prefix declaration.
	 * May remember the prefix and URI for later use.
	 *
	 * @param string $prefix
	 * @param mixed $uri a reference container as returned by uri()
	 */
	public function prefix( $prefix, $uri );

	/**
	 * Start an about clause. May or may not immediately emit anything.
	 * May remember the subject reference for later use.
	 *
	 * @param mixed $subject a reference container as returned by uri(), qname(), or blank()
	 *
	 * @return RdfEmitter $this
	 */
	public function about( $subject );

	/**
	 * Start a predicate clause. May or may not immediately emit anything.
	 * May remember the verb reference for later use.
	 *
	 * @param mixed $verb a reference container as returned by uri(), qname(), or blank();
	 *        or the shorthand "a" for rdf:type.
	 *
	 * @return RdfEmitter $this
	 */
	public function predicate( $verb );

	/**
	 * Emits a resource object.
	 *
	 * @param mixed $object a reference container as returned by uri(), qname(), or blank()
	 *
	 * @return RdfEmitter $this
	 */
	public function resource( $object );

	/**
	 * Emits a text object.
	 *
	 * @param string $text the text to be emitted
	 * @param string|null $language the language the text is in
	 *
	 * @return RdfEmitter $this
	 */
	public function text( $text, $language = null );


	/**
	 * Emits a text object.
	 *
	 * @param string $literal the value encoded as a string
	 * @param string|null $type a reference container as returned by uri(), qname(), or blank()
	 *
	 * @return RdfEmitter $this
	 */
	public function value( $literal, $type = null );


}
