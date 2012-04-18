
( function () {

	module( 'ext.wikibase', QUnit.newMwEnvironment() );

	test( '-- NaNNaN!', function() {
		expect(1);

		function theAnswerToLifeTheUniverseAndEverything() {
			return 42;
		}

		var matches = theAnswerToLifeTheUniverseAndEverything() === 42;

		ok(
			matches,
			'woho!'
		);
	} );

	test( '-- NaNNaN!', function() {
		expect(1);

		equal(
			Array(10).join( 'ohai' - 42 ) + ' batman!',
			'NaNNaNNaNNaNNaNNaNNaNNaNNaN batman!',
			'O_o'
		);
	} );

}());
