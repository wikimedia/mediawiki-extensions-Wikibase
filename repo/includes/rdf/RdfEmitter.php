<?php

namespace Wikibase\RDF;

/**
 * Emitter interface for RDF output. RdfEmitter instances generally stateful, but should
 * be implemented to operate in a stream-like manner with a minimum of state.
 *
 * Caveats:
 * - no relative uris
 * - predicates must be qnames
 * - no inline/nested blank nodes
 * - no comments
 * - no collections
 * - no automatic xsd types
 * - no automatic conversion of uris to qnames
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface RdfEmitter {

	/**
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return string A qname for the blank node.
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
	 * Implementations are free to fail if prefix() is called after the first call to about().
	 *
	 * @param string $prefix
	 * @param string $uri a reference container as returned by uri()
	 */
	public function prefix( $prefix, $uri );

	/**
	 * Start an about clause. May or may not immediately emit anything.
	 * Must be preceded by a call to start().
	 * May remember the subject reference for later use.
	 *
	 * @param string $subject a resource reference (URI or QName)
	 *
	 * @return RdfEmitter $this
	 */
	public function about( $subject );

	/**
	 * Start a predicate clause. May or may not immediately emit anything.
	 * Must be preceded by a call to about().
	 * May remember the verb reference for later use.
	 *
	 * @todo: rename to verb() maybe?
	 *
	 * @param string $verb a QName or the shorthand "a" for rdf:type; Implementations
	 *        may or may not support full URIs to be given here.
	 *
	 * @return RdfEmitter $this
	 */
	public function predicate( $verb );

	/**
	 * Emits a resource object.
	 * Must be preceded by a call to predicate().
	 *
	 * @param string $object a resource reference (URI or QName)
	 *
	 * @return RdfEmitter $this
	 */
	public function resource( $object );

	/**
	 * Emits a text object.
	 * Must be preceded by a call to predicate().
	 *
	 * @param string $text the text to be emitted
	 * @param string|null $language the language the text is in
	 *
	 * @return RdfEmitter $this
	 */
	public function text( $text, $language = null );


	/**
	 * Emits a text object.
	 * Must be preceded by a call to predicate().
	 *
	 * @param string $literal the value encoded as a string
	 * @param string|null $type a resource reference (URI or QName)
	 *
	 * @return RdfEmitter $this
	 */
	public function value( $literal, $type = null );


}
