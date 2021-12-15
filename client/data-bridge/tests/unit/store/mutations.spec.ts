import { DataValue } from '@wmde/wikibase-datamodel-types';
import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EditDecision from '@/definitions/EditDecision';
import EditFlow from '@/definitions/EditFlow';
import Application from '@/store/Application';
import { RootMutations } from '@/store/mutations';
import newApplicationState from './newApplicationState';
import { inject } from 'vuex-smart-module';
import WikibaseClientConfiguration from '@/definitions/WikibaseClientConfiguration';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';

describe( 'root/mutations', () => {
	it( 'changes the targetProperty of the state', () => {
		const state: Application = newApplicationState();

		const mutations = inject( RootMutations, { state } );

		mutations.setPropertyPointer( 'P42' );
		expect( state.targetProperty ).toBe( 'P42' );
	} );

	it( 'changes the editFlow of the state', () => {
		const editFlow: EditFlow = EditFlow.SINGLE_BEST_VALUE;
		const state: Application = newApplicationState();

		const mutations = inject( RootMutations, { state } );
		mutations.setEditFlow( editFlow );
		expect( state.editFlow ).toBe( editFlow );
	} );

	it( 'changes the originalHref of the state', () => {
		const state: Application = newApplicationState();
		const originalHref = 'https://example.com/index.php?title=Item:Q42&uselang=en#P31';

		const mutations = inject( RootMutations, { state } );

		mutations.setOriginalHref( originalHref );
		expect( state.originalHref ).toBe( originalHref );
	} );

	it( 'changes the applicationStatus of the state', () => {
		const state: Application = newApplicationState();
		const mutations = inject( RootMutations, { state } );
		mutations.setApplicationStatus( ApplicationStatus.READY );
		expect( state.applicationStatus ).toBe( ApplicationStatus.READY );
	} );

	it( 'changes the targetLabel of the state', () => {
		const targetLabel = { language: 'el', value: 'πατατα' };
		const state: Application = newApplicationState();
		const mutations = inject( RootMutations, { state } );

		mutations.setTargetLabel( targetLabel );
		expect( state.targetLabel ).toBe( targetLabel );
	} );

	it( 'adds errors to the state', () => {
		const state: Application = newApplicationState();
		const errors: ApplicationError[] = [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ];

		const mutations = inject( RootMutations, { state } );

		mutations.addApplicationErrors( errors );
		expect( state.applicationErrors ).toStrictEqual( errors );
	} );

	it( 'clears all errors from the state', () => {
		const state: Application = newApplicationState();
		state.applicationErrors.push(
			{ type: ErrorTypes.SAVING_FAILED, info: {} },
			{ type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} },
		);

		const mutations = inject( RootMutations, { state } );

		mutations.clearApplicationErrors();
		expect( state.applicationErrors ).toStrictEqual( [] );
	} );

	it( 'does not drop existing errors when adding new ones to the state', () => {
		const oldErrors: ApplicationError[] = [ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ];
		const state: Application = newApplicationState( { applicationErrors: oldErrors.slice() } );
		const newErrors: ApplicationError[] = [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ];

		const mutations = inject( RootMutations, { state } );

		mutations.addApplicationErrors( newErrors );
		expect( state.applicationErrors ).toStrictEqual( [ ...oldErrors, ...newErrors ] );
	} );

	it( 'sets the rendered references', () => {
		const renderedReferences = [
			'<span>Ref1</span>',
			'<span>Ref2</span>',
		];
		const state: Application = newApplicationState();
		const mutations = inject( RootMutations, { state } );

		mutations.setRenderedTargetReferences( renderedReferences );

		expect( state.renderedTargetReferences ).toBe( renderedReferences );
	} );

	it( 'sets the edit decision of the state', () => {
		const state: Application = newApplicationState();
		const editDecision = EditDecision.REPLACE;

		const mutations = inject( RootMutations, { state } );

		mutations.setEditDecision( editDecision );
		expect( state.editDecision ).toBe( editDecision );
	} );

	it( 'sets the target value of the state', () => {
		const state: Application = newApplicationState();
		const targetValue: DataValue = { type: 'string', value: 'a string' };

		const mutations = inject( RootMutations, { state } );

		mutations.setTargetValue( targetValue );
		expect( state.targetValue ).not.toBe( targetValue );
		expect( state.targetValue ).toStrictEqual( targetValue );
	} );

	it( 'sets the entity title of the state', () => {
		const state: Application = newApplicationState();
		const entityTitle = 'Entity title';

		const mutations = inject( RootMutations, { state } );

		mutations.setEntityTitle( entityTitle );
		expect( state.entityTitle ).toBe( entityTitle );
	} );

	it( 'sets the page title of the store', () => {
		const state: Application = newApplicationState();
		const pageTitle = 'Page_title';

		const mutations = inject( RootMutations, { state } );

		mutations.setPageTitle( pageTitle );
		expect( state.pageTitle ).toBe( pageTitle );
	} );

	it( 'sets the page URL of the store', () => {
		const state: Application = newApplicationState();
		const pageUrl = 'https://client.example/wiki/Page_title';

		const mutations = inject( RootMutations, { state } );

		mutations.setPageUrl( pageUrl );
		expect( state.pageUrl ).toBe( pageUrl );
	} );

	it( 'sets the “show warning for anonymous edit” flag of the store', () => {
		const state: Application = newApplicationState();

		const mutations = inject( RootMutations, { state } );

		mutations.setShowWarningAnonymousEdit( true );
		expect( state.showWarningAnonymousEdit ).toBe( true );
	} );

	it( 'sets the assertUserWhenSaving flag', () => {
		const state: Application = newApplicationState();
		const mutations = inject( RootMutations, { state } );

		mutations.setAssertUserWhenSaving( false );
		expect( state.assertUserWhenSaving ).toBe( false );
	} );

	it( 'sets the client config in the store', () => {
		const initialConfig = { dataRightsText: 'foo' };
		const state: Application = newApplicationState( { config: initialConfig } );
		const mutations = inject( RootMutations, { state } );

		const clientConfig: WikibaseClientConfiguration = {
			usePublish: true,
			issueReportingLink: 'https://client.example/',
		};
		mutations.setClientConfig( clientConfig );

		expect( state.config ).toEqual( {
			...initialConfig,
			...clientConfig,
		} );
	} );

	it( 'sets the repo config in the store', () => {
		const initialConfig = { usePublish: true };
		const state: Application = newApplicationState( { config: initialConfig } );
		const mutations = inject( RootMutations, { state } );

		const repoConfig: WikibaseRepoConfiguration = {
			dataRightsText: 'foo',
			dataRightsUrl: 'https://example.com/datarights',
			termsOfUseUrl: 'https://example.com/termsofuse',
			dataTypeLimits: {
				string: { maxLength: 123 },
			},
		};
		mutations.setRepoConfig( repoConfig );

		expect( state.config ).toEqual( {
			...initialConfig,
			dataRightsText: repoConfig.dataRightsText,
			dataRightsUrl: repoConfig.dataRightsUrl,
			termsOfUseUrl: repoConfig.termsOfUseUrl,
			stringMaxLength: repoConfig.dataTypeLimits.string.maxLength,
		} );
	} );

	it( 'resets the root module of the store', () => {
		const state: Application = newApplicationState( {
			applicationStatus: ApplicationStatus.SAVING,
			applicationErrors: [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ],
			targetLabel: { language: 'en', value: 'a label' },
		} );
		const mutations = inject( RootMutations, { state } );

		mutations.reset();

		expect( state.applicationStatus ).toBe( ApplicationStatus.INITIALIZING );
		expect( state.applicationErrors ).toStrictEqual( [] );
		expect( state.targetLabel ).toBe( null );
	} );
} );
