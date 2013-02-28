/**
 * @since 0.4
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( wb, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.Item', QUnit.newWbEnvironment() );

	var entityTestDefinition = $.extend( {}, wb.tests.testEntity.basicTestDefinition, {
		entityConstructor: wb.Item
	} );

	wb.tests.testEntity( entityTestDefinition );

	// TODO: test site-links stuff after it got implemented

}( wikibase, jQuery, QUnit ) );
