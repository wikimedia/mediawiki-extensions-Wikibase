/**
 * @since 0.4
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( wb, dt, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.Property', QUnit.newWbEnvironment() );

	// TODO: not nice to just assume that 'commonsMedia' data type is globally registered,
	//  should create own mock data type instead and not rely on global data type store...

	var entityTestDefinition = $.extend( true, {}, wb.tests.testEntity.basicTestDefinition, {
		entityConstructor: wb.Property,
		testData: {
			empty: {
				datatype: 'commonsMedia'
			},
			newOne: {
				datatype: 'commonsMedia'
			},
			full: {
				datatype: 'commonsMedia'
			}
		}
	} );

	wb.tests.testEntity( entityTestDefinition );

}( wikibase, dataTypes, jQuery, QUnit ) );
