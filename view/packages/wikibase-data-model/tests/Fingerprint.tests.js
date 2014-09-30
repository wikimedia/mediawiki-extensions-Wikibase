/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit, $ ) {
'use strict';

QUnit.module( 'wikibase.datamodel.Fingerprint' );

var testSets = [
	[
		new wb.datamodel.TermSet(),
		new wb.datamodel.TermSet(),
		new wb.datamodel.MultiTermSet()
	], [
		new wb.datamodel.TermSet( [
			new wb.datamodel.Term( 'de', 'de-label' ),
			new wb.datamodel.Term( 'en', 'en-label' )
		] ),
		new wb.datamodel.TermSet( [
			new wb.datamodel.Term( 'de', 'de-description' ),
			new wb.datamodel.Term( 'en', 'en-description' )
		] ),
		new wb.datamodel.MultiTermSet( [
			new wb.datamodel.MultiTerm( 'de', ['de-alias1', 'de-alias2'] ),
			new wb.datamodel.MultiTerm( 'en', ['en-alias1'] )
		] )
	]
];

QUnit.test( 'Constructor (positive)', function( assert ) {

	/**
	 * @param {Object} assert
	 * @param {string} term
	 * @param {wikibase.datamodel.Set} set
	 */
	function checkGetters( assert, term, set ) {
		var languageCodes = set.getKeys(),
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
			var expectedItem = set.getItemByKey( languageCodes[j] );

			assert.ok(
				fingerprint[functionNames[term][1]]( expectedItem ),
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

	for( var i = 0; i < testSets.length; i++ ) {
		var fingerprint = new wb.datamodel.Fingerprint(
			testSets[i][0], testSets[i][1], testSets[i][2]
		);

		assert.ok(
			fingerprint instanceof wb.datamodel.Fingerprint,
			'Test set #' + i +': Instantiated Fingerprint.'
		);

		var sets = {
				labels: testSets[i][0],
				descriptions: testSets[i][1],
				aliases: testSets[i][2]
			};

		for( var term in sets ) {
			checkGetters( assert, term, sets[term] );
		}
	}
} );

QUnit.test( 'Constructor (negative)', function( assert ) {
	var negativeTestSets = [
		['string', new wb.datamodel.TermSet(), new wb.datamodel.MultiTermSet()],
		[new wb.datamodel.TermSet(), 'string', new wb.datamodel.MultiTermSet()],
		[new wb.datamodel.TermSet(), new wb.datamodel.TermSet(), 'string']
	];

	/**
	 * @param {wikibase.datamodel.TermSet} labels
	 * @param {wikibase.datamodel.TermSet} descriptions
	 * @param {wikibase.datamodel.MultiTermSet} aliasGroups
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
	var fingerprint = new wb.datamodel.Fingerprint(),
		label = new wb.datamodel.Term( 'de', 'de-label' );

	assert.ok(
		!fingerprint.hasLabel( label ),
		'Verified fingerprint not featuring the label that will be added.'
	);

	fingerprint.setLabel( label );

	assert.ok(
		fingerprint.hasLabel( label ),
		'Set label.'
	);
} );

QUnit.test( 'removeLabel()', function( assert ) {
	var label = new wb.datamodel.Term( 'de', 'de-label' ),
		fingerprint = new wb.datamodel.Fingerprint( new wb.datamodel.TermSet( [label] ) );

	assert.ok(
		fingerprint.hasLabel( label ),
		'Verified fingerprint featuring the label to be removed.'
	);

	fingerprint.removeLabel( label );

	assert.ok(
		!fingerprint.hasLabel( label ),
		'Removed label.'
	);
} );

QUnit.test( 'removeLabelFor()', function( assert ) {
	var label = new wb.datamodel.Term( 'de', 'de-label' ),
		fingerprint = new wb.datamodel.Fingerprint( new wb.datamodel.TermSet( [label] ) );

	assert.ok(
		fingerprint.hasLabel( label ),
		'Verified fingerprint featuring the label to be removed.'
	);

	fingerprint.removeLabelFor( label.getLanguageCode() );

	assert.ok(
		!fingerprint.hasLabel( label ),
		'Removed label.'
	);
} );

QUnit.test( 'setDescription()', function( assert ) {
	var fingerprint = new wb.datamodel.Fingerprint(),
		description = new wb.datamodel.Term( 'de', 'de-description' );

	assert.ok(
		!fingerprint.hasDescription( description ),
		'Verified fingerprint not featuring the description that will be added.'
	);

	fingerprint.setDescription( description );

	assert.ok(
		fingerprint.hasDescription( description ),
		'Set description.'
	);
} );

QUnit.test( 'removeDescription()', function( assert ) {
	var description = new wb.datamodel.Term( 'de', 'de-description' ),
		fingerprint = new wb.datamodel.Fingerprint(
			null,
			new wb.datamodel.TermSet( [description] )
		);

	assert.ok(
		fingerprint.hasDescription( description ),
		'Verified fingerprint featuring the description to be removed.'
	);

	fingerprint.removeDescription( description );

	assert.ok(
		!fingerprint.hasDescription( description ),
		'Removed description.'
	);
} );

QUnit.test( 'removeDescriptionFor()', function( assert ) {
	var description = new wb.datamodel.Term( 'de', 'de-description' ),
		fingerprint = new wb.datamodel.Fingerprint(
			null,
			new wb.datamodel.TermSet( [description] )
		);

	assert.ok(
		fingerprint.hasDescription( description ),
		'Verified fingerprint featuring the description to be removed.'
	);

	fingerprint.removeDescriptionFor( description.getLanguageCode() );

	assert.ok(
		!fingerprint.hasDescription( description ),
		'Removed description.'
	);
} );

QUnit.test( 'setAliases()', function( assert ) {
	var fingerprint = new wb.datamodel.Fingerprint(),
		deAliases = new wb.datamodel.MultiTerm( 'de', ['de-alias'] ),
		enAliases = new wb.datamodel.MultiTerm( 'en', ['en-alias'] ),
		aliases = new wb.datamodel.MultiTermSet( [enAliases] );

	assert.ok(
		!fingerprint.hasAliases( deAliases ),
		'Verified fingerprint not featuring the aliases that will be added.'
	);

	fingerprint.setAliases( deAliases );

	assert.ok(
		fingerprint.hasAliases( deAliases ),
		'Set aliases passing a MultiTerm object.'
	);

	assert.ok(
		!fingerprint.hasAliases( enAliases ),
		'Verified fingerprint not featuring the aliases that will be added.'
	);

	fingerprint.setAliases( aliases );

	assert.ok(
		fingerprint.hasAliases( enAliases ),
		'Set aliases passing a MultiTermSet.'
	);
} );

QUnit.test( 'removeAliases()', function( assert ) {
	var aliases = new wb.datamodel.MultiTerm( 'de', ['de-alias'] ),
		fingerprint = new wb.datamodel.Fingerprint(
			null,
			null,
			new wb.datamodel.MultiTermSet( [aliases] )
		);

	assert.ok(
		fingerprint.hasAliases( aliases ),
		'Verified fingerprint featuring the aliases to be removed.'
	);

	fingerprint.removeAliases( aliases );

	assert.ok(
		!fingerprint.hasAliases( aliases ),
		'Removed aliases.'
	);
} );

QUnit.test( 'removeAliasesFor()', function( assert ) {
	var aliases = new wb.datamodel.MultiTerm( 'de', ['de-alias'] ),
		fingerprint = new wb.datamodel.Fingerprint(
			null,
			null,
			new wb.datamodel.MultiTermSet( [aliases] )
		);

	assert.ok(
		fingerprint.hasAliases( aliases ),
		'Verified fingerprint featuring the aliases to be removed.'
	);

	fingerprint.removeAliasesFor( aliases.getLanguageCode() );

	assert.ok(
		!fingerprint.hasAliases( aliases ),
		'Removed aliases.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.ok(
		( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermSet(),
			new wb.datamodel.TermSet(),
			new wb.datamodel.MultiTermSet()
		) ).isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	assert.ok(
		!( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermSet( [new wb.datamodel.Term( 'en', 'en-string' )] ),
			new wb.datamodel.TermSet(),
			new wb.datamodel.MultiTermSet()
		) ).isEmpty(),
		'FALSE when there is a label.'
	);

	assert.ok(
		!( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermSet(),
			new wb.datamodel.TermSet( [new wb.datamodel.Term( 'en', 'en-string' )] ),
			new wb.datamodel.MultiTermSet()
		) ).isEmpty(),
		'FALSE when there is a description.'
	);

	assert.ok(
		!( new wb.datamodel.Fingerprint(
			new wb.datamodel.TermSet(),
			new wb.datamodel.TermSet(),
			new wb.datamodel.MultiTermSet( [new wb.datamodel.MultiTerm( 'en', ['en-string'] )] )
		) ).isEmpty(),
		'FALSE when there is an alias.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
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

}( wikibase, QUnit, jQuery ) );
