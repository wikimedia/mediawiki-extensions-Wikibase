/**
 * QUnit tests for wikibase.Reference
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.3
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

( function( wb, dv, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.reference.js', QUnit.newMwEnvironment() );

	var snaks = [
		new wb.PropertyNoValueSnak( 9001 ),
		new wb.PropertySomeValueSnak( 42 ),
		new wb.PropertyValueSnak( 23, new dv.StringValue( '~=[,,_,,]:3' ) )
	];

	var snakLists = [ snaks ];

	$.each( snaks, function( i, snak ) {
		snakLists.push( [ snak ] );
		snakLists.push( [ snak, new wb.PropertyValueSnak( 1, new dv.StringValue( 'O_o' ) ) ] );
	} );

	QUnit.test( 'constructor', function( assert ) {
		$.each( snakLists, function( i, snakList ) {
			var reference = new wb.Reference( snakList );

			assert.deepEqual(
				reference.getSnaks(),
				snakList,
				'Snaks were set correctly'
			);
		} );
	} );

	QUnit.test( 'setSnaks and getSnaks', function( assert ) {
		$.each( snakLists, function( i, snakList ) {
			var reference = new wb.Reference( [] );

			reference.setSnaks( snakList );

			assert.deepEqual(
				reference.getSnaks(),
				snakList,
				'Snaks were set correctly'
			);

			reference.setSnaks( [] );

			assert.strictEqual(
				reference.getSnaks().length,
				0,
				'Setting the snaks to an empty array should result in getSnaks returning an empty array'
			);
		} );
	} );

}( wikibase, dataValues, jQuery, QUnit ) );
