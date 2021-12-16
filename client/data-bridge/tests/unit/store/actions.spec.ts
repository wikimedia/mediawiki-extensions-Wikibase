import {
	DataValue,
	Reference,
	Statement,
} from '@wmde/wikibase-datamodel-types';
import SavingError from '@/data-access/error/SavingError';
import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import ApplicationStatus, { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import EditDecision from '@/definitions/EditDecision';
import EditFlow from '@/definitions/EditFlow';
import Application from '@/store/Application';
import { RootGetters } from '@/store/getters';
import PropertyDatatypeRepository from '@/definitions/data-access/PropertyDatatypeRepository';
import {
	BridgePermissionsRepository,
	MissingPermissionsError,
	PageNotEditable,
	CascadeProtectedReason,
} from '@/definitions/data-access/BridgePermissionsRepository';
import { inject } from 'vuex-smart-module';
import { RootActions } from '@/store/actions';
import AppInformation from '@/definitions/AppInformation';
import ApiErrors from '../../../src/data-access/error/ApiErrors';
import { ApiBadtokenError } from '../../../src/definitions/data-access/Api';
import newMockServiceContainer from '../services/newMockServiceContainer';
import newMockTracker from '../../util/newMockTracker';
import newApplicationState from './newApplicationState';
import { MainSnakPath } from '@/store/statements/MainSnakPath';
import { StatementState } from '@/store/statements/StatementState';
import MediaWikiPurge from '@/definitions/MediaWikiPurge';
import { getMockBridgeRepoConfig } from '../../util/mocks';
import { budge } from '../../util/timer';

describe( 'root/actions', () => {
	const defaultEntityId = 'Q32';
	const defaultPropertyId = 'P71';
	function newMockAppInformation( fields: Partial<AppInformation> = {} ): AppInformation {
		return {
			editFlow: EditFlow.SINGLE_BEST_VALUE,
			propertyId: defaultPropertyId,
			entityId: defaultEntityId,
			entityTitle: 'Entity Title',
			originalHref: '',
			pageTitle: '',
			client: { usePublish: true, issueReportingLink: '' },
			pageUrl: '',
			userName: null,
			...fields,
		};
	}

	describe( 'relaunchBridge', () => {
		it( 'commits three resets and then dispatches init', async () => {
			const information = newMockAppInformation();
			const commit = jest.fn();
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch,
			} );
			const entityModuleCommit = jest.fn();
			// @ts-ignore
			actions.entityModule = {
				commit: entityModuleCommit,
			};
			const statementModuleCommit = jest.fn();
			// @ts-ignore
			actions.statementModule = {
				commit: statementModuleCommit,
			};

			await actions.relaunchBridge( information );

			expect( commit ).toHaveBeenCalledWith( 'reset' );
			expect( entityModuleCommit ).toHaveBeenCalledWith( 'reset' );
			expect( statementModuleCommit ).toHaveBeenCalledWith( 'reset' );
			expect( dispatch ).toHaveBeenCalledWith( 'initBridge', information );
		} );
	} );

	describe( 'initBridge', () => {

		const wikibaseRepoConfigRepository = {
			getRepoConfiguration: jest.fn( () => Promise.resolve() ),
		};
		const editAuthorizationChecker: BridgePermissionsRepository = {
			canUseBridgeForItemAndPage: jest.fn().mockResolvedValue( [] ),
		};
		const propertyDatatypeRepository: PropertyDatatypeRepository = {
			getDataType: jest.fn().mockResolvedValue( 'string' ),
		};

		it(
			'commits relevant parts of AppInformation',
			async () => {
				const editFlow = EditFlow.SINGLE_BEST_VALUE;
				const propertyId = 'P42';
				const entityTitle = 'Douglas Adams';
				const originalHref = 'https://example.com/index.php?title=Item:Q42&uselang=en#P31';
				const pageTitle = 'Client_page';
				const pageUrl = 'https://client.example/wiki/Douglas_Adams';
				const client = { usePublish: true, issueReportingLink: '' };
				const information = newMockAppInformation( {
					editFlow,
					propertyId,
					entityTitle,
					originalHref,
					pageTitle,
					pageUrl,
					client,
				} );

				const commit = jest.fn();
				const actions = inject( RootActions, {
					commit,
					dispatch: jest.fn(),
				} );
				// @ts-ignore
				actions.store = {
					$services: newMockServiceContainer( {
						wikibaseRepoConfigRepository,
						editAuthorizationChecker,
						propertyDatatypeRepository,
					} ),
				};

				// @ts-ignore
				actions.entityModule = {
					dispatch: jest.fn(),
				};

				await actions.initBridge( information );
				expect( commit ).toHaveBeenCalledWith( 'setEditFlow', editFlow );
				expect( commit ).toHaveBeenCalledWith( 'setPropertyPointer', propertyId );
				expect( commit ).toHaveBeenCalledWith( 'setEntityTitle', entityTitle );
				expect( commit ).toHaveBeenCalledWith( 'setOriginalHref', originalHref );
				expect( commit ).toHaveBeenCalledWith( 'setPageTitle', pageTitle );
				expect( commit ).toHaveBeenCalledWith( 'setPageUrl', pageUrl );
				expect( commit ).toHaveBeenCalledWith( 'setApplicationStatus', ApplicationStatus.READY );
				expect( commit ).toHaveBeenCalledWith( 'setClientConfig', client );
			},
		);

		it.each( [
			[ false, 'Test user' ],
			[ true, null ],
		] )(
			'sets showWarningAnonymousEdit to %s if user name is %s',
			async ( expected: boolean, userName: string|null ) => {
				const information = newMockAppInformation( { userName } );

				const commit = jest.fn();
				const actions = inject( RootActions, {
					commit,
					dispatch: jest.fn(),
				} );
				// @ts-ignore
				actions.store = {
					$services: newMockServiceContainer( {
						wikibaseRepoConfigRepository,
						editAuthorizationChecker,
						propertyDatatypeRepository,
					} ),
				};

				// @ts-ignore
				actions.entityModule = {
					dispatch: jest.fn(),
				};

				await actions.initBridge( information );
				expect( commit ).toHaveBeenCalledWith( 'setShowWarningAnonymousEdit', expected );
			},
		);

		it( 'dispatches to requestAndSetTargetLabel', async () => {
			const propertyId = 'P42';
			const information = newMockAppInformation( {
				propertyId,
			} );

			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit: jest.fn(),
				dispatch,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
				} ),
			};

			// @ts-ignore
			actions.entityModule = {
				dispatch: jest.fn(),
			};

			await actions.initBridge( information );
			expect( dispatch ).toHaveBeenCalledWith( 'requestAndSetTargetLabel', propertyId );
		} );

		it( 'Requests repoConfig, authorization, datatype and dispatches entityInit in that order', async () => {
			const propertyId = 'P42';
			const entityTitle = 'Douglas Adams';
			const pageTitle = 'South_Pole_Telescope';
			const entityId = 'Q42';
			const information = newMockAppInformation( {
				propertyId,
				entityTitle,
				pageTitle,
				entityId,
			} );
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit: jest.fn(),
				dispatch,
			} );

			const mockWikibaseRepoConfig = {};
			const wikibaseRepoConfigRepository = {
				getRepoConfiguration: jest.fn().mockResolvedValue( mockWikibaseRepoConfig ),
			};
			const mockListOfAuthorizationErrors: MissingPermissionsError[] = [];
			const editAuthorizationChecker = {
				canUseBridgeForItemAndPage: jest.fn().mockResolvedValue( mockListOfAuthorizationErrors ),
			};
			const propertyDataType = 'string';
			const propertyDatatypeRepository: PropertyDatatypeRepository = {
				getDataType: jest.fn().mockResolvedValue( propertyDataType ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
				} ),
			};
			const ignoredEntityInitResult = {};
			const entityModuleDispatch = jest.fn().mockResolvedValue( ignoredEntityInitResult );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};

			await actions.initBridge( information );
			expect( wikibaseRepoConfigRepository.getRepoConfiguration ).toHaveBeenCalled();
			expect( editAuthorizationChecker.canUseBridgeForItemAndPage ).toHaveBeenCalledWith(
				entityTitle,
				pageTitle,
			);
			expect( propertyDatatypeRepository.getDataType ).toHaveBeenCalledWith( propertyId );
			expect( entityModuleDispatch ).toHaveBeenCalledWith( 'entityInit', { entity: entityId } );
			expect( dispatch ).toHaveBeenCalledTimes( 2 );
			expect( dispatch.mock.calls[ 1 ][ 0 ] ).toBe( 'initBridgeWithRemoteData' );
			expect( dispatch.mock.calls[ 1 ][ 1 ] ).toStrictEqual( {
				results: [
					mockWikibaseRepoConfig,
					mockListOfAuthorizationErrors,
					propertyDataType,
					ignoredEntityInitResult,
				],
			} );

		} );

		it( 'Retries initialization once on CentralAuth badtoken error', async () => {
			const propertyId = 'P42';
			const entityTitle = 'Douglas Adams';
			const pageTitle = 'South_Pole_Telescope';
			const entityId = 'Q42';
			const information = newMockAppInformation( {
				propertyId,
				entityTitle,
				pageTitle,
				entityId,
			} );
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit: jest.fn(),
				dispatch,
			} );

			const mockWikibaseRepoError = new ApiErrors( [ {
				code: 'badtoken',
				params: [ 'apierror-centralauth-badtoken' ],
			} as ApiBadtokenError ] );
			const mockWikibaseRepoConfig = {};
			const wikibaseRepoConfigRepository = {
				getRepoConfiguration: jest.fn()
					.mockRejectedValueOnce( mockWikibaseRepoError )
					.mockResolvedValue( mockWikibaseRepoConfig ),
			};
			const mockListOfAuthorizationErrors: MissingPermissionsError[] = [];
			const editAuthorizationChecker = {
				canUseBridgeForItemAndPage: jest.fn().mockResolvedValue( mockListOfAuthorizationErrors ),
			};
			const propertyDataType = 'string';
			const propertyDatatypeRepository: PropertyDatatypeRepository = {
				getDataType: jest.fn().mockResolvedValue( propertyDataType ),
			};
			const tracker = newMockTracker();

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
					tracker,
				} ),
			};
			const ignoredEntityInitResult = {};
			const entityModuleDispatch = jest.fn().mockResolvedValue( ignoredEntityInitResult );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};

			await actions.initBridge( information );
			expect( wikibaseRepoConfigRepository.getRepoConfiguration ).toHaveBeenCalled();
			expect( editAuthorizationChecker.canUseBridgeForItemAndPage ).toHaveBeenCalledWith(
				entityTitle,
				pageTitle,
			);
			expect( propertyDatatypeRepository.getDataType ).toHaveBeenCalledWith( propertyId );
			expect( entityModuleDispatch ).toHaveBeenCalledWith( 'entityInit', { entity: entityId } );
			expect( dispatch ).toHaveBeenCalledTimes( 2 );
			expect( dispatch.mock.calls[ 1 ][ 0 ] ).toBe( 'initBridgeWithRemoteData' );
			expect( dispatch.mock.calls[ 1 ][ 1 ] ).toStrictEqual( {
				results: [
					mockWikibaseRepoConfig,
					mockListOfAuthorizationErrors,
					propertyDataType,
					ignoredEntityInitResult,
				],
			} );
			expect( tracker.trackRecoveredError ).toHaveBeenCalledWith( ErrorTypes.CENTRALAUTH_BADTOKEN );
		} );

		it( 'Commits to addApplicationErrors if there is an error', async () => {
			const information = newMockAppInformation();
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch: jest.fn(),
			} );
			const error = new Error( 'This should be logged and propagated' );
			const wikibaseRepoConfigRepository = {
				getRepoConfiguration: jest.fn().mockRejectedValue( error ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
				} ),
			};

			// @ts-ignore
			actions.entityModule = {
				dispatch: jest.fn(),
			};
			await expect( actions.initBridge( information ) ).rejects.toBe( error );
			expect( commit ).toHaveBeenCalledWith(
				'addApplicationErrors',
				[ {
					type: ErrorTypes.INITIALIZATION_ERROR,
					info: error,
				} ],
			);
		} );

		it( 'Commits repeated CentralAuth badtoken error as custom type', async () => {
			const information = newMockAppInformation();
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch: jest.fn(),
			} );
			const error = new ApiErrors( [ {
				code: 'badtoken',
				params: [ 'apierror-centralauth-badtoken' ],
			} as ApiBadtokenError ] );
			const wikibaseRepoConfigRepository = {
				getRepoConfiguration: jest.fn().mockRejectedValue( error ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
				} ),
			};

			// @ts-ignore
			actions.entityModule = {
				dispatch: jest.fn(),
			};
			await expect( actions.initBridge( information ) ).rejects.toBe( error );
			expect( commit ).toHaveBeenCalledWith(
				'addApplicationErrors',
				[ {
					type: ErrorTypes.CENTRALAUTH_BADTOKEN,
					info: error,
				} ],
			);
		} );
	} );

	describe( 'requestAndSetTargetLabel', () => {

		it( 'commits to setTargetLabel if request was successful', async () => {
			const propertyId = 'Q42';
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const propertyLabel = 'instance of';
			const entityLabelRepository = {
				getLabel: jest.fn().mockResolvedValue( propertyLabel ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					entityLabelRepository,
				} ),
			};

			await actions.requestAndSetTargetLabel( propertyId );
			expect( entityLabelRepository.getLabel ).toHaveBeenCalledWith( propertyId );
			expect( commit ).toHaveBeenCalledWith( 'setTargetLabel', propertyLabel );
		} );

		it( 'does nothing if there was an error', async () => {
			const commit = jest.fn();
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch,
			} );
			const error = new Error( 'ignored' );
			const entityLabelRepository = {
				getLabel: jest.fn().mockRejectedValue( error ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					entityLabelRepository,
				} ),
			};

			await actions.requestAndSetTargetLabel( 'Q123' );
			expect( commit ).not.toHaveBeenCalled();
			expect( dispatch ).not.toHaveBeenCalled();
		} );

	} );

	describe( 'postEntityLoad', () => {
		it( 'dispatches validateEntityState', async () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ { mainsnak: { datavalue: {} } } ],
						},
					},
				} ),
				commit: jest.fn(),
				dispatch,
				getters: jest.fn() as any,
			} );

			const mainSnakPath = new MainSnakPath( defaultEntityId, defaultPropertyId, 0 );

			await actions.postEntityLoad();

			expect( dispatch ).toHaveBeenCalledWith( 'validateEntityState', mainSnakPath );
		} );

		it( 'commits targetValue & setApplicationStatus if there are no errors', async () => {
			const commit = jest.fn();
			const dataValue: DataValue = { type: 'string', value: 'a string value' };
			const statement = { mainsnak: { datavalue: dataValue } };
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ statement ],
						},
					},
				} ),
				commit,
				dispatch: jest.fn(),
				getters: {
					applicationStatus: ApplicationStatus.INITIALIZING,
				} as RootGetters,
			} );

			await actions.postEntityLoad();

			expect( commit ).toHaveBeenCalledWith( 'setTargetValue', dataValue );
		} );

		it( 'doesn\'t commit if there are errors', async () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ {} ],
						},
					},
				} ),
				commit,
				dispatch: jest.fn(),
				getters: {
					applicationStatus: ApplicationStatus.ERROR,
				} as RootGetters,
			} );

			await actions.postEntityLoad();

			expect( commit ).not.toHaveBeenCalled();
		} );

		it( 'doesn\'t commit setApplicationStatus if not initializing', async () => {
			const commit = jest.fn();
			const dataValue: DataValue = { type: 'string', value: 'a string value' };
			const statement = { mainsnak: { datavalue: dataValue } };
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ statement ],
						},
					},
				} ),
				commit,
				dispatch: jest.fn(),
				getters: {
					applicationStatus: ApplicationStatus.SAVED,
				} as RootGetters,
			} );

			await actions.postEntityLoad();

			expect( commit ).toHaveBeenCalledWith( 'setTargetValue', dataValue );
			expect( commit ).not.toHaveBeenCalledWith( 'setApplicationStatus', ApplicationStatus.READY );
		} );
	} );

	describe( 'initBridgeWithRemoteData', () => {
		it( 'commits to addApplicationErrors if there are permission errors', async () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch: jest.fn(),
			} );

			const permissionErrors = [ {
				type: PageNotEditable.ITEM_CASCADE_PROTECTED,
				info: {
					pages: [ 'foo' ],
				},
			} as CascadeProtectedReason ];

			await actions.initBridgeWithRemoteData( { results: [
				{} as WikibaseRepoConfiguration,
				permissionErrors,
				'string',
				undefined,
			] } );

			expect( commit ).toHaveBeenCalledWith( 'addApplicationErrors', permissionErrors );
		} );

		it( 'tracks the datatype', async () => {
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ { mainsnak: { datavalue: {} } } ],
						},
					},
				} ),
				commit: jest.fn(),
				dispatch: jest.fn(),
				getters: jest.fn() as any,
			} );
			const propertyDataType = 'string';
			const tracker = newMockTracker();

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};
			await actions.initBridgeWithRemoteData( { results: [
				{} as WikibaseRepoConfiguration,
				[],
				propertyDataType,
				undefined,
			] } );

			expect( tracker.trackPropertyDatatype ).toHaveBeenCalledWith( propertyDataType );

		} );

		it( 'dispatches the reference rendering', async () => {
			let referenceRenderingResolve;
			const dispatch = jest.fn().mockReturnValue( new Promise( ( resolve ) => {
				referenceRenderingResolve = resolve;
			} ) );
			const actions = inject( RootActions, {
				state: newApplicationState(),
				dispatch,
				commit: jest.fn(),
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker: newMockTracker(),
				} ),
			};

			actions.initBridgeWithRemoteData( {
				results: [
					{} as WikibaseRepoConfiguration,
					[],
					'string',
					undefined,
				],
			} );

			expect( dispatch ).toHaveBeenCalledTimes( 1 );
			expect( dispatch ).toHaveBeenCalledWith( 'renderReferences' );

			// @ts-ignore
			referenceRenderingResolve();
			await budge();
			expect( dispatch ).toHaveBeenCalledTimes( 2 );
			expect( dispatch ).toHaveBeenCalledWith( 'postEntityLoad' );

		} );

		it( 'gracefully ignores but tracks problems while rendering references', async () => {
			const dispatch = jest.fn( ( action: string ) => {
				if ( action === 'renderReferences' ) {
					throw new Error( 'bad things happened' );
				}
				return Promise.resolve();
			} );
			const actions = inject( RootActions, {
				state: newApplicationState(),
				dispatch,
				commit: jest.fn(),
			} );
			const tracker = newMockTracker();

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};

			actions.initBridgeWithRemoteData( {
				results: [
					{} as WikibaseRepoConfiguration,
					[],
					'string',
					undefined,
				],
			} );

			expect( dispatch ).toHaveBeenCalledWith( 'renderReferences' );
			expect( tracker.trackError ).toHaveBeenCalledWith( 'render_references' );

			expect( dispatch ).toHaveBeenCalledWith( 'postEntityLoad' );
		} );

		it( 'sets the config for repo', async () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ { mainsnak: { datavalue: {} } } ],
						},
					},
				} ),
				commit,
				dispatch: jest.fn(),
				getters: jest.fn() as any,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker: newMockTracker(),
				} ),
			};

			const wikibaseRepoConfiguration = getMockBridgeRepoConfig();

			await actions.initBridgeWithRemoteData( { results: [
				wikibaseRepoConfiguration,
				[],
				'string',
				undefined,
			] } );

			expect( commit ).toHaveBeenCalledWith(
				'setRepoConfig',
				wikibaseRepoConfiguration,
			);
		} );

		it( 'dispatches postEntityLoad', async () => {
			const commit = jest.fn();
			const dataValue: DataValue = { type: 'string', value: 'a string value' };
			const statement = { mainsnak: { datavalue: dataValue } };
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ statement ],
						},
					},
				} ),
				commit,
				dispatch,
				getters: jest.fn() as any,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker: newMockTracker(),
				} ),
			};

			await actions.initBridgeWithRemoteData( { results: [
				{} as WikibaseRepoConfiguration,
				[],
				'string',
				undefined,
			] } );

			expect( dispatch ).toHaveBeenCalledWith( 'postEntityLoad' );
		} );
	} );

	describe( 'renderReferences', () => {
		it( 'gets the rendered references and stores them in the store', async () => {
			const commit = jest.fn();
			const mockReferences: Reference[] = [
				{} as Reference,
				{} as Reference,
			];
			const actions = inject( RootActions, {
				commit,
				getters: {
					targetReferences: mockReferences,
				} as any,
			} );
			const mockRenderedReferences = [
				'<span>ref1</span>',
				'<span>ref2</span>',
			];
			const getRenderedReferences = jest.fn().mockResolvedValue( mockRenderedReferences );
			const referencesRenderingRepository = {
				getRenderedReferences,
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					referencesRenderingRepository,
				} ),
			};

			await expect( actions.renderReferences() ).resolves.toBeUndefined();

			expect( getRenderedReferences ).toHaveBeenCalledWith( mockReferences );
			expect( commit ).toHaveBeenCalledWith( 'setRenderedTargetReferences', mockRenderedReferences );
		} );
	} );

	describe( 'validateEntityState', () => {
		it( 'commits to addApplicationErrors if property is missing on entity', () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const statementPropertyGetter = jest.fn().mockReturnValue( false );

			// @ts-ignore
			actions.statementModule = {
				getters: {
					propertyExists: statementPropertyGetter,
				} as any,
			};

			const entityId = 'Q42';
			const propertyId = 'P42';
			const mainSnakPath = new MainSnakPath( entityId, propertyId, 0 );
			actions.validateEntityState( mainSnakPath );
			expect( statementPropertyGetter ).toHaveBeenCalledWith( mainSnakPath );
			expect( commit ).toHaveBeenCalledWith(
				'addApplicationErrors',
				[ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ],
			);
		} );

		it( 'dispatches validateBridgeApplicability if there are no errors', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				dispatch,
			} );
			const statementPropertyGetter = jest.fn().mockReturnValue( true );

			// @ts-ignore
			actions.statementModule = {
				getters: {
					propertyExists: statementPropertyGetter,
				} as any,
			};
			const mainSnakPath = new MainSnakPath( 'Q42', 'P42', 0 );
			actions.validateEntityState( mainSnakPath );
			expect( dispatch ).toHaveBeenCalledWith( 'validateBridgeApplicability', mainSnakPath );
		} );
	} );

	describe( 'validateBridgeApplicability', () => {
		const state = { applicationStatus: ApplicationStatus.INITIALIZING } as Application;
		const statementModule = {
			getters: {
				isStatementGroupAmbiguous: jest.fn().mockReturnValue( false ),
				rank: jest.fn().mockReturnValue( 'normal' ),
				snakType: jest.fn().mockReturnValue( 'value' ),
				dataType: jest.fn().mockReturnValue( 'string' ),
				dataValueType: jest.fn().mockReturnValue( 'string' ),
			},
		};

		it( 'doesn\'t dispatch error if applicable', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			// @ts-ignore
			actions.statementModule = statementModule;

			const entityId = 'Q42';
			const propertyId = 'P42';
			const mainSnakPath = new MainSnakPath( entityId, propertyId, 0 );
			actions.validateBridgeApplicability( mainSnakPath );
			expect( dispatch ).not.toHaveBeenCalled();
			expect( statementModule.getters.isStatementGroupAmbiguous ).toHaveBeenCalledWith( mainSnakPath );
			expect( statementModule.getters.snakType ).toHaveBeenCalledWith( mainSnakPath );
			expect( statementModule.getters.dataValueType ).toHaveBeenCalledWith( mainSnakPath );
		} );

		it( 'doesn\'t dispatch error if saved', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state: { applicationStatus: ApplicationStatus.SAVED } as Application,
				dispatch,
			} );

			// @ts-ignore
			actions.statementModule = statementModule;

			const entityId = 'Q42';
			const propertyId = 'P42';
			const mainSnakPath = new MainSnakPath( entityId, propertyId, 0 );
			actions.validateBridgeApplicability( mainSnakPath );
			expect( dispatch ).not.toHaveBeenCalled();
			expect( statementModule.getters.isStatementGroupAmbiguous ).not.toHaveBeenCalled();
			expect( statementModule.getters.snakType ).not.toHaveBeenCalled();
			expect( statementModule.getters.dataValueType ).not.toHaveBeenCalled();
		} );

		it( 'dispatches error on ambiguous statements', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.isStatementGroupAmbiguous.mockReturnValueOnce( true );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ { type: ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT } ],
			);

		} );

		it( 'dispatches error on deprecated statement', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.rank.mockReturnValueOnce( 'deprecated' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ { type: ErrorTypes.UNSUPPORTED_DEPRECATED_STATEMENT } ],
			);
		} );

		it( 'dispatches error for non-value snak types', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.snakType.mockReturnValueOnce( 'novalue' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ { type: ErrorTypes.UNSUPPORTED_SNAK_TYPE, info: { snakType: 'novalue' } } ],
			);

		} );

		it( 'dispatches error for non-string data types', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.dataType.mockReturnValueOnce( 'url' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ {
					type: ErrorTypes.UNSUPPORTED_DATATYPE,
					info: {
						unsupportedDatatype: 'url',
					},
				} ],
			);
		} );

		it( 'dispatches error for non-string data value types', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.dataValueType.mockReturnValueOnce( 'number' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ { type: ErrorTypes.UNSUPPORTED_DATAVALUE_TYPE } ],
			);
		} );

		it( 'dispatches single error for multiple problems', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				dispatch,
			} );

			statementModule.getters.isStatementGroupAmbiguous.mockReturnValueOnce( true );
			statementModule.getters.snakType.mockReturnValueOnce( 'novalue' );
			statementModule.getters.dataType.mockReturnValueOnce( 'url' );
			statementModule.getters.dataValueType.mockReturnValueOnce( 'number' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions.validateBridgeApplicability( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledTimes( 1 );
			expect( dispatch ).toHaveBeenCalledWith(
				'addError',
				[ { type: ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT } ],
			);
		} );
	} );

	describe( 'setTargetValue', () => {
		it( `logs an error if the application is not in ${ApplicationStatus.READY} state and rejects`, async () => {
			const commit = jest.fn();
			const state = newApplicationState();
			const actions = inject( RootActions, {
				commit,
				state,
			} );

			await expect( actions.setTargetValue( {} as DataValue ) ).rejects.toBeNull();
			expect( commit ).toHaveBeenCalledTimes( 1 );
			expect( commit.mock.calls[ 0 ][ 0 ] ).toBe( 'addApplicationErrors' );
			const arrayOfActualErrors = commit.mock.calls[ 0 ][ 1 ];
			expect( arrayOfActualErrors.length ).toBe( 1 );
			expect( arrayOfActualErrors[ 0 ].type ).toBe( ErrorTypes.APPLICATION_LOGIC_ERROR );
			expect( arrayOfActualErrors[ 0 ].info ).toHaveProperty( 'stack' );
		} );

		it( 'commits targetValue', async () => {
			const rootModuleCommit = jest.fn();
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
			} );
			const actions = inject( RootActions, {
				commit: rootModuleCommit,
				state,
			} );

			const dataValue: DataValue = {
				type: 'string',
				value: 'the value',
			};

			await expect( actions.setTargetValue( dataValue ) ).resolves.toBe( undefined );
			expect( rootModuleCommit ).toHaveBeenCalledWith( 'setTargetValue', dataValue );
		} );
	} );

	describe( 'saveBridge', () => {
		it( `logs an error if the application is not in ${ApplicationStatus.READY} state and rejects`, async () => {
			const commit = jest.fn();
			const state = newApplicationState();
			const actions = inject( RootActions, {
				commit,
				state,
			} );

			await expect( actions.saveBridge() ).rejects.toBeNull();
			expect( commit ).toHaveBeenCalledTimes( 1 );
			expect( commit.mock.calls[ 0 ][ 0 ] ).toBe( 'addApplicationErrors' );
			const arrayOfActualErrors = commit.mock.calls[ 0 ][ 1 ];
			expect( arrayOfActualErrors.length ).toBe( 1 );
			expect( arrayOfActualErrors[ 0 ].type ).toBe( ErrorTypes.APPLICATION_LOGIC_ERROR );
			expect( arrayOfActualErrors[ 0 ].info ).toHaveProperty( 'stack' );
		} );

		// eslint-disable-next-line max-len
		it( `sets the application state to ${ApplicationStatus.SAVING} if there are no errors and sets it to ${ApplicationStatus.SAVED} if saving was successful`, async () => {
			const rootModuleDispatch = jest.fn();
			const commit = jest.fn();
			const entityId = 'Q42';
			const targetPropertyId = 'P42';
			const targetValue: DataValue = {
				type: 'string',
				value: 'a new value',
			};
			const statementsState: StatementState = {
				[ entityId ]: {
					[ targetPropertyId ]: [
						{} as Statement,
					],
				},
			};
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				targetValue,
				targetProperty: targetPropertyId,
				entity: {
					id: entityId,
				},
				statements: statementsState,
			} );
			const actions = inject( RootActions, {
				state,
				commit,
				dispatch: rootModuleDispatch,
			} );
			const entityModuleDispatch = jest.fn( () => Promise.resolve() );

			const statementMutationStrategy = jest.fn().mockReturnValue( statementsState );
			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: statementMutationStrategy,
			} ) );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};
			await actions.saveBridge();

			expect( commit ).toHaveBeenCalledTimes( 2 );
			expect( commit ).toHaveBeenNthCalledWith( 1, 'setApplicationStatus', ApplicationStatus.SAVING );
			expect( commit ).toHaveBeenNthCalledWith( 2, 'setApplicationStatus', ApplicationStatus.SAVED );
		} );

		it( 'dispatches entitySave, purges the page & dispatches postEntityLoad', async () => {
			const rootModuleDispatch = jest.fn();
			const commit = jest.fn();
			const entityId = 'Q42';
			const targetPropertyId = 'P42';
			const targetValue: DataValue = {
				type: 'string',
				value: 'a new value',
			};
			const statementsState: StatementState = {
				[ entityId ]: {
					[ targetPropertyId ]: [
						{} as Statement,
					],
				},
			};
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				targetValue,
				targetProperty: targetPropertyId,
				entity: {
					id: entityId,
				},
				statements: statementsState,
			} );

			const actions = inject( RootActions, {
				state,
				commit,
				dispatch: rootModuleDispatch,
			} );

			const entityModuleDispatch = jest.fn( () => Promise.resolve() );

			const statementMutationStrategy = jest.fn().mockReturnValue( statementsState );
			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: statementMutationStrategy,
			} ) );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};
			await actions.saveBridge();

			expect( statementMutationStrategy )
				.toHaveBeenCalledWith(
					targetValue,
					new MainSnakPath( entityId, targetPropertyId, 0 ),
					statementsState,
				);
			expect( statementMutationStrategy.mock.calls[ 0 ][ 2 ] ).toStrictEqual( statementsState );
			expect( statementMutationStrategy.mock.calls[ 0 ][ 2 ] ).not.toBe( statementsState );

			expect( entityModuleDispatch ).toHaveBeenCalledWith(
				'entitySave',
				{ statements: statementsState[ entityId ], assertUser: true },
			);
			expect( rootModuleDispatch ).toHaveBeenCalledWith( 'purgeTargetPage' );
			expect( rootModuleDispatch ).toHaveBeenCalledWith( 'postEntityLoad' );
		} );

		it( 'takes assertUserWhenSaving = false into account', async () => {
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				entity: {
					id: 'Q123',
				},
				statements: {},
				assertUserWhenSaving: false,
			} );
			const actions = inject( RootActions, {
				state,
				commit: jest.fn(),
				dispatch: jest.fn(),
			} );

			const statementMutationStrategy = jest.fn().mockReturnValue( {} );
			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: statementMutationStrategy,
			} ) );

			const entityModuleDispatch = jest.fn( () => Promise.resolve() );
			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};
			await actions.saveBridge();

			expect( entityModuleDispatch ).toHaveBeenCalledWith(
				'entitySave',
				{ statements: undefined, assertUser: false },
			);
		} );

		it( 'chooses the strategy based on the edit decision', async () => {
			const editDecision = EditDecision.UPDATE;
			const state = newApplicationState( {
				editDecision,
				applicationStatus: ValidApplicationStatus.READY,
				entity: {
					id: 'Q123',
				},
				statements: {},
			} );
			const actions = inject( RootActions, {
				state,
				commit: jest.fn(),
				dispatch: jest.fn(),
			} );

			const statementMutationFactory = jest.fn( ( () => ( {
				apply: jest.fn().mockReturnValue( {} ),
			} ) ) );
			// @ts-ignore
			actions.statementMutationFactory = statementMutationFactory;

			// @ts-ignore
			actions.entityModule = {
				dispatch: jest.fn( () => Promise.resolve() ),
			};

			await actions.saveBridge();
			expect( statementMutationFactory ).toHaveBeenCalledWith( editDecision );
		} );

		it( 'logs an error if statement mutation strategy fails', async () => {
			const entityId = 'Q42';
			const targetPropertyId = 'P42';
			const targetValue: DataValue = {
				type: 'string',
				value: 'a new value',
			};
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				targetValue,
				targetProperty: targetPropertyId,
				entity: {
					id: entityId,
				},
				statements: {},
			} );
			const commit = jest.fn();
			const actions = inject( RootActions, {
				state,
				commit,
			} );

			const error = new Error( 'This error should be logged and propagated.' );
			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: jest.fn( () => {
					throw error;
				} ),
			} ) );

			await expect( actions.saveBridge() ).rejects.toBe( error );
			expect( commit ).toHaveBeenCalledWith(
				'addApplicationErrors',
				[ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: error } ],
			);
		} );

		it( 'logs an error if saving failed and rejects', async () => {
			const entityId = 'Q42';
			const targetPropertyId = 'P42';
			const dataValue: DataValue = {
				type: 'string',
				value: 'the value',
			};
			const statementsState: StatementState = {
				[ entityId ]: {
					[ targetPropertyId ]: [
						{} as Statement,
					],
				},
			};
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				targetValue: dataValue,
				targetProperty: targetPropertyId,
				entity: {
					id: entityId,
				},
				statements: statementsState,
			} );

			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
				state,
			} );

			const error = new Error( 'This error should be logged and propagated.' );
			const entityModuleDispatch = jest.fn().mockRejectedValue( error );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};

			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: jest.fn().mockReturnValue( statementsState ),
			} ) );

			await expect( actions.saveBridge() ).rejects.toBe( error );
			expect( commit ).toHaveBeenCalledWith(
				'addApplicationErrors',
				[ { type: ErrorTypes.SAVING_FAILED, info: error } ],
			);
		} );

		it( 'logs SavingError errors individually and rejects', async () => {
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				entity: {
					id: 'Q123',
				},
				statements: {},
			} );

			const commit = jest.fn();
			const actions = inject( RootActions, {
				state,
				commit,
			} );

			const statementMutationStrategy = jest.fn().mockReturnValue( {} );
			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: statementMutationStrategy,
			} ) );

			const assertAnonFailedError = { code: 'assertanonfailed' };
			const assertUserFailedError = { code: 'assertuserfailed' };
			const someOtherApiError = { code: 'foo' };
			const savingEntityError = new SavingError( [
				{ type: ErrorTypes.ASSERT_ANON_FAILED, info: assertAnonFailedError },
				{ type: ErrorTypes.ASSERT_USER_FAILED, info: assertUserFailedError },
				{ type: ErrorTypes.SAVING_FAILED, info: someOtherApiError },
			] );
			const entityModuleDispatch = jest.fn( () => Promise.reject( savingEntityError ) );
			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};

			await expect( actions.saveBridge() ).rejects.toBe( savingEntityError );
			expect( commit ).toHaveBeenCalledWith(
				'addApplicationErrors',
				savingEntityError.errors,
			);
		} );

		it( 'purges the page if there is an edit conflict error', async () => {
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				entity: {
					id: 'Q123',
				},
				statements: {},
			} );

			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state,
				commit: jest.fn(),
				dispatch,
			} );

			const statementMutationStrategy = jest.fn().mockReturnValue( {} );
			// @ts-ignore
			actions.statementMutationFactory = ( () => ( {
				apply: statementMutationStrategy,
			} ) );

			const savingEntityError = new SavingError( [
				{ type: ErrorTypes.ASSERT_USER_FAILED },
				{ type: ErrorTypes.EDIT_CONFLICT },
				{ type: ErrorTypes.SAVING_FAILED },
			] );
			const entityModuleDispatch = jest.fn( () => Promise.reject( savingEntityError ) );
			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};

			await expect( actions.saveBridge() ).rejects.toThrow();
			expect( dispatch ).toHaveBeenCalledWith( 'purgeTargetPage' );
		} );

	} );

	describe( 'purgeTargetPage', () => {
		it( 'purges the page title', async () => {
			const pageTitle = 'South_Pole_Telescope';
			const state = newApplicationState( {
				pageTitle,
			} );

			const actions = inject( RootActions, {
				state,
			} );

			const purgeTitles: MediaWikiPurge = {
				purge: jest.fn().mockReturnValue( Promise.resolve() ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					purgeTitles,
				} ),
			};

			await actions.purgeTargetPage();

			expect( purgeTitles.purge ).toHaveBeenCalledWith( [ pageTitle ] );
		} );

		it( 'gracefully ignores but tracks problems while purging the page', async () => {
			const pageTitle = 'South_Pole_Telescope';
			const state = newApplicationState( {
				pageTitle,
			} );

			const actions = inject( RootActions, {
				state,
			} );

			const purgeTitles: MediaWikiPurge = {
				purge: jest.fn().mockReturnValue( Promise.reject() ),
			};
			const trackError = jest.fn();

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					purgeTitles,
					tracker: newMockTracker( {
						trackError,
					} ),
				} ),
			};

			await expect( actions.purgeTargetPage() ).resolves.toBeUndefined();
			expect( trackError ).toHaveBeenCalledTimes( 1 );
			expect( trackError ).toHaveBeenCalledWith( 'purge' );
		} );
	} );

	describe( 'retrySave', () => {
		it( 'dispatches goBackFromErrorToReady and saveBridge', async () => {
			const rootModuleDispatch = jest.fn();

			const state = newApplicationState();

			const actions = inject( RootActions, {
				state,
				dispatch: rootModuleDispatch,
			} );
			await actions.retrySave();
			expect( rootModuleDispatch ).toHaveBeenCalledTimes( 2 );
			expect( rootModuleDispatch ).toHaveBeenNthCalledWith( 1, 'goBackFromErrorToReady' );
			expect( rootModuleDispatch ).toHaveBeenNthCalledWith( 2, 'saveBridge' );
		} );
	} );

	describe( 'goBackFromErrorToReady', () => {
		it( 'commits clearApplicationErrors, sets the status to READY', async () => {
			const rootModuleDispatch = jest.fn();
			const state = newApplicationState( {
				applicationErrors: [ { type: ErrorTypes.SAVING_FAILED } ],
				applicationStatus: ApplicationStatus.SAVING,
			} );

			const commit = jest.fn();
			const actions = inject( RootActions, {
				state,
				commit,
				dispatch: rootModuleDispatch,
			} );

			await actions.goBackFromErrorToReady();
			expect( commit ).toHaveBeenCalledTimes( 2 );
			expect( commit ).toHaveBeenNthCalledWith( 1, 'clearApplicationErrors' );
			expect( commit ).toHaveBeenNthCalledWith( 2, 'setApplicationStatus', ApplicationStatus.READY );
		} );
	} );

	describe( 'addError', () => {
		it( 'it commits the provided error', () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const errors: ApplicationError[] = [ { type: ErrorTypes.SAVING_FAILED } ];
			actions.addError( errors );
			expect( commit ).toHaveBeenCalledWith( 'addApplicationErrors', errors );
		} );
	} );

	describe( 'setEditDecision', () => {
		it( 'commits the edit decision to the store', () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const editDecision = EditDecision.REPLACE;

			actions.setEditDecision( editDecision );

			expect( commit ).toHaveBeenCalledWith( 'setEditDecision', editDecision );
		} );
	} );

	describe( 'trackErrorsFallingBackToGenericView', () => {
		it( 'sends application errors to tracker', async () => {
			const state = newApplicationState( {
				applicationErrors: [
					{ type: 'type_1' } as unknown as ApplicationError,
					{ type: 'type_2' } as unknown as ApplicationError,
				],
			} );
			const actions = inject( RootActions, { state } );
			const tracker = newMockTracker();
			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};

			await actions.trackErrorsFallingBackToGenericView();

			expect( tracker.trackUnknownError ).toHaveBeenCalledWith( 'type_1' );
			expect( tracker.trackUnknownError ).toHaveBeenCalledWith( 'type_2' );
		} );
	} );

	describe( 'trackSavingErrorsFallingBackToGenericView', () => {
		it( 'sends application errors to tracker', async () => {
			const state = newApplicationState( {
				applicationErrors: [
					{ type: 'type_1' } as unknown as ApplicationError,
					{ type: 'type_2' } as unknown as ApplicationError,
				],
			} );
			const actions = inject( RootActions, { state } );
			const tracker = newMockTracker();
			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};

			await actions.trackSavingErrorsFallingBackToGenericView();

			expect( tracker.trackSavingUnknownError ).toHaveBeenCalledWith( 'type_1' );
			expect( tracker.trackSavingUnknownError ).toHaveBeenCalledWith( 'type_2' );
		} );
	} );

	describe( 'dismissWarningAnonymousEdit', () => {
		it( 'commits the unset flag to the store', () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );

			actions.dismissWarningAnonymousEdit();

			expect( commit ).toHaveBeenCalledWith( 'setShowWarningAnonymousEdit', false );
		} );
	} );

	describe( 'stopAssertingUserWhenSaving', () => {
		it( 'sets assertUserWhenSaving to false', () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );

			actions.stopAssertingUserWhenSaving();

			expect( commit ).toHaveBeenCalledWith( 'setAssertUserWhenSaving', false );
		} );
	} );

} );
