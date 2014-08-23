/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ClaimSerializer' );

var testSets = [
	[
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1' ),
		{
			id: 'Q1$1',
			mainsnak: {
				snaktype: 'novalue',
				property: 'P1'
			},
			type: 'claim'
		}
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var claimSerializer = new wb.serialization.ClaimSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			claimSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}
} );

}( wikibase, QUnit ) );
