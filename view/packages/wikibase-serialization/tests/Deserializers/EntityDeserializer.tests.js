/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function() {
	'use strict';

	QUnit.module( 'EntityDeserializer' );
	var FingerprintDeserializer = require( '../../src/Deserializers/FingerprintDeserializer.js' ),
		datamodel = require( 'wikibase.datamodel' ),
		MockEntity = require( '../MockEntity.js' ),
		EntityDeserializer = require( '../../src/Deserializers/EntityDeserializer.js' ),
		Deserializer = require( '../../src/Deserializers/Deserializer.js' );

	/**
 * @extends Deserializer
 *
 * @constructor
 */
	var MockEntityDeserializer = util.inherit(
		'WbMockEntityDeserializer',
		Deserializer,
		{
			/**
			 * @inheritdoc
			 *
			 * @return {MockEntity}
			 */
			deserialize: function( serialization ) {
				if( serialization.type !== MockEntity.TYPE ) {
					throw new Error( 'Serialization does not resolve to a MockEntity' );
				}

				var fingerprintDeserializer = new FingerprintDeserializer();

				return new MockEntity(
					serialization.id,
					fingerprintDeserializer.deserialize( serialization )
				);
			}
		} );

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
				id: 'P1',
				type: 'property',
				datatype: 'string',
				claims: defaults[0].statementGroupSet
			} ),
			new datamodel.Property(
				'P1',
				'string',
				defaults[1].fingerprint,
				defaults[1].statementGroupSet
			)
		], [
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
		assert.expect( 2 );
		var entityDeserializer = new EntityDeserializer();

		for( var i = 0; i < testSets.length; i++ ) {
			assert.ok(
				entityDeserializer.deserialize( testSets[i][0] ).equals( testSets[i][1] ),
				'Test set #' + i + ': Deserializing successful.'
			);
		}
	} );

	QUnit.test( 'registerStrategy()', function( assert ) {
		assert.expect( 2 );
		var entityDeserializer = new EntityDeserializer();

		var mockEntitySerialization = $.extend( true, {
			id: 'i am an id',
			type: 'mock'
		}, defaults[0].fingerprint );

		assert.throws(
			function() {
				entityDeserializer.deserialize( mockEntitySerialization );
			},
			'Throwing an error when trying to deserialize an Entity no Deserializer is registered for.'
		);

		entityDeserializer.registerStrategy(
			new MockEntityDeserializer(),
			MockEntity.TYPE
		);

		var mockEntity = entityDeserializer.deserialize( mockEntitySerialization );

		assert.ok(
			mockEntity instanceof MockEntity,
			'Deserialized Entity after registering a proper Deserializer.'
		);
	} );

}() );
