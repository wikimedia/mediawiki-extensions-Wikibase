/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'StrategyProvider' );

	var testSets = [
			{
				strategies: [ [ 0, 'key1' ], [ 1, 'key2' ] ]
			}
		],
		StrategyProvider = require( '../src/StrategyProvider.js' );

	QUnit.test( 'Constructor', function( assert ) {
		assert.expect( 1 );
		assert.ok(
			( new StrategyProvider() ) instanceof StrategyProvider,
			'Instantiated StrategyProvider.'
		);
	} );

	QUnit.test( 'registerStrategy() & getStrategyFor()', function( assert ) {
		assert.expect( 2 );
		for( var i = 0; i < testSets.length; i++ ) {
			var strategyProvider = new StrategyProvider();

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

}() );
