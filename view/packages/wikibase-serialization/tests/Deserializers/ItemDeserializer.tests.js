/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'EntityDeserializer' );

	var datamodel = require( 'wikibase.datamodel' ),
		EntityDeserializer = require( '../../src/Deserializers/EntityDeserializer.js' );

	var defaults = [
		{
			fingerprint: {
				labels: { en: { language: 'en', value: 'label' } },
				descriptions: { en: { language: 'en', value: 'description' } },
				aliases: { en: [ { language: 'en', value: 'alias' } ] }
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
		}, {
			fingerprint: new datamodel.Fingerprint(
				new datamodel.TermMap( { en: new datamodel.Term( 'en', 'label' ) } ),
				new datamodel.TermMap( { en: new datamodel.Term( 'en', 'description' ) } ),
				new datamodel.MultiTermMap( { en: new datamodel.MultiTerm( 'en', [ 'alias' ] ) } )
			),
			statementGroupSet: new datamodel.StatementGroupSet( [
				new datamodel.StatementGroup( 'P1', new datamodel.StatementList( [
					new datamodel.Statement(
						new datamodel.Claim(
							new datamodel.PropertyNoValueSnak( 'P1' ), null, 'Q1$1'
						)
					)
				] ) )
			] )
		}
	];

	var testSets = [
		[
			$.extend( true, {}, defaults[0].fingerprint, {
				id: 'Q1',
				type: 'item',
				claims: defaults[0].statementGroupSet,
				sitelinks: {
					someSite: {
						site: 'someSite',
						title: 'page',
						badges: []
					}
				}
			} ),
			new datamodel.Item(
				'Q1',
				defaults[1].fingerprint,
				defaults[1].statementGroupSet,
				new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'someSite', 'page' ) ] )
			)
		]
	];

	QUnit.test( 'deserialize()', function( assert ) {
		assert.expect( 1 );
		var entityDeserializer = new EntityDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				entityDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

}() );
