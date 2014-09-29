/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.StatementGroupUnserializer' );

var testSets = [
	[
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
		new wb.datamodel.StatementGroup( 'P1',
			new wb.datamodel.StatementList( [new wb.datamodel.Statement(
				new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
			)] )
		)
	]
];

QUnit.test( 'unserialize()', function( assert ) {
	var statementGroupUnserializer = new wb.serialization.StatementGroupUnserializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.ok(
			statementGroupUnserializer.unserialize( testSets[i][0] ).equals( testSets[i][1] ),
			'Test set #' + i + ': Unserializing successful.'
		);
	}

	assert.throws(
		function() {
			statementGroupUnserializer.unserialize( [] );
		},
		'Unable to unserialize an empty array since there is no way to determine the property id '
			+ 'statements shall be grouped with.'
	)
} );

}( wikibase, QUnit ) );
