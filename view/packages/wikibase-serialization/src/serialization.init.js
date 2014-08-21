/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
	'use strict';

	var MODULE = wb.serialization;

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.EntityUnserializer,
		wb.datamodel.Entity
	);

}( wikibase ) );
