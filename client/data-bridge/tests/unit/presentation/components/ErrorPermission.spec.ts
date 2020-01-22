import ErrorPermission from '@/presentation/components/ErrorPermission.vue';
import ErrorPermissionInfo from '@/presentation/components/ErrorPermissionInfo.vue';
import {
	MissingPermissionsError,
	PageNotEditable,
	ProtectedReason,
	BlockReason,
	UnknownReason,
	CascadeProtectedReason,
} from '@/definitions/data-access/BridgePermissionsRepository';
import MessageKeys from '@/definitions/MessageKeys';
import Application from '@/store/Application';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import { shallowMount, createLocalVue } from '@vue/test-utils';
import Vuex, { Store } from 'vuex';
import Mock = jest.Mock;

const localVue = createLocalVue();
localVue.use( Vuex );

const entityTitle = 'Q42';

function unusedRouter( wiki: 'repo'|'client' ): MediaWikiRouter {
	return {
		getPageUrl( _title: string, _params?: Record<string, unknown> ): string {
			throw new Error( `The ${wiki} router should not be used in this test` );
		},
	};
}

/**
 * Assert that a mock has been called with an `HTMLElement` as the *x*th argument of the *y*th call,
 * and then replace the argument with its `outerHTML`, to simplify subsequent assertions.
 *
 * Usage example:
 *
 * ```
 * const mock = jest.fn();
 * mock( 'x', 'y', 'z' );
 * mock( 'a', document.createElement( 'b' ), 'c' );
 * calledWithHTMLElement( mock, 1, 1 );
 * expect( mock ).toHaveBeenNthCalledWith( 1, 'x', 'y', 'z' );
 * expect( mock ).toHaveBeenNthCalledWith( 2, 'a', '<b></b>', 'c' );
 * ```
 *
 * @param mock any mock function
 * @param callNum 0-indexed
 * @param argumentNum 0-indexed
 */
function calledWithHTMLElement( mock: Mock, callNum: number, argumentNum: number ): void {
	const call = mock.mock.calls[ callNum ];
	expect( call[ argumentNum ] ).toBeInstanceOf( HTMLElement );
	call[ argumentNum ] = call[ argumentNum ].outerHTML;
}

