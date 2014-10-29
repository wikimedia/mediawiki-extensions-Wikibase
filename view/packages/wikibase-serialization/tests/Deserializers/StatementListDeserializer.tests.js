/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.StatementListDeserializer' );

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

QUnit.test( 'deserialize()', function( assert ) {
	var statementListDeserializer = new wb.serialization.StatementListDeserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			statementListDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Deserializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
