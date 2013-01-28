/**
 * QUnit tests for wikibase.Reference
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.3
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( wb, dv, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.reference.js', QUnit.newMwEnvironment() );

	var snakLists = [
		new wb.SnakList( [
			new wb.PropertyNoValueSnak( 9001 ),
			new wb.PropertySomeValueSnak( 42 ),
			new wb.PropertyValueSnak( 23, new dv.StringValue( '~=[,,_,,]:3' ) )
		] ),
		new wb.SnakList( [] ),
		new wb.SnakList( [ new wb.PropertyNoValueSnak( 9001 ) ] )
	];

	QUnit.test( 'constructor', function( assert ) {
		$.each( snakLists, function( i, snakList ) {
			var reference = new wb.Reference( snakList );

			assert.ok(
				reference.getSnaks().equals( snakList ),
				'Snaks were set correctly'
			);
		} );
	} );

	QUnit.test( 'setSnaks and getSnaks', function( assert ) {
		$.each( snakLists, function( i, snakList ) {
			var reference = new wb.Reference( [] );

			reference.setSnaks( snakList );

			assert.ok(
				reference.getSnaks().equals( new wb.SnakList( snakList ) ),
				'Snaks were set correctly'
			);
		} );
	} );

}( wikibase, dataValues, jQuery, QUnit ) );
