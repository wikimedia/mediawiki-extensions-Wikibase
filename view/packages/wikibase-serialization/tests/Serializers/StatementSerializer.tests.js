/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.StatementSerializer' );

var testSets = [
	[
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			null,
			wb.datamodel.Statement.RANK.NORMAL
		),
		{
			mainsnak: {
				snaktype: 'novalue',
				property: 'P1'
			},
			type: 'statement',
			rank: 'normal'
		}
	], [
		new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			new wb.datamodel.ReferenceList( [new wb.datamodel.Reference()] ),
			wb.datamodel.Statement.RANK.PREFERRED
		),
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
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var statementSerializer = new wb.serialization.StatementSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			statementSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
