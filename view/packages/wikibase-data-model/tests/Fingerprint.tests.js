/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.Fingerprint' );

var testSets = [
	[
		new wb.datamodel.TermMap(),
		new wb.datamodel.TermMap(),
		new wb.datamodel.MultiTermMap()
	], [
		new wb.datamodel.TermMap( {
			de: new wb.datamodel.Term( 'de', 'de-label' ),
			en: new wb.datamodel.Term( 'en', 'en-label' )
		} ),
		new wb.datamodel.TermMap( {
			de: new wb.datamodel.Term( 'de', 'de-description' ),
			en: new wb.datamodel.Term( 'en', 'en-description' )
		} ),
		new wb.datamodel.MultiTermMap( {
			de: new wb.datamodel.MultiTerm( 'de', ['de-alias1', 'de-alias2'] ),
			en: new wb.datamodel.MultiTerm( 'en', ['en-alias1'] )
		} )
	]
];

QUnit.test( 'Constructor (positive)', function( assert ) {
	assert.expect( 26 );
	var i, fingerprint;

	/**
	 * @param {QUnit.assert} assert
	 * @param {string} term
	 * @param {wikibase.datamodel.Map} map
	 */
	function checkGetters( assert, term, map ) {
		var languageCodes = map.getKeys(),
			functionNames = {
				labels: ['getLabels', 'hasLabel', 'hasLabelFor', 'getLabelFor'],
				descriptions: ['getDescriptions', 'hasDescription', 'hasDescriptionFor', 'getDescriptionFor'],
				aliases: ['getAliases', 'hasAliases', 'hasAliasesFor', 'getAliasesFor']
			};

		assert.ok(
			fingerprint[functionNames[term][0]](),
			'Test set #' + i + ': Verified result of ' + functionNames[term][0] + '.'
		);

		for( var j = 0; j < languageCodes.length; j++ ) {
			var expectedItem = map.getItemByKey( languageCodes[j] );

			assert.ok(
				fingerprint[functionNames[term][1]]( languageCodes[j], expectedItem ),
				'Test set #' + i + ': Verified result of ' + functionNames[term][1]
					+ ' for language #' + languageCodes[j] + '.'
			);

			assert.ok(
				fingerprint[functionNames[term][2]]( languageCodes[j] ),
				'Test set #' + i + ': Verified result of ' + functionNames[term][2]
					+ ' for language #' + languageCodes[j] + '.'
			);

			assert.ok(
				fingerprint[functionNames[term][3]]( languageCodes[j] ),
				'Test set #' + i + ': Verified result of ' + functionNames[term][3]
					+ ' for language #' + languageCodes[j] + '.'
			);
		}
	}

	for( i = 0; i < testSets.length; i++ ) {
		fingerprint = new wb.datamodel.Fingerprint(
			testSets[i][0], testSets[i][1], testSets[i][2]
		);

		assert.ok(
			fingerprint instanceof wb.datamodel.Fingerprint,
			'Test set #' + i +': Instantiated Fingerprint.'
		);

		var maps = {
				labels: testSets[i][0],
				descriptions: testSets[i][1],
				aliases: testSets[i][2]
			};

		for( var term in maps ) {
			checkGetters( assert, term, maps[term] );
		}
	}
} );

QUnit.test( 'Constructor (negative)', function( assert ) {
	assert.expect( 3 );
	var negativeTestSets = [
		['string', new wb.datamodel.TermMap(), new wb.datamodel.MultiTermMap()],
		[new wb.datamodel.TermMap(), 'string', new wb.datamodel.MultiTermMap()],
		[new wb.datamodel.TermMap(), new wb.datamodel.TermMap(), 'string']
	];

	/**
	 * @param {wikibase.datamodel.TermMap} labels
	 * @param {wikibase.datamodel.TermMap} descriptions
	 * @param {wikibase.datamodel.MultiTermMap} aliasGroups
	 * @return {Function}
	 */
	function instantiateObject( labels, descriptions, aliasGroups ) {
		return function() {
			return new wb.datamodel.Fingerprint( labels, descriptions, aliasGroups );
		};
	}

	for( var i = 0; i < negativeTestSets.length; i++ ) {
		assert.throws(
			instantiateObject(
				negativeTestSets[i][0], negativeTestSets[i][1], negativeTestSets[i][2]
			),
			'Test set #' + i +': Threw expected error.'
		);
	}
} );