describe( 'ErrorPermission', () => {
	const blockId = 10,
		blockedBy = 'John Doe',
		blockedById = 1,
		blockReason = 'Bad behavior',
		blockedTimestamp = '2019-12-12T12:12:12',
		blockExpiry = '2020-01-12T12:12:12',
		blockPartial = false,
		pagesCausingCascadeProtection = [ 'Page_One', 'Page_Two', 'Page_Three' ];

	it( 'passes properties to ErrorPermissionInfo', () => {
		const messageGet = jest.fn( ( key ) => key );
		const store = new Store<Partial<Application>>( {
			state: {
				entityTitle,
			},
		} );
		const error: ProtectedReason = {
			type: PageNotEditable.ITEM_FULLY_PROTECTED,
			info: {
				right: '',
			},
		};
		const permissionErrors: MissingPermissionsError[] = [ error ];
		const wrapper = shallowMount( ErrorPermission, {
			localVue,
			propsData: {
				permissionErrors,
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			store,
		} );

		expect( wrapper.find( ErrorPermissionInfo ).props( 'messageHeader' ) )
			.toBe( MessageKeys.PERMISSIONS_PROTECTED_HEADING );
		expect( wrapper.find( ErrorPermissionInfo ).props( 'messageBody' ) )
			.toBe( MessageKeys.PERMISSIONS_PROTECTED_BODY );
	} );

	it( 'shows a list of all errors', () => {
		const messageGet = jest.fn( ( key ) => key );
		const store = new Store<Partial<Application>>( {
			state: {
				entityTitle,
			},
		} );
		const errorProtected: ProtectedReason = {
			type: PageNotEditable.ITEM_FULLY_PROTECTED,
			info: {
				right: '',
			},
		};
		const errorBlockedOnRepo: BlockReason = {
			type: PageNotEditable.BLOCKED_ON_REPO_ITEM,
			info: {
				blockedById,
				blockId,
			} as any,
		};
		const errorBlockedOnClient: BlockReason = {
			type: PageNotEditable.BLOCKED_ON_CLIENT_PAGE,
			info: {
				blockedById,
				blockId,
			} as any,
		};
		const permissionErrors: MissingPermissionsError[] = [
			errorProtected,
			errorBlockedOnRepo,
			errorBlockedOnClient,
		];
		const wrapper = shallowMount( ErrorPermission, {
			localVue,
			propsData: {
				permissionErrors,
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
				$repoRouter: {
					getPageUrl( title: string, _params?: Record<string, unknown> ) {
						return `https://repo.wiki.example/wiki/${title}`;
					},
				},
				$clientRouter: {
					getPageUrl( title: string, _params?: Record<string, unknown> ) {
						return `https://client.wiki.example/wiki/${title}`;
					},
				},
			},
			store,
		} );

		expect( wrapper.findAll( ErrorPermissionInfo ) ).toHaveLength( permissionErrors.length );
	} );

	it( 'interpolates correct message for unknown error', () => {
		const error: UnknownReason = {
			type: PageNotEditable.UNKNOWN,
			info: {} as any,
		};
		const messageGet = jest.fn( ( key ) => key );
		const store = new Store<Partial<Application>>( {
			state: {
				entityTitle,
			},
		} );

		shallowMount( ErrorPermission, {
			localVue,
			propsData: {
				permissionErrors: [ error ],
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
				$repoRouter: unusedRouter( 'repo' ),
				$clientRouter: unusedRouter( 'client' ),
			},
			store,
		} );

		expect( messageGet ).toHaveBeenNthCalledWith( 2, MessageKeys.PERMISSIONS_ERROR_UNKNOWN_HEADING );
		expect( messageGet ).toHaveBeenNthCalledWith( 3, MessageKeys.PERMISSIONS_ERROR_UNKNOWN_BODY );
	} );

	it( 'interpolates correct message for protected item', () => {
		const error: ProtectedReason = {
			type: PageNotEditable.ITEM_FULLY_PROTECTED,
		} as ProtectedReason;
		const header = MessageKeys.PERMISSIONS_PROTECTED_HEADING;
		const headerParams = [
			'http://localhost/wiki/Project:Page_protection_policy',
			'http://localhost/wiki/Project:Administrators',
		];
		const body = MessageKeys.PERMISSIONS_PROTECTED_BODY;
		const bodyParams = [
			'http://localhost/wiki/Project:Page_protection_policy',
			'http://localhost/wiki/Project:Edit_warring',
			`http://localhost/wiki/Special:Log/protect?page=${entityTitle}`,
			`http://localhost/wiki/Talk:${entityTitle}`,
		];
		const messageGet = jest.fn( ( key ) => key );
		const repoRouterGetPageUrl = jest.fn();
		repoRouterGetPageUrl
			.mockReturnValueOnce( 'http://localhost/wiki/Project:Page_protection_policy' )
			.mockReturnValueOnce( 'http://localhost/wiki/Project:Administrators' )
			.mockReturnValueOnce( 'http://localhost/wiki/Project:Page_protection_policy' )
			.mockReturnValueOnce( 'http://localhost/wiki/Project:Edit_warring' )
			.mockReturnValueOnce( `http://localhost/wiki/Special:Log/protect?page=${entityTitle}` )
			.mockReturnValueOnce( `http://localhost/wiki/Talk:${entityTitle}` );
		const $repoRouter: MediaWikiRouter = {
			getPageUrl: repoRouterGetPageUrl,
		};
		const store = new Store<Partial<Application>>( {
			state: {
				entityTitle,
			},
		} );

		shallowMount( ErrorPermission, {
			localVue,
			propsData: {
				permissionErrors: [ error ],
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
				$repoRouter,
				$clientRouter: unusedRouter( 'client' ),
			},
			store,
		} );

		expect( messageGet ).toHaveBeenNthCalledWith( 2, header, ...headerParams );
		expect( messageGet ).toHaveBeenNthCalledWith( 3, body, ...bodyParams );
	} );

	it( 'interpolates correct message for semi-protected item', () => {
		const error: ProtectedReason = {
			type: PageNotEditable.ITEM_SEMI_PROTECTED,
		} as ProtectedReason;
		const header = MessageKeys.PERMISSIONS_SEMI_PROTECTED_HEADING;
		const headerParams = [
			'http://localhost/wiki/Project:Page_protection_policy',
			'http://localhost/wiki/Project:Autoconfirmed_users',
		];
		const body = MessageKeys.PERMISSIONS_SEMI_PROTECTED_BODY;
		const bodyParams = [
			`http://localhost/wiki/Special:Log/protect?page=${entityTitle}`,
			`http://localhost/wiki/Talk:${entityTitle}`,
		];
		const messageGet = jest.fn( ( key ) => key );
		const repoRouterGetPageUrl = jest.fn();
		repoRouterGetPageUrl
			.mockReturnValueOnce( 'http://localhost/wiki/Project:Page_protection_policy' )
			.mockReturnValueOnce( 'http://localhost/wiki/Project:Autoconfirmed_users' )
			.mockReturnValueOnce( `http://localhost/wiki/Special:Log/protect?page=${entityTitle}` )
			.mockReturnValueOnce( `http://localhost/wiki/Talk:${entityTitle}` );
		const $repoRouter: MediaWikiRouter = {
			getPageUrl: repoRouterGetPageUrl,
		};
		const store = new Store<Partial<Application>>( {
			state: {
				entityTitle,
			},
		} );

		shallowMount( ErrorPermission, {
			localVue,
			propsData: {
				permissionErrors: [ error ],
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
				$repoRouter,
				$clientRouter: unusedRouter( 'client' ),
			},
			store,
		} );

		expect( messageGet ).toHaveBeenNthCalledWith( 2, header, ...headerParams );
		expect( messageGet ).toHaveBeenNthCalledWith( 3, body, ...bodyParams );
	} );

	it( 'interpolates correct message for user blocked on client', () => {
		const error: BlockReason = {
			type: PageNotEditable.BLOCKED_ON_CLIENT_PAGE,
			info: {
				blockId,
				blockedBy,
				blockedById,
				blockReason,
				blockedTimestamp,
				blockExpiry,
				blockPartial,
			},
		};
		const body = MessageKeys.PERMISSIONS_BLOCKED_ON_CLIENT_BODY;
		const bodyParams = [
			`<a href="http://localhost/wiki/Special:Redirect/user/${blockedById}"><bdi>${blockedBy}</bdi></a>`,
			blockReason,
			'', // reserved for currentIP
			`<bdi>${blockedBy}</bdi>`,
			blockId,
			blockExpiry,
			'', // reserved for intended blockee
			blockedTimestamp,
		].map( ( param: string|number ) => param.toString() );
		const messageGet = jest.fn( ( key ) => key );
		const clientRouterGetPageUrl = jest.fn();
		clientRouterGetPageUrl
			.mockReturnValueOnce( `http://localhost/wiki/Special:Redirect/user/${blockedById}` );
		const $clientRouter: MediaWikiRouter = {
			getPageUrl: clientRouterGetPageUrl,
		};
		const store = new Store<Partial<Application>>( {
			state: {
				entityTitle,
			},
		} );

		shallowMount( ErrorPermission, {
			localVue,
			propsData: {
				permissionErrors: [ error ],
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
				$repoRouter: unusedRouter( 'repo' ),
				$clientRouter,
			},
			store,
		} );

		calledWithHTMLElement( messageGet, 2, 1 );
		calledWithHTMLElement( messageGet, 2, 4 );

		expect( messageGet ).toHaveBeenNthCalledWith( 2, MessageKeys.PERMISSIONS_BLOCKED_ON_CLIENT_HEADING );
		expect( messageGet ).toHaveBeenNthCalledWith( 3, body, ...bodyParams );
	} );

	it( 'interpolates correct message for user blocked on repo', () => {
		const error: BlockReason = {
			type: PageNotEditable.BLOCKED_ON_REPO_ITEM,
			info: {
				blockId,
				blockedBy,
				blockedById,
				blockReason,
				blockedTimestamp,
				blockExpiry,
				blockPartial,
			},
		};
		const body = MessageKeys.PERMISSIONS_BLOCKED_ON_REPO_BODY;
		const bodyParams = [
			`<a href="http://localhost/wiki/Special:Redirect/user/${blockedById}"><bdi>${blockedBy}</bdi></a>`,
			blockReason,
			'', // reserved for currentIP
			`<bdi>${blockedBy}</bdi>`,
			blockId,
			blockExpiry,
			'', // reserved for intended blockee
			blockedTimestamp,
			'http://localhost/wiki/Project:Administrators',
		].map( ( param: string|number ) => param.toString() );
		const messageGet = jest.fn( ( key ) => key );
		const repoRouterGetPageUrl = jest.fn();
		repoRouterGetPageUrl
			.mockReturnValueOnce( `http://localhost/wiki/Special:Redirect/user/${blockedById}` )
			.mockReturnValueOnce( 'http://localhost/wiki/Project:Administrators' );
		const $repoRouter: MediaWikiRouter = {
			getPageUrl: repoRouterGetPageUrl,
		};
		const store = new Store<Partial<Application>>( {
			state: {
				entityTitle,
			},
		} );

		shallowMount( ErrorPermission, {
			localVue,
			propsData: {
				permissionErrors: [ error ],
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
				$repoRouter,
				$clientRouter: unusedRouter( 'client' ),
			},
			store,
		} );

		calledWithHTMLElement( messageGet, 2, 1 );
		calledWithHTMLElement( messageGet, 2, 4 );

		expect( messageGet ).toHaveBeenNthCalledWith( 2, MessageKeys.PERMISSIONS_BLOCKED_ON_REPO_HEADING );
		expect( messageGet ).toHaveBeenNthCalledWith( 3, body, ...bodyParams );
	} );

	it.each( [
		[
			'client',
			PageNotEditable.PAGE_CASCADE_PROTECTED,
			MessageKeys.PERMISSIONS_PAGE_CASCADE_PROTECTED_HEADING,
			MessageKeys.PERMISSIONS_PAGE_CASCADE_PROTECTED_BODY,
		],
		[
			'repo',
			PageNotEditable.ITEM_CASCADE_PROTECTED,
			MessageKeys.PERMISSIONS_CASCADE_PROTECTED_HEADING,
			MessageKeys.PERMISSIONS_CASCADE_PROTECTED_BODY,
		],
	] )( 'interpolates correct message for page cascade-protected on %s', (
		wiki: 'repo'|'client',
		type: typeof PageNotEditable.ITEM_CASCADE_PROTECTED
		| typeof PageNotEditable.PAGE_CASCADE_PROTECTED,
		header: MessageKeys,
		body: MessageKeys,
	) => {
		const error: CascadeProtectedReason = {
			type,
			info: {
				pages: pagesCausingCascadeProtection,
			},
		};
		const messageGet = jest.fn( ( key ) => key );
		const routerGetPageUrl = jest.fn();
		routerGetPageUrl
			.mockReturnValueOnce( 'http://localhost/wiki/Page_One' )
			.mockReturnValueOnce( 'http://localhost/wiki/Page_Two' )
			.mockReturnValueOnce( 'http://localhost/wiki/Page_Three' );
		const $router: MediaWikiRouter = {
			getPageUrl: routerGetPageUrl,
		};

		const store = new Store<Partial<Application>>( {
			state: {
				entityTitle,
			},
		} );

		shallowMount( ErrorPermission, {
			localVue,
			propsData: {
				permissionErrors: [ error ],
			},

			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
				$repoRouter: wiki === 'repo' ? $router : unusedRouter( 'repo' ),
				$clientRouter: wiki === 'client' ? $router : unusedRouter( 'client' ),
			},
			store,
		} );

		calledWithHTMLElement( messageGet, 2, 2 );

		expect( messageGet ).toHaveBeenNthCalledWith( 2, header );
		expect( messageGet ).toHaveBeenNthCalledWith(
			3,
			body,
			pagesCausingCascadeProtection.length.toString(),
			// eslint-disable-next-line max-len
			`<ul><li><a href="http://localhost/wiki/${pagesCausingCascadeProtection[ 0 ]}">${pagesCausingCascadeProtection[ 0 ]}</a></li><li><a href="http://localhost/wiki/${pagesCausingCascadeProtection[ 1 ]}">${pagesCausingCascadeProtection[ 1 ]}</a></li><li><a href="http://localhost/wiki/${pagesCausingCascadeProtection[ 2 ]}">${pagesCausingCascadeProtection[ 2 ]}</a></li></ul>`,
		);
	} );

	it( 'builds a talk page name based on the entity title', () => {
		const messageGet = jest.fn( ( key ) => key ),
			protectionPolicyUrl = 'Project:Page_protection_policy',
			autoconfirmedUsers = 'Autoconfirmed_users',
			entityTitle = 'Property:P18',
			propertyTalkUrl = 'http://localhost/wiki/Property_talk:P18',
			logUrl = `http://localhost/wiki/Special:Log/protect?page=${entityTitle}`;
		const $repoRouter: MediaWikiRouter = {
			getPageUrl: jest.fn()
				.mockReturnValueOnce( protectionPolicyUrl )
				.mockReturnValueOnce( autoconfirmedUsers )
				.mockReturnValueOnce( logUrl )
				.mockReturnValueOnce( propertyTalkUrl ),
		};
		const store = new Store<Partial<Application>>( {
			state: {
				entityTitle,
			},
		} );
		const error = {
			type: PageNotEditable.ITEM_SEMI_PROTECTED,
		};

		shallowMount( ErrorPermission, {
			localVue,
			propsData: {
				permissionErrors: [ error ],
			},
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
				$repoRouter,
			},
			store,
		} );

		expect( messageGet ).toHaveBeenNthCalledWith( 2,
			MessageKeys.PERMISSIONS_SEMI_PROTECTED_HEADING,
			protectionPolicyUrl,
			autoconfirmedUsers );

		expect( messageGet ).toHaveBeenNthCalledWith( 3,
			MessageKeys.PERMISSIONS_SEMI_PROTECTED_BODY,
			logUrl,
			propertyTalkUrl );
	} );
} );
