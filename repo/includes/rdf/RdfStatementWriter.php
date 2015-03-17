<?php

namespace Wikibase\RDF;

/**
 * Writer interface for RDF statements.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface RdfStatementWriter {

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

}
