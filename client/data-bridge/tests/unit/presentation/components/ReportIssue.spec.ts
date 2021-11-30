import { ErrorTypes } from '@/definitions/ApplicationError';
import MessageKeys from '@/definitions/MessageKeys';
import ReportIssue from '@/presentation/components/ReportIssue.vue';
import { shallowMount } from '@vue/test-utils';
import { createTestStore } from '../../../util/store';
import { BridgeConfig } from '@/store/Application';

describe( 'ReportIssue', () => {
	it( 'shows a message with the right parameters', () => {
		const pageUrl = 'https://client.example/wiki/Client_page';
		const targetProperty = 'P1';
		const entityTitle = 'Q1';
		const errorCode = ErrorTypes.APPLICATION_LOGIC_ERROR;
		const store = createTestStore( {
			state: {
				pageUrl,
				targetProperty,
				entityTitle,
				applicationErrors: [
					{ type: errorCode, info: {} },
					{ type: ErrorTypes.UNSUPPORTED_DATAVALUE_TYPE }, // should be ignored, only first error is shown
				],
				config: {
					issueReportingLink: 'https://bugs.example/new',
				} as BridgeConfig,
			},
		} );
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn().mockReturnValue( 'Some <abbr>HTML</abbr>.' ),
			getText: jest.fn().mockReturnValue( 'Some text' ),
		};
		const wrapper = shallowMount( ReportIssue, { global: { plugins: [ store ], mocks: { $messages } } } );
		expect( $messages.get ).toHaveBeenCalledTimes( 1 );
		expect( $messages.get ).toHaveBeenCalledWith(
			MessageKeys.ERROR_REPORT,
			store.state.config.issueReportingLink,
			pageUrl,
			targetProperty,
			entityTitle,
			errorCode,
		);
		expect( wrapper.find( '.wb-db-report-issue' ).html() ).toContain( 'Some <abbr>HTML</abbr>.' );
	} );

	it( 'replaces <body> with the issue body getter, URL-encoded', () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ],
			},
			getters: {
				get reportIssueTemplateBody() {
					return '"report issue" template `body` &=#';
				},
				get issueReportingLinkConfig() {
					return 'https://bugs.example/new?body=<body>&tag=data-bridge';
				},
			},
		} );
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn(),
			getText: jest.fn(),
		};
		shallowMount( ReportIssue, { global: { plugins: [ store ], mocks: { $messages } } } );
		expect( $messages.get.mock.calls[ 0 ][ 1 ] ).toBe(
			'https://bugs.example/new?body=%22report%20issue%22%20template%20%60body%60%20%26%3D%23&tag=data-bridge',
		);
	} );
} );