QUnit.test( 'setLabel()', function( assert ) {
	assert.expect( 4 );
	var fingerprint = new wb.datamodel.Fingerprint(),
		label = new wb.datamodel.Term( 'de', 'de-label' );

	assert.ok(
		!fingerprint.hasLabel( 'de', label ),
		'Verified fingerprint not featuring the label that will be added.'
	);

	assert.throws(
		function() {
			fingerprint.setLabel( label );
		},
		'Throwing error when trying to set a label without specifying a language code.'
	);

	fingerprint.setLabel( 'de', label );

	assert.ok(
		fingerprint.hasLabel( 'de', label ),
		'Set label.'
	);

	fingerprint.setLabel( 'de', new wb.datamodel.Term( 'de', '' ) );

	assert.ok(
		!fingerprint.hasLabelFor( 'de' ),
		'Set label with empty term removes label.'
	);
} );

QUnit.test( 'removeLabel()', function( assert ) {
	assert.expect( 3 );
	var label = new wb.datamodel.Term( 'de', 'de-label' ),
		fingerprint = new wb.datamodel.Fingerprint( new wb.datamodel.TermMap( { de: label } ) );

	assert.ok(
		fingerprint.hasLabel( 'de', label ),
		'Verified fingerprint featuring the label to be removed.'
	);

	assert.throws(
		function() {
			fingerprint.removeLabel( label );
		},
		'Throwing error when trying to remove a label without specifying a language code.'
	);

	fingerprint.removeLabel( 'de', label );

	assert.ok(
		!fingerprint.hasLabel( 'de', label ),
		'Removed label.'
	);
} );

QUnit.test( 'removeLabelFor()', function( assert ) {
	assert.expect( 2 );
	var label = new wb.datamodel.Term( 'de', 'de-label' ),
		fingerprint = new wb.datamodel.Fingerprint( new wb.datamodel.TermMap( { de: label } ) );

	assert.ok(
		fingerprint.hasLabel( 'de', label ),
		'Verified fingerprint featuring the label to be removed.'
	);

	fingerprint.removeLabelFor( 'de' );

	assert.ok(
		!fingerprint.hasLabel( 'de', label ),
		'Removed label.'
	);
} );

QUnit.test( 'setDescription()', function( assert ) {
	assert.expect( 4 );
	var fingerprint = new wb.datamodel.Fingerprint(),
		description = new wb.datamodel.Term( 'de', 'de-description' );

	assert.ok(
		!fingerprint.hasDescription( 'de', description ),
		'Verified fingerprint not featuring the description that will be added.'
	);

	assert.throws(
		function() {
			fingerprint.setDescription( description );
		},
		'Throwing error when trying to set a description without specifying a language code.'
	);

	fingerprint.setDescription( 'de', description );

	assert.ok(
		fingerprint.hasDescription( 'de', description ),
		'Set description.'
	);

	fingerprint.setDescription( 'de', new wb.datamodel.Term( 'de', '' ) );

	assert.ok(
		!fingerprint.hasDescriptionFor( 'de' ),
		'Set description with empty term removes description.'
	);
} );

QUnit.test( 'removeDescription()', function( assert ) {
	assert.expect( 3 );
	var description = new wb.datamodel.Term( 'de', 'de-description' ),
		fingerprint = new wb.datamodel.Fingerprint(
			null,
			new wb.datamodel.TermMap( { de: description } )
		);

	assert.ok(
		fingerprint.hasDescription( 'de', description ),
		'Verified fingerprint featuring the description to be removed.'
	);

	assert.throws(
		function() {
			fingerprint.removeDescription( description );
		},
		'Throwing error when trying to remove a description without specifying a language code.'
	);

	fingerprint.removeDescription( 'de', description );

	assert.ok(
		!fingerprint.hasDescription( 'de', description ),
		'Removed description.'
	);
} );

QUnit.test( 'removeDescriptionFor()', function( assert ) {
	assert.expect( 2 );
	var description = new wb.datamodel.Term( 'de', 'de-description' ),
		fingerprint = new wb.datamodel.Fingerprint(
			null,
			new wb.datamodel.TermMap( { de: description } )
		);

	assert.ok(
		fingerprint.hasDescription( 'de', description ),
		'Verified fingerprint featuring the description to be removed.'
	);

	fingerprint.removeDescriptionFor( description.getLanguageCode() );

	assert.ok(
		!fingerprint.hasDescription( 'de', description ),
		'Removed description.'
	);
} );

