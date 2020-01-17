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
	const blockId = 1,
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
			} as any,
		};
		const errorBlockedOnClient: BlockReason = {
			type: PageNotEditable.BLOCKED_ON_CLIENT_PAGE,
			info: {
				blockedById,
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
			},
			store,
		} );

		expect( wrapper.findAll( ErrorPermissionInfo ).length ).toBe( permissionErrors.length );
	} );

	it.each( [
		[
			{
				type: PageNotEditable.UNKNOWN,
				info: {} as any,
			} as UnknownReason,

			MessageKeys.PERMISSIONS_ERROR_UNKNOWN_HEADING,
			[ ],
			MessageKeys.PERMISSIONS_ERROR_UNKNOWN_BODY,
			[ ],
		],
		[
			{
				type: PageNotEditable.ITEM_FULLY_PROTECTED,
			} as ProtectedReason,

			MessageKeys.PERMISSIONS_PROTECTED_HEADING,
			[
				'http://localhost/wiki/Project:Page_protection_policy',
				'http://localhost/wiki/Project:Administrators',
			],
			MessageKeys.PERMISSIONS_PROTECTED_BODY,
			[
				'http://localhost/wiki/Project:Page_protection_policy',
				'http://localhost/wiki/Project:Edit_warring',
				`http://localhost/wiki/Special:Log/protect?page=${entityTitle}`,
				`http://localhost/wiki/Talk:${entityTitle}`,
			],
		],
		[
			{
				type: PageNotEditable.ITEM_SEMI_PROTECTED,
			} as ProtectedReason,

			MessageKeys.PERMISSIONS_SEMI_PROTECTED_HEADING,
			[
				'http://localhost/wiki/Project:Page_protection_policy',
				'http://localhost/wiki/Project:Autoconfirmed_users',
			],
			MessageKeys.PERMISSIONS_SEMI_PROTECTED_BODY,
			[
				`http://localhost/wiki/Special:Log/protect?page=${entityTitle}`,
				`http://localhost/wiki/Talk:${entityTitle}`,
			],
		],
		[
			{
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
			} as BlockReason,
			MessageKeys.PERMISSIONS_BLOCKED_ON_CLIENT_HEADING,
			[ ],
			MessageKeys.PERMISSIONS_BLOCKED_ON_CLIENT_BODY,
			[ blockedBy,
				blockReason,
				'', // reserved for currentIP
				blockedBy,
				blockId,
				blockExpiry,
				'', // reserved for intended blockee
				blockedTimestamp,
			],
		],
		[
			{
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
			} as BlockReason,
			MessageKeys.PERMISSIONS_BLOCKED_ON_REPO_HEADING,
			[ ],
			MessageKeys.PERMISSIONS_BLOCKED_ON_REPO_BODY,
			[ blockedBy,
				blockReason,
				'', // reserved for currentIP
				blockedBy,
				blockId,
				blockExpiry,
				'', // reserved for intended blockee
				blockedTimestamp,
				'http://localhost/wiki/Project:Administrators',
			],
		],
	] )( '%#: interpolates message with correct key and parameters', ( error: MissingPermissionsError,
		header,
		headerParams,
		body,
		bodyParams ) => {
		// The info object in the interfaces allows for string number or boolean,
		// but $messages.get() accepts only string values
		// Better solutions are welcome.
		headerParams = headerParams.map( ( param: any ) => param.toString() );
		bodyParams = bodyParams.map( ( param: any ) => param.toString() );
		const messageGet = jest.fn( ( key ) => key );
		const repoRouterFactory = function ( errorType: PageNotEditable ): any {
			const fn = jest.fn();
			switch ( errorType ) {
				case PageNotEditable.ITEM_FULLY_PROTECTED:
					fn
						.mockReturnValueOnce( 'http://localhost/wiki/Project:Page_protection_policy' )
						.mockReturnValueOnce( 'http://localhost/wiki/Project:Administrators' )
						.mockReturnValueOnce( 'http://localhost/wiki/Project:Page_protection_policy' )
						.mockReturnValueOnce( 'http://localhost/wiki/Project:Edit_warring' )
						.mockReturnValueOnce( `http://localhost/wiki/Special:Log/protect?page=${entityTitle}` )
						.mockReturnValueOnce( `http://localhost/wiki/Talk:${entityTitle}` );
					break;
				case PageNotEditable.ITEM_SEMI_PROTECTED:
					fn
						.mockReturnValueOnce( 'http://localhost/wiki/Project:Page_protection_policy' )
						.mockReturnValueOnce( 'http://localhost/wiki/Project:Autoconfirmed_users' )
						.mockReturnValueOnce( `http://localhost/wiki/Special:Log/protect?page=${entityTitle}` )
						.mockReturnValueOnce( `http://localhost/wiki/Talk:${entityTitle}` );
					break;
				case PageNotEditable.BLOCKED_ON_REPO_ITEM:
					fn
						.mockReturnValueOnce( 'http://localhost/wiki/Project:Administrators' );
					break;
			}
			return fn;
		};
		const $repoRouter: MediaWikiRouter = {
			getPageUrl: repoRouterFactory( error.type ),
		};
		const $clientRouter: MediaWikiRouter = {
			getPageUrl: repoRouterFactory( error.type ),
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
				$clientRouter,
			},
			store,
		} );

		expect( messageGet ).toHaveBeenNthCalledWith( 2, header, ...headerParams );
		expect( messageGet ).toHaveBeenNthCalledWith( 3, body, ...bodyParams );
	} );

	it.each( [
		[
			{
				type: PageNotEditable.PAGE_CASCADE_PROTECTED,
				info: {
					pages: pagesCausingCascadeProtection,
				},
			} as CascadeProtectedReason,
			MessageKeys.PERMISSIONS_PAGE_CASCADE_PROTECTED_HEADING,
			[ ],
			MessageKeys.PERMISSIONS_PAGE_CASCADE_PROTECTED_BODY,
			[
				pagesCausingCascadeProtection.length,
				// eslint-disable-next-line max-len
				`<ul><li><a href="http://localhost/wiki/${pagesCausingCascadeProtection[ 0 ]}">${pagesCausingCascadeProtection[ 0 ]}</a></li><li><a href="http://localhost/wiki/${pagesCausingCascadeProtection[ 1 ]}">${pagesCausingCascadeProtection[ 1 ]}</a></li><li><a href="http://localhost/wiki/${pagesCausingCascadeProtection[ 2 ]}">${pagesCausingCascadeProtection[ 2 ]}</a></li></ul>`,
			],
		],
		[
			{
				type: PageNotEditable.ITEM_CASCADE_PROTECTED,
				info: {
					pages: pagesCausingCascadeProtection,
				},
			} as CascadeProtectedReason,
			MessageKeys.PERMISSIONS_CASCADE_PROTECTED_HEADING,
			[ ],
			MessageKeys.PERMISSIONS_CASCADE_PROTECTED_BODY,
			[
				pagesCausingCascadeProtection.length,
				// eslint-disable-next-line max-len
				`<ul><li><a href="http://localhost/wiki/${pagesCausingCascadeProtection[ 0 ]}">${pagesCausingCascadeProtection[ 0 ]}</a></li><li><a href="http://localhost/wiki/${pagesCausingCascadeProtection[ 1 ]}">${pagesCausingCascadeProtection[ 1 ]}</a></li><li><a href="http://localhost/wiki/${pagesCausingCascadeProtection[ 2 ]}">${pagesCausingCascadeProtection[ 2 ]}</a></li></ul>`,
			],
		],
	] )( 'interpolates correct message for page cascade-protected', ( error: MissingPermissionsError,
		header,
		headerParams,
		body,
		bodyParams ) => {
		headerParams = headerParams.map( ( param: any ) => param.toString() );
		bodyParams = bodyParams.map( ( param: any ) => param.toString() );
		const router = jest.fn()
			.mockReturnValueOnce( 'http://localhost/wiki/Page_One' )
			.mockReturnValueOnce( 'http://localhost/wiki/Page_Two' )
			.mockReturnValueOnce( 'http://localhost/wiki/Page_Three' );
		const messageGet = jest.fn( ( key ) => key );
		const $repoRouter: MediaWikiRouter = {
			getPageUrl: router,
		};
		const $clientRouter: MediaWikiRouter = {
			getPageUrl: router,
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
				$clientRouter,
			},
			store,
		} );

		calledWithHTMLElement( messageGet, 2, 2 );
		expect( messageGet ).toHaveBeenNthCalledWith( 2, header, ...headerParams );
		expect( messageGet ).toHaveBeenNthCalledWith( 3, body, ...bodyParams );
	} );

	it( 'uses the correct router to build page urls on repo', () => {
		const messageGet = jest.fn( ( key ) => key );
		const error: CascadeProtectedReason = {
			type: PageNotEditable.ITEM_CASCADE_PROTECTED,
			info: {
				pages: pagesCausingCascadeProtection,
			},
		};
		const $repoRouter: MediaWikiRouter = {
			getPageUrl: jest.fn(),
		};
		const $clientRouter: MediaWikiRouter = {
			getPageUrl: jest.fn(),
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
				$clientRouter,
			},
			store,
		} );
		expect( $repoRouter.getPageUrl ).toHaveBeenCalledTimes( 3 );
		expect( $clientRouter.getPageUrl ).not.toHaveBeenCalled();
	} );

	it( 'uses the correct router to build page urls on client', () => {
		const messageGet = jest.fn( ( key ) => key );
		const error: CascadeProtectedReason = {
			type: PageNotEditable.PAGE_CASCADE_PROTECTED,
			info: {
				pages: pagesCausingCascadeProtection,
			},
		};
		const $repoRouter: MediaWikiRouter = {
			getPageUrl: jest.fn(),
		};
		const $clientRouter: MediaWikiRouter = {
			getPageUrl: jest.fn(),
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
				$clientRouter,
			},
			store,
		} );
		expect( $clientRouter.getPageUrl ).toHaveBeenCalledTimes( 3 );
		expect( $repoRouter.getPageUrl ).not.toHaveBeenCalled();
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
