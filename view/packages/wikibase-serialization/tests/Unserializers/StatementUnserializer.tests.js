/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.StatementUnserializer' );

var testSets = [
	[
		{
			mainsnak: {
				snaktype: 'novalue',
				property: 'P1'
			},
			type: 'statement',
			rank: 'normal'
		},
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			null,
			wb.datamodel.Statement.RANK.NORMAL
		)
	], [
		{
			mainsnak: {
				snaktype: 'novalue',
				property: 'P1'
			},
			references: [ {
				snaks: {},
				'snaks-order': []
			}],
			type: 'statement',
			rank: 'preferred'
		},
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			new wb.datamodel.ReferenceList( [new wb.datamodel.Reference()] ),
			wb.datamodel.Statement.RANK.PREFERRED
		)
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var statementUnserializer = new wb.serialization.StatementUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			statementUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
