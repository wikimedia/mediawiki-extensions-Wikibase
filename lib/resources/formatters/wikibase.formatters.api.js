/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
( function( wb, dataTypeStore ) {
	'use strict';

	wb.formatters = wb.formatters || {};

	var api = new wb.RepoApi();

	/**
	 * ValueFormatters API.
	 * @since 0.5
	 * @type {wb.api.FormatValueCaller}
	 */
	wb.formatters.api = new wb.api.FormatValueCaller(
		api,
		dataTypeStore
	);

}( wikibase, wikibase.dataTypes ) );
