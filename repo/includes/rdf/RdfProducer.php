<?php

namespace Wikibase;

/**
 * RDF producer options
 */
interface RdfProducer {
	const PRODUCE_TRUTHY_STATEMENTS = 1;
	const PRODUCE_ALL_STATEMENTS    = 2;
	const PRODUCE_QUALIFIERS        = 4;
	const PRODUCE_REFERENCES        = 8;
	const PRODUCE_SITELINKS         = 16;
	const PRODUCE_PROPERTIES        = 32;
	const PRODUCE_FULL_VALUES       = 64;
	const PRODUCE_VERSION_INFO      = 128;

	const PRODUCE_ALL = 0xFF;
}