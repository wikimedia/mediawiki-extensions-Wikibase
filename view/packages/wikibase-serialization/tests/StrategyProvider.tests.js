/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.serialization.StrategyProvider' );

var testSets = [
	{
		strategies: [ [ 0, 'key1' ], [ 1, 'key2' ] ]
	}
];

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 1 );
	assert.ok(
		( new wb.serialization.StrategyProvider() ) instanceof wb.serialization.StrategyProvider,
		'Instantiated StrategyProvider.'
	);
} );

QUnit.test( 'registerStrategy() & getStrategyFor()', function( assert ) {
	assert.expect( 2 );
	for( var i = 0; i < testSets.length; i++ ) {
		var strategyProvider = new wb.serialization.StrategyProvider();

		for( var j = 0; j < testSets[i].strategies.length; j++ ) {
			strategyProvider.registerStrategy(
				testSets[i].strategies[j][0],
				testSets[i].strategies[j][1]
			);
		}

		for( j = 0; j < testSets[i].strategies.length; j++ ) {
			assert.strictEqual(
				strategyProvider.getStrategyFor( testSets[i].strategies[j][1] ),
				testSets[i].strategies[j][0]
			);
		}
	}
} );

}( wikibase, QUnit ) );
