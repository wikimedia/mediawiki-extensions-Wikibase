/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.StatementListUnserializer' );

var testSets = [
	[
		[],
		new wb.datamodel.StatementList()
	], [
		[
			{
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'statement',
				rank: 'normal'
			}
		],
		new wb.datamodel.StatementList( [new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		)] )
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var statementListUnserializer = new wb.serialization.StatementListUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			statementListUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
