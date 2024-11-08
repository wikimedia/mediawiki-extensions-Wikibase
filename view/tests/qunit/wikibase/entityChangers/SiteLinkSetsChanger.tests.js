/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.SiteLinkSetsChanger', QUnit.newMwEnvironment() );

	var datamodel = require( 'wikibase.datamodel' );
	var SUBJECT = wikibase.entityChangers.SiteLinkSetsChanger;
	var API_RESPONSE = {
		entity: {
			sitelinks: {
				siteId: {
					title: 'pageName'
				},
				lastrevid: 'lastrevid'
			}
		}
	};

	QUnit.test( 'is a function', ( assert ) => {
		assert.strictEqual(
			typeof SUBJECT,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', ( assert ) => {
		assert.true( new SUBJECT() instanceof SUBJECT );
	} );

	QUnit.test( 'save performs correct API call', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().resolve( API_RESPONSE ).promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			}, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		var done = assert.async();
		siteLinksChanger.save(
			new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'siteId', 'pageName' ) ] ),
			new datamodel.SiteLinkSet()
		).always( () => {
			assert.true( api.setSitelink.calledOnce );
			done();
		} );

	} );

	QUnit.test( 'save correctly handles API response', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().resolve( API_RESPONSE ).promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			}, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.save(
			new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'siteId', 'pageName' ) ] ),
			new datamodel.SiteLinkSet()
		).done( ( valueChangeResult ) => {
			assert.true( valueChangeResult.getSavedValue() instanceof datamodel.SiteLinkSet );
		} );
	} );

	QUnit.test( 'save correctly passes badges', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					sitelinks: {
						siteId: {
							title: 'pageName',
							badges: [ 'Q2' ]
						},
						lastrevid: 'lastrevid'
					}
				}
			} ).promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			}, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.save(
			new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'siteId', 'pageName', [ 'Q2' ] ) ] ),
			new datamodel.SiteLinkSet()
		).done( ( valueChangeResult ) => {
			assert.deepEqual( valueChangeResult.getSavedValue().getItemByKey( 'siteId' ).getBadges(), [ 'Q2' ] );
		} );
	} );

	QUnit.test( 'save correctly handles API failures', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			}, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		var done = assert.async();

		siteLinksChanger.save(
			new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'siteId', 'pageName' ) ] ),
			new datamodel.SiteLinkSet()
		).done( ( _valueChangeResult ) => {
			assert.true( false, 'save should have failed' );
			done();
		} )
		.fail( ( error ) => {
			assert.true( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.strictEqual( error.code, 'errorCode' );
			done();
		} );
	} );

	QUnit.test( 'save performs correct API call for removal', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().resolve( API_RESPONSE ).promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			}, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		var done = assert.async();
		siteLinksChanger.save(
			new datamodel.SiteLinkSet(),
			new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'siteId', 'pageName' ) ] )
		).always( () => {
			assert.true( api.setSitelink.calledOnce );
			done();
		} );
	} );

	QUnit.test( 'save correctly handles API response for removal', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					sitelinks: {
						siteId: {
							removed: ''
						},
						lastrevid: 'lastrevid'
					}
				}
			} ).promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			}, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.save(
			new datamodel.SiteLinkSet(),
			new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'siteId', 'pageName' ) ] )
		).done( ( valueChangeResult ) => {
			assert.true( valueChangeResult.getSavedValue() instanceof datamodel.SiteLinkSet );
		} );
	} );

	QUnit.test( 'save correctly passes badges for removal', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					sitelinks: {
						siteId: {
							removed: ''
						},
						lastrevid: 'lastrevid'
					}
				}
			} ).promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			}, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		return siteLinksChanger.save(
			new datamodel.SiteLinkSet(),
			new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'siteId', 'pageName', [ 'Q2' ] ) ] )
		).done( ( valueChangeResult ) => {
			assert.strictEqual( valueChangeResult.getSavedValue().getItemByKey( 'siteId' ), null );
		} );
	} );

	QUnit.test( 'save correctly handles API failures for removal', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			}, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		var done = assert.async();

		siteLinksChanger.save(
			new datamodel.SiteLinkSet(),
			new datamodel.SiteLinkSet( [ new datamodel.SiteLink( 'siteId', 'pageName' ) ] )
		).done( ( _valueChangeResult ) => {
			assert.true( false, 'save should have failed' );
			done();
		} )
		.fail( ( error ) => {
			assert.true( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.strictEqual( error.code, 'errorCode' );
			done();
		} );
	} );

}( wikibase ) );
