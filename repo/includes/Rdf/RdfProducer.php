<?php

namespace Wikibase\Rdf;

/**
 * RDF producer options
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
interface RdfProducer {

	/** Produce "truthy" statements, i.e. best ranked
	 * entity-property-value without qualifiers or expanded values
	 */
	const PRODUCE_TRUTHY_STATEMENTS = 1;

	/**
	 * Produce all statements
	 */
	const PRODUCE_ALL_STATEMENTS = 2;

	/**
	 * Produce qualifiers for statements
	 * Should be used together with PRODUCE_ALL_STATEMENTS.
	 */
	const PRODUCE_QUALIFIERS = 4;

	/**
	 * Produce references for statements
	 * Should be used together with PRODUCE_ALL_STATEMENTS.
	 */
	const PRODUCE_REFERENCES = 8;

	/**
	 * Produce links and badges
	 */
	const PRODUCE_SITELINKS = 16;

	/**
	 * Add entity definitions for properties used in the dump.
	 */
	const PRODUCE_PROPERTIES = 32;

	/**
	 * Produce full expanded values as nodes.
	 * Should be used together with PRODUCE_ALL_STATEMENTS.
	 */
	const PRODUCE_FULL_VALUES = 64;

	/**
	 * Produce metadata header containing software version info and copyright.
	 */
	const PRODUCE_VERSION_INFO = 128;

	/**
	 * Produce definitions for all entities used in the dump
	 */
	const PRODUCE_RESOLVED_ENTITIES = 256;

	/**
	 * Produce normalized values for values with units.
	 */
	const PRODUCE_NORMALIZED_VALUES = 512;

	/**
	 * All options turned on.
	 */
	const PRODUCE_ALL = 0xFFFF;

}
