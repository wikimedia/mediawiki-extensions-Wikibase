/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.serialization.ItemSerializer' );

var defaults = [
	{
		fingerprint: new wb.datamodel.Fingerprint(
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'label' ) } ),
			new wb.datamodel.TermMap( { en: new wb.datamodel.Term( 'en', 'description' ) } ),
			new wb.datamodel.MultiTermMap( { en: new wb.datamodel.MultiTerm( 'en', ['alias'] ) } )
		),
		statementGroupSet: new wb.datamodel.StatementGroupSet( [
			new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList( [
				new wb.datamodel.Statement(
					new wb.datamodel.Claim(
						new wb.datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1'
					)
				)
			] ) )
		] )
	}, {
		fingerprint: {
			labels: { en: { language: 'en', value: 'label' } },
			descriptions: { en: { language: 'en', value: 'description' } },
			aliases: { en: [{ language: 'en', value: 'alias' }] }
		},
		statementGroupSet: {
			P1: [ {
				id: 'Q1$1',
				mainsnak: {
					snaktype: 'novalue',
					property: 'P1'
				},
				type: 'statement',
				rank: 'normal'
			} ]
		}
	}
];

var testSets = [
	[
		new wb.datamodel.Item(
			'Q1',
			defaults[0].fingerprint,
			defaults[0].statementGroupSet,
			new wb.datamodel.SiteLinkSet( [new wb.datamodel.SiteLink( 'someSite', 'page' )] )
		),
		$.extend( true, {}, defaults[1].fingerprint, {
			id: 'Q1',
			type: 'item',
			claims: defaults[1].statementGroupSet,
			sitelinks: {
				someSite: {
					site: 'someSite',
					title: 'page',
					badges: []
				}
			}
		} )
	]
];

QUnit.test( 'serialize()', function( assert ) {
	var itemSerializer = new wb.serialization.ItemSerializer();

	for( var i = 0; i < testSets.length; i++ ) {
		assert.deepEqual(
			itemSerializer.serialize( testSets[i][0] ),
			testSets[i][1],
			'Test set #' + i + ': Serializing successful.'
		);
	}

	assert.throws(
		function() {
			itemSerializer.serialize(
				new wb.datamodel.Property(
					'P1',
					'string',
					defaults[0].fingerprint,
					defaults[0].statementGroupSet
				)
			);
		},
		'Throwing an error when trying to serialize an Entity not of type "item".'
	);
} );

}( jQuery, wikibase, QUnit ) );
