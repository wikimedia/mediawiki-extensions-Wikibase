/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.StatementListSerializer' );

var testSets = [
	[
		new wb.datamodel.StatementList(),
		[]
	], [
		new wb.datamodel.StatementList( [new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		)] ),
		[
			{
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'statement',
				rank: 'normal'
			}
		]
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var statementListSerializer = new wb.serialization.StatementListSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			statementListSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
