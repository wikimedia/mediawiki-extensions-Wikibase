/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.StatementListSerializer' );

var datamodel = require( 'wikibase.datamodel' );

var testSets = [
	[
		new datamodel.StatementList(),
		[]
	], [
		new datamodel.StatementList( [ new datamodel.Statement(
			new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) )
		) ] ),
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
	assert.expect( 2 );
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
