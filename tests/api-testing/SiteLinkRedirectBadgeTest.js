'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { requireExtensions } = require( './utils.js' );

describe( 'sitelink redirect behavior with badges', () => {
	let alice;
	let siteId;
	let goodArticleBadgeId;
	let sitelinkToRedirectBadgeId;
	let intentionalSitelinkToRedirectBadgeId;

	before( 'require extensions', requireExtensions( [
		'WikibaseRepository',
		'WikibaseClient',
	] ) );

	before( 'set up user', async () => {
		alice = await action.alice();
	} );

	before( 'get site ID', async () => {
		siteId = ( await action.getAnon().meta(
			'wikibase',
			{ wbprop: 'siteid' },
		) ).siteid;
	} );

	/**
	 * Create an item with the given label and data.
	 *
	 * @param {string} enLabel The English label.
	 * A unique suffix is automatically appended.
	 * @param {Object} [data] Any other item data.
	 * @return {Promise<string>} The item ID.
	 */
	async function createItem( enLabel, data = {} ) {
		if ( !data.labels ) {
			data.labels = {};
		}
		data.labels.en = { language: 'en', value: `${enLabel}-${utils.uniq()}` };

		const response = await alice.action( 'wbeditentity', {
			new: 'item',
			token: await alice.token( 'csrf' ),
			data: JSON.stringify( data ),
		}, 'POST' );
		return response.entity.id;
	}

	before( 'create badge items', async () => {
		goodArticleBadgeId = await createItem( 'good article' );
		sitelinkToRedirectBadgeId = await createItem( 'sitelink to redirect' );
		intentionalSitelinkToRedirectBadgeId = await createItem( 'intentional sitelink to redirect' );

		// set request headers for all requests; see repo/config/Wikibase.ci.php
		alice.req.set( 'X-Wikibase-CI-Badges',
			[ goodArticleBadgeId, sitelinkToRedirectBadgeId, intentionalSitelinkToRedirectBadgeId ]
				.join( ', ' ) );
		alice.req.set( 'X-Wikibase-CI-Redirect-Badges',
			[ sitelinkToRedirectBadgeId, intentionalSitelinkToRedirectBadgeId ]
				.join( ', ' ) );
	} );

	/**
	 * Create a regular page that can be used as the target of a redirect.
	 *
	 * @param {string} title The page title.
	 * A unique suffix is automatically appended,
	 * and the page is always a subpage of the Alice user page.
	 * @return {Promise<string>} The full title.
	 */
	async function createRedirectTarget( title ) {
		const fullTitle = `User:${alice.username}/${title}-${utils.uniq()}`;
		const response = await alice.edit( fullTitle, {
			text: 'Redirect target for SiteLinkRedirectBadgeTest',
			createonly: '1',
		} );
		return response.title;
	}

	/**
	 * Create a redirect to the given target page.
	 *
	 * @param {string} title The redirect page title.
	 * A unique suffix is automatically appended,
	 * and the page is always a subpage of the Alice user page.
	 * @param {string} target The full title of the target page,
	 * typically returned by {@link createRedirectTarget}.
	 * @return {Promise<string>} The full title.
	 */
	async function createRedirect( title, target ) {
		const fullTitle = `User:${alice.username}/${title}-${utils.uniq()}`;
		const response = await alice.edit( fullTitle, {
			text: `#REDIRECT [[${target}]]`,
			createonly: '1',
		} );
		return response.title;
	}

	const wbSetSiteLinkAction = {
		name: 'wbsetsitelink',
		expectActionSuccess: async ( itemId, redirectTargetTitle, badges ) => {
			return alice.action( 'wbsetsitelink', {
				id: itemId,
				linksite: siteId,
				linktitle: redirectTargetTitle,
				badges: badges,
				token: await alice.token( 'csrf' ),
			}, 'POST' );
		},
		expectActionError: async ( itemId, redirectTargetTitle, badges ) => {
			const params = {
				id: itemId,
				linksite: siteId,
				badges: badges,
				token: await alice.token( 'csrf' ),
			};
			if ( redirectTargetTitle !== null && redirectTargetTitle !== undefined ) {
				params.linktitle = redirectTargetTitle;
			}
			return alice.actionError( 'wbsetsitelink', params, 'POST' );
		},
	};

	const wbEditEntityAction = {
		name: 'wbeditentity',
		expectActionSuccess: async ( itemId, redirectTargetTitle, badges ) => {
			if ( typeof badges === 'string' ) {
				badges = [ badges ];
			}
			return alice.action( 'wbeditentity', {
				id: itemId,
				data: JSON.stringify( {
					sitelinks: {
						[ siteId ]: {
							site: siteId,
							title: redirectTargetTitle,
							badges: badges,
						},
					},
				} ),
				token: await alice.token( 'csrf' ),
			}, 'POST' );
		},
		expectActionError: async ( itemId, redirectTargetTitle, badges ) => {
			if ( typeof badges === 'string' ) {
				badges = [ badges ];
			}
			const dataPayload = {
				sitelinks: {
					[ siteId ]: {
						site: siteId,
						badges: badges,
					},
				},
			};
			if ( redirectTargetTitle !== null && redirectTargetTitle !== undefined ) {
				dataPayload.sitelinks[ siteId ].title = redirectTargetTitle;
			}
			return alice.actionError( 'wbeditentity', {
				id: itemId,
				data: JSON.stringify( dataPayload ),
				token: await alice.token( 'csrf' ),
			}, 'POST' );
		},
	};

	/* eslint-disable-next-line mocha/no-setup-in-describe */
	[ wbSetSiteLinkAction, wbEditEntityAction ].forEach( ( setSiteLinkEndpoint ) => {

		// Redirects are NOT allowed as separate sitelinks
		// if NO redirect badge is added in the same edit
		it( setSiteLinkEndpoint.name + ': disallows redirect sitelink without redirect badge', async () => {
			const redirectTarget = await createRedirectTarget( 'target' ),
				redirect = await createRedirect( 'redirect', redirectTarget ),
				item = await createItem( 'item where we try to add a sitelink to a redirect' ),
				itemLinkedToTarget = await createItem( 'item linked to redirect target', {
					sitelinks: {
						[ siteId ]: {
							site: siteId,
							title: redirectTarget,
							badges: [],
						},
					},
				} );

			const error = await setSiteLinkEndpoint.expectActionError(
				item,
				redirect,
				goodArticleBadgeId,
			);

			assert.strictEqual( error.code, 'failed-save' );
			const sitelinkConflict = error.messages.find( ( message ) =>
				message.name === 'wikibase-validator-sitelink-conflict-redirects-supported' );
			assert.isNotNull( sitelinkConflict );
			assert.include( sitelinkConflict.parameters[ 1 ], itemLinkedToTarget );
		} );

		// Redirects are allowed as separate sitelinks
		// if a redirect badge is added in the same edit
		it( setSiteLinkEndpoint.name + ': allows redirect sitelink with redirect badge', async () => {
			const redirectTarget = await createRedirectTarget( 'target' ),
				redirect = await createRedirect( 'redirect', redirectTarget ),
				item = await createItem( 'item where we add a sitelink to a redirect' );

			const response = await setSiteLinkEndpoint.expectActionSuccess(
				item,
				redirect,
				sitelinkToRedirectBadgeId,
			);
			assert.strictEqual( response.entity.sitelinks[ siteId ].title, redirect );
		} );

		// Redirect badges can be added to existing sitelinks even if they are redirects
		it( setSiteLinkEndpoint.name + ': allows adding redirect badge to redirect sitelink', async () => {
			const redirectTarget = await createRedirectTarget( 'target' ),
				redirect = await createRedirectTarget( 'redirect' ), // not yet a redirect
				item = await createItem( 'item with sitelink to redirect', {
					sitelinks: {
						[ siteId ]: {
							site: siteId,
							title: redirect,
							badges: [],
						},
					},
				} );

			await alice.edit( redirect, {
				text: `#REDIRECT [[${redirectTarget}]]`,
			} );

			const response = await setSiteLinkEndpoint.expectActionSuccess(
				item,
				redirect,
				sitelinkToRedirectBadgeId,
			);
			assert.strictEqual( response.entity.sitelinks[ siteId ].title, redirect );
			assert.deepEqual( response.entity.sitelinks[ siteId ].badges,
				[ sitelinkToRedirectBadgeId ] );
		} );

		// Redirect badges can NOT be removed from sitelinks
		// if the redirected target is used as a sitelink for a different Item
		it( setSiteLinkEndpoint.name + ': disallows removing redirect badge from redirect sitelink', async () => {
			const redirectTarget = await createRedirectTarget( 'target' ),
				redirect = await createRedirect( 'redirect', redirectTarget ),
				item = await createItem( 'item where we try to remove a redirect badge', {
					sitelinks: {
						[ siteId ]: {
							site: siteId,
							title: redirect,
							badges: [ sitelinkToRedirectBadgeId ],
						},
					},
				} ),
				itemLinkedToTarget = await createItem( 'item linked to redirect target', {
					sitelinks: {
						[ siteId ]: {
							site: siteId,
							title: redirectTarget,
							badges: [],
						},
					},
				} );

			const error = await setSiteLinkEndpoint.expectActionError( item, redirect, [] );

			assert.strictEqual( error.code, 'failed-save' );
			const sitelinkConflict = error.messages.find( ( message ) =>
				message.name === 'wikibase-validator-sitelink-conflict-redirects-supported' );
			assert.isNotNull( sitelinkConflict );
			assert.include( sitelinkConflict.parameters[ 1 ], itemLinkedToTarget );
		} );

		// A redirect badge can always be switched with another redirect badge in the same edit
		it( setSiteLinkEndpoint.name + ': allows replacing redirect badge with another', async () => {
			const redirectTarget = await createRedirectTarget( 'target' ),
				redirect = await createRedirect( 'redirect', redirectTarget ),
				item = await createItem( 'item where we replace a redirect badge', {
					sitelinks: {
						[ siteId ]: {
							site: siteId,
							title: redirect,
							badges: [ sitelinkToRedirectBadgeId ],
						},
					},
				} );

			const response = await setSiteLinkEndpoint.expectActionSuccess(
				item,
				redirect,
				intentionalSitelinkToRedirectBadgeId,
			);
			assert.strictEqual( response.entity.sitelinks[ siteId ].title, redirect );
			assert.deepEqual( response.entity.sitelinks[ siteId ].badges,
				[ intentionalSitelinkToRedirectBadgeId ] );
		} );

		// Redirect badges can NOT be removed from sitelinks
		// if the redirected target is used as a sitelink for a different Item
		// Not even if there is no title in the request
		it( setSiteLinkEndpoint.name + ': disallows removing redirect badge from redirect sitelink with no title provided', async () => {
			const redirectTarget = await createRedirectTarget( 'target' ),
				redirect = await createRedirect( 'redirect', redirectTarget ),
				item = await createItem( 'item where we try to remove a redirect badge', {
					sitelinks: {
						[ siteId ]: {
							site: siteId,
							title: redirect,
							badges: [ sitelinkToRedirectBadgeId ],
						},
					},
				} ),
				itemLinkedToTarget = await createItem( 'item linked to redirect target', {
					sitelinks: {
						[ siteId ]: {
							site: siteId,
							title: redirectTarget,
							badges: [],
						},
					},
				} );

			const error = await setSiteLinkEndpoint.expectActionError(
				item,
				null,
				goodArticleBadgeId,
			);
			assert.strictEqual( error.code, 'failed-save' );
			const sitelinkConflict = error.messages.find( ( message ) =>
				message.name === 'wikibase-validator-sitelink-conflict-redirects-supported' );
			assert.isNotNull( sitelinkConflict );
			assert.include( sitelinkConflict.parameters[ 1 ], itemLinkedToTarget );
		} );
	} );
} );