QUnit.test( 'setAliases()', function( assert ) {
	assert.expect( 8 );
	var fingerprint = new wb.datamodel.Fingerprint(),
		deAliases = new wb.datamodel.MultiTerm( 'de', ['de-alias'] ),
		enAliases = new wb.datamodel.MultiTerm( 'en', ['en-alias'] ),
		aliases = new wb.datamodel.MultiTermMap( { en: enAliases } );

	assert.ok(
		!fingerprint.hasAliases( 'de', deAliases ),
		'Verified fingerprint not featuring the aliases that will be added.'
	);

	assert.throws(
		function() {
			fingerprint.setAliases( deAliases );
		},
		'Throwing error when trying to set a MultiTerm without specifying a language code.'
	);

	fingerprint.setAliases( 'de', deAliases );

	assert.ok(
		fingerprint.hasAliases( 'de', deAliases ),
		'Set aliases passing a MultiTerm object.'
	);

	assert.throws(
		function() {
			fingerprint.setAliases( 'de', aliases );
		},
		'Throwing error when trying to set a MultiTermMap with a language code.'
	);

	assert.ok(
		!fingerprint.hasAliases( 'en', enAliases ),
		'Verified fingerprint not featuring the aliases that will be added.'
	);

	fingerprint.setAliases( aliases );

	assert.ok(
		fingerprint.hasAliases( 'en', enAliases ),
		'Set aliases passing a MultiTermMap object.'
	);

	fingerprint.setAliases( 'en', new wb.datamodel.MultiTerm( 'en', [] ) );

	assert.ok(
		!fingerprint.hasAliasesFor( 'en' ),
		'Set aliases with empty list removes aliases.'
	);

	assert.throws(
		function() {
			fingerprint.setAliases( new wb.datamodel.MultiTerm( 'en', [] ) );
		},
		'Throwing error when trying to set an empty MultiTerm without specifying a language code.'
	);
} );

QUnit.test( 'removeAliases()', function( assert ) {
	assert.expect( 3 );
	var aliases = new wb.datamodel.MultiTerm( 'de', ['de-alias'] ),
		fingerprint = new wb.datamodel.Fingerprint(
			null,
			null,
			new wb.datamodel.MultiTermMap( { de: aliases } )
		);

	assert.ok(
		fingerprint.hasAliases( 'de', aliases ),
		'Verified fingerprint featuring the aliases to be removed.'
	);

	assert.throws(
		function() {
			fingerprint.removeAliases( aliases );
		},
		'Throwing error when trying to remove aliases without specifying a language code.'
	);

	fingerprint.removeAliases( 'de', aliases );

	assert.ok(
		!fingerprint.hasAliases( 'de', aliases ),
		'Removed aliases.'
	);
} );

QUnit.test( 'removeAliasesFor()', function( assert ) {
	assert.expect( 2 );
	var aliases = new wb.datamodel.MultiTerm( 'de', ['de-alias'] ),
		fingerprint = new wb.datamodel.Fingerprint(
			null,
			null,
			new wb.datamodel.MultiTermMap( { de: aliases } )
		);

	assert.ok(
		fingerprint.hasAliases( 'de', aliases ),
		'Verified fingerprint featuring the aliases to be removed.'
	);

	fingerprint.removeAliasesFor( 'de' );

	assert.ok(
		!fingerprint.hasAliases( 'de', aliases ),
		'Removed aliases.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.expect( 4 );
	assert.ok(
		( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermMap(),
			new wb.datamodel.TermMap(),
			new wb.datamodel.MultiTermMap()
		) ).isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	assert.ok(
		!( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'en-string' ) } ),
			new wb.datamodel.TermMap(),
			new wb.datamodel.MultiTermMap()
		) ).isEmpty(),
		'FALSE when there is a label.'
	);

	assert.ok(
		!( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermMap(),
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'en-string' ) } ),
			new wb.datamodel.MultiTermMap()
		) ).isEmpty(),
		'FALSE when there is a description.'
	);

	assert.ok(
		!( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermMap(),
			new wb.datamodel.TermMap(),
			new wb.datamodel.MultiTermMap( {
				en: new wb.datamodel.MultiTerm( 'en', ['en-string'] )
			} )
		) ).isEmpty(),
		'FALSE when there is an alias.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	assert.expect( 4 );
	for( var i = 0; i < testSets.length; i++ ) {
		var fingerprint1 = new wb.datamodel.Fingerprint(
			testSets[i][0], testSets[i][1], testSets[i][2]
		);

		for( var j = 0; j < testSets.length; j++ ) {
			var fingerprint2 = new wb.datamodel.Fingerprint(
				testSets[j][0], testSets[j][1], testSets[j][2]
			);

			if( j === i ) {
				assert.ok(
					fingerprint1.equals( fingerprint2 ),
					'Test set #' + i + ' equals test set #' + j + '.'
				);
				continue;
			}

			assert.ok(
				!fingerprint1.equals( fingerprint2 ),
				'Test set #' + i + ' does not equal test set #' + j + '.'
			);
		}
	}
} );

}( wikibase, QUnit ) );
