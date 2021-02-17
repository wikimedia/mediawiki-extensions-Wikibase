import { ErrorTypes } from '@/definitions/ApplicationError';
import MessageKeys from '@/definitions/MessageKeys';
import ReportIssue from '@/presentation/components/ReportIssue.vue';
import { shallowMount } from '@vue/test-utils';
import { createTestStore } from '../../../util/store';

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
			},
		} );
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn().mockReturnValue( 'Some <abbr>HTML</abbr>.' ),
			getText: jest.fn().mockReturnValue( 'Some text' ),
		};
		const $bridgeConfig = {
			issueReportingLink: 'https://bugs.example/new',
		};
		const wrapper = shallowMount( ReportIssue, { store, mocks: { $messages, $bridgeConfig } } );
		expect( $messages.get ).toHaveBeenCalledTimes( 1 );
		expect( $messages.get ).toHaveBeenCalledWith(
			MessageKeys.ERROR_REPORT,
			$bridgeConfig.issueReportingLink,
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
			},
		} );
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn(),
			getText: jest.fn(),
		};
		const $bridgeConfig = {
			issueReportingLink: 'https://bugs.example/new?body=<body>&tag=data-bridge',
		};
		shallowMount( ReportIssue, { store, mocks: { $messages, $bridgeConfig } } );
		expect( $messages.get.mock.calls[ 0 ][ 1 ] ).toBe(
			'https://bugs.example/new?body=%22report%20issue%22%20template%20%60body%60%20%26%3D%23&tag=data-bridge',
		);
	} );
} );
