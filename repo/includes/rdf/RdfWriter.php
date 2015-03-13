<?php

namespace Wikibase\RDF;

/**
 * Writer interface for RDF output. RdfWriter instances generally stateful, but should
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
interface RdfWriter {

	//TODO: split: generic RdfWriter class with shorthands, use RdfFormatters for output
	//TODO: magic DSL class on top.
	//TODO: dummy ->and()  does nothing, returns this.

	/**
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return string A local name for the blank node, for use with the '_' prefix.
	 */
	public function blank( $label = null );

	/**
	 * Write a document header.
	 */
	public function start();

	/**
	 * Generates an RDF string from the current buffers state and returns it.
	 * The buffer is reset to the empty state.
	 * Before the result string is generated, implementations should close any
	 * open syntactical structures (close tags, generate footers, etc).
	 *
	 * @return string The RDF output
	 */
	public function drain();

	/**
	 * Write a prefix declaration.
	 * May remember the prefix and URI for later use.
	 * Implementations are free to fail if prefix() is called after the first call to about().
	 *
	 * @note Depending on implementation, re-definitions of prefixes may fail silently.
	 *
	 * @param string $prefix
	 * @param string $uri a reference container as returned by uri()
	 */
	public function prefix( $prefix, $uri );

	/**
	 * Start an about clause. May or may not immediately write anything.
	 * Must be preceded by a call to start().
	 * May remember the subject reference for later use.
	 *
	 * @param string $base A QName prefix if $local is given, or an IRI if $local is null.
	 * @param string|null $local A QName suffix, or null if $base is an IRI.
	 *
	 * @return RdfWriter $this
	 */
	public function about( $base, $local = null );

	/**
	 * Start a predicate clause. May or may not immediately write anything.
	 * Must be preceded by a call to about().
	 * May remember the verb reference for later use.
	 *
	 * @param string $base A QName prefix if $local is given, or an IRI or shorthand if $local is null.
	 * @param string|null $local A QName suffix, or null if $base is an IRI or shorthand.
	 *
	 * @return RdfWriter $this
	 */
	public function say( $base, $local = null );

	/**
	 * Emits a resource object.
	 * Must be preceded by a call to predicate().
	 *
	 * @param string $base A QName prefix if $local is given, or an IRI or shorthand if $local is null.
	 * @param string|null $local A QName suffix, or null if $base is an IRI or shorthand.
	 *
	 * @return RdfWriter $this
	 */
	public function is( $base, $local = null );

	/**
	 * Emits a text object.
	 * Must be preceded by a call to say().
	 *
	 * @param string $text the text to be writeted
	 * @param string|null $language the language the text is in
	 *
	 * @return RdfWriter $this
	 */
	public function text( $text, $language = null );


	/**
	 * Emits a value object.
	 * Must be preceded by a call to say().
	 *
	 * @param string $literal the value encoded as a string
	 * @param string $typeBase The data type's QName prefix if $typeLocal is given,
	 *        or an IRI or shorthand if $typeLocal is null.
	 * @param string|null $typeLocal The data type's  QName suffix,
	 *        or null if $typeBase is an IRI or shorthand.
	 *
	 * @return RdfWriter $this
	 */
	public function value( $literal, $typeBase = null, $typeLocal = null );

	/**
	 * Shorthand for say( 'a' )->is( $type ).
	 *
	 * @param string $typeBase The data type's QName prefix if $typeLocal is given,
	 *        or an IRI or shorthand if $typeLocal is null.
	 * @param string|null $typeLocal The data type's  QName suffix,
	 *        or null if $typeBase is an IRI or shorthand.
	 *
	 * @return RdfWriter $this
	 */
	public function a( $typeBase, $typeLocal = null );

	/**
	 * Returns a document-level sub-writer.
	 *
	 * @note: do not call drain() on sub-writers!
	 *
	 * @return RdfWriter
	 */
	public function sub();

	/**
	 * Resets any state the writer may be holding.
	 */
	public function reset();

	/**
	 * @return string a MIME type
	 */
	public function getMimeType();
}
