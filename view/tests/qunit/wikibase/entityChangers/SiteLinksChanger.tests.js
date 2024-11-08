/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.SiteLinksChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.SiteLinksChanger,
		TempUserWatcher = wikibase.entityChangers.TempUserWatcher,
		datamodel = require( 'wikibase.datamodel' );

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

	QUnit.test( 'setSiteLink performs correct API call', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			} },
			new datamodel.Item( 'Q1' )
		);

		siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', 'pageName' ), new TempUserWatcher() );

		assert.true( api.setSitelink.calledOnce );
	} );

	QUnit.test( 'setSiteLink correctly handles API response', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					sitelinks: {
						siteId: {
							title: 'pageName'
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

		return siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', 'pageName' ), new TempUserWatcher() )
		.done( ( savedSiteLink ) => {
			assert.true( savedSiteLink instanceof datamodel.SiteLink );
		} );
	} );

	QUnit.test( 'setSiteLink correctly handles TempUser info in API response', ( assert ) => {
		const targetUrl = 'https://wiki.example/test';
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					sitelinks: {
						siteId: {
							title: 'pageName'
						},
						lastrevid: 'lastrevid'
					}
				},
				tempusercreated: 'name',
				tempuserredirect: targetUrl
			} ).promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			}, setSitelinksRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		const tempUserWatcher = new TempUserWatcher();
		return siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', 'pageName' ), tempUserWatcher )
			.done( ( savedSiteLink ) => {
				assert.true( savedSiteLink instanceof datamodel.SiteLink );
				assert.strictEqual( targetUrl, tempUserWatcher.getRedirectUrl() );
			} );
	} );

	QUnit.test( 'setSiteLink correctly passes badges', ( assert ) => {
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

		return siteLinksChanger.setSiteLink(
			new datamodel.SiteLink( 'siteId', 'pageName', [ 'Q2' ] ),
			new TempUserWatcher()
		).done( ( savedSiteLink ) => {
			assert.deepEqual( savedSiteLink.getBadges(), [ 'Q2' ] );
		} );
	} );

	QUnit.test( 'setSiteLink correctly handles API failures', ( assert ) => {
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

		siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', 'pageName' ), new TempUserWatcher() )
		.done( ( savedSiteLink ) => {
			assert.true( false, 'setSiteLink should have failed' );
		} )
		.fail( ( error ) => {
			assert.true( error instanceof wb.api.RepoApiError, 'setSiteLink did not fail with a RepoApiError' );
			assert.strictEqual( error.code, 'errorCode' );
		} )
		.always( done );
	} );

	QUnit.test( 'setSiteLink performs correct API call for remove', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().promise() )
		};
		var siteLinksChanger = new SUBJECT(
			api,
			{ getSitelinksRevision: function () {
				return 0;
			} },
			new datamodel.Item( 'Q1' )
		);

		siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', '' ), new TempUserWatcher() );

		assert.true( api.setSitelink.calledOnce );
	} );

	QUnit.test( 'setSiteLink correctly handles API response for remove', ( assert ) => {
		var api = {
			setSitelink: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					sitelinks: {
						siteId: {
							title: 'pageName',
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

		return siteLinksChanger.setSiteLink( new datamodel.SiteLink( 'siteId', '' ), new TempUserWatcher() )
		.done( ( savedSiteLink ) => {
			assert.strictEqual( savedSiteLink, null );
		} );
	} );

}( wikibase ) );
