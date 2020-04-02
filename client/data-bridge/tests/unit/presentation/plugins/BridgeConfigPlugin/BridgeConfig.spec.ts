import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin/BridgeConfig';
import { getMockBridgeRepoConfig } from '../../../../util/mocks';

describe( 'BridgeConfig', () => {
	it( 'fails if invalid options have been provided', () => {
		expect( () => new BridgeConfig( undefined as any ) ).toThrowError();
	} );

	describe( 'mirroring of given values', () => {
		it( 'mirrors usePublish', () => {
			const config = new BridgeConfig( { usePublish: true, issueReportingLink: '' } );
			expect( config.usePublish ).toBe( true );
		} );

		it( 'mirrors the issueReportingLink', () => {
			const issueReportingLink = 'https://example.com';
			const config = new BridgeConfig( { usePublish: true, issueReportingLink } );
			expect( config.issueReportingLink ).toBe( issueReportingLink );
		} );

		describe( 'stringMaxLength', () => {
			it( 'mirrors null if no value is provided', () => {
				const config = new BridgeConfig( { usePublish: false, issueReportingLink: '' } );
				expect( config.stringMaxLength ).toBeNull();
			} );

			it( 'mirrors the given value', () => {
				const maxLength = 12345;
				const config = new BridgeConfig( {
					usePublish: false,
					issueReportingLink: '',
					...getMockBridgeRepoConfig( { dataTypeLimits: {
						string: { maxLength },
					} } ),
				} );
				expect( config.stringMaxLength ).toBe( maxLength );
			} );

		} );

		describe( 'options related to the license', () => {
			it( 'mirrors null if no value is provided', () => {
				const config = new BridgeConfig( { usePublish: false, issueReportingLink: '' } );
				expect( config.dataRightsText ).toBeNull();
				expect( config.dataRightsUrl ).toBeNull();
				expect( config.termsOfUseUrl ).toBeNull();
			} );

			it( 'mirrors the given options related to the license', () => {
				const dataRightsText = 'some license';
				const dataRightsUrl = 'https://example.com/some_license';
				const termsOfUseUrl = 'https://example.com/terms_of_use';

				const config = new BridgeConfig( {
					usePublish: false,
					issueReportingLink: '',
					...getMockBridgeRepoConfig( {
						dataRightsText,
						dataRightsUrl,
						termsOfUseUrl,
					} ),
				} );

				expect( config.dataRightsText ).toBe( dataRightsText );
				expect( config.dataRightsUrl ).toBe( dataRightsUrl );
				expect( config.termsOfUseUrl ).toBe( termsOfUseUrl );
			} );
		} );
	} );
} );
