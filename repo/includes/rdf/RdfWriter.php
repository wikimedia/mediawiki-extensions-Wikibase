<?php

namespace Wikibase\RDF;

/**
 * Writer interface for RDF output. RdfWriter instances are generally stateful,
 * but should be implemented to operate in a stream-like manner with a minimum of state.
 *
 * This is intended to provide a "fluent interface" that allows programmers to use
 * a turtle-like structure when generating RDF output. E.g.:
 *
 * @code
 * $writer->prefix( 'acme', 'http://acme.test/terms/' );
 * $writer->about( 'http://quux.test/Something' )
 *   ->say( 'acme', 'name' )->text( 'Thingy' )->text( 'Dingsda', 'en' )
 *   ->say( 'acme', 'owner' )->is( 'http://quux.test/' );
 * @endcode
 *
 * To get the generated RDF output, use the drain() method.
 *
 * @note: The contract of this interface follows the GIGO principle, that is,
 * implementations are not required to ensure valid output or prompt failure on
 * invalid input. Speed should generally be favored over safety.
 *
 * Caveats:
 * - no relative iris
 * - predicates must be qnames
 * - no inline/nested blank nodes
 * - no comments
 * - no collections
 * - no automatic conversion of iris to qnames
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
	 * @return string A qname for the blank node.
	 */
	public function blank( $label = null );

	/**
	 * Start the document. May generate a header.
	 */
	public function start();

	/**
	 * Generates an RDF string from the current buffers state and returns it.
	 * The buffer is reset to the empty state.
	 * Before the result string is generated, implementations should close any
	 * pending syntactical structures (close tags, generate footers, etc).
	 *
	 * @return string The RDF output
	 */
	public function drain();

	/**
	 * Write a prefix declaration. May remember the prefix and IRI for later use.
	 * May fail if called if the writer's state doesn't allow a prefix in the
	 * current syntactical construct.
	 *
	 * @note Depending on implementation, re-definitions of prefixes may fail silently.
	 *
	 * @param string $prefix
	 * @param string $iri a IRI
	 */
	public function prefix( $prefix, $iri );

	/**
	 * Start an "about" (subject) clause, given a subject.
	 * Can occur at the beginning odf the output sequence, but can later only follow
	 * a call to is(), text(), or value().
	 * Should fail if called at an inappropriate time in the output sequence.
	 *
	 * @param string $base A QName prefix if $local is given, or an IRI if $local is null.
	 * @param string|null $local A QName suffix, or null if $base is an IRI.
	 *
	 * @return RdfWriter $this
	 */
	public function about( $base, $local = null );

	/**
	 * Start a predicate clause.
	 * Can only follow a call to about() or say().
	 * Should fail if called at an inappropriate time in the output sequence.
	 *
	 * @param string $base A QName prefix if $local is given, or an IRI or shorthand if $local is null.
	 * @param string|null $local A QName suffix, or null if $base is an IRI or shorthand.
	 *
	 * @return RdfWriter $this
	 */
	public function say( $base, $local = null );

	/**
	 * Produce a resource as the object of a statement.
	 * Can only follow a call to say() or a call to one of is(), text(), or value().
	 * Should fail if called at an inappropriate time in the output sequence.
	 *
	 * @param string $base A QName prefix if $local is given, or an IRI or shorthand if $local is null.
	 * @param string|null $local A QName suffix, or null if $base is an IRI or shorthand.
	 *
	 * @return RdfWriter $this
	 */
	public function is( $base, $local = null );

	/**
	 * Produce a text literal as the object of a statement.
	 * Can only follow a call to say() or a call to one of is(), text(), or value().
	 * Should fail if called at an inappropriate time in the output sequence.
	 *
	 * @param string $text the text to be writeted
	 * @param string|null $language the language the text is in
	 *
	 * @return RdfWriter $this
	 */
	public function text( $text, $language = null );


	/**
	 * Produce a typed or untyped literal as the object of a statement.
	 * Can only follow a call to say() or a call to one of is(), text(), or value().
	 * Should fail if called at an inappropriate time in the output sequence.
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
	 * This can be used to generate parts statements out of sequence.
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
	 * Returns the MIME type of the RDF serialization the writer produces.
	 *
	 * @return string a MIME type
	 */
	public function getMimeType();
}
