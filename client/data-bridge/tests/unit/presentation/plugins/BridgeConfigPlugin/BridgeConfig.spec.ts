import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin/BridgeConfig';

describe( 'BridgeConfig', () => {
	it( 'fails if invalid options have been provided', () => {
		expect( () => new BridgeConfig( undefined as any ) ).toThrowError();
	} );

	describe( 'mirroring of given values', () => {
		it( 'mirrows usePublish', () => {
			const config = new BridgeConfig( { usePublish: true } );
			expect( config.usePublish ).toBe( true );
		} );

		describe( 'stringMaxLength', () => {
			it( 'mirrows null if no value is provided', () => {
				const config = new BridgeConfig( { usePublish: false } );
				expect( config.stringMaxLength ).toBeNull();
			} );

			it( 'mirrows the given value', () => {
				const maxLength = 12345;
				const config = new BridgeConfig( {
					usePublish: false,
					dataTypeLimits: {
						string: { maxLength },
					},
				} );
				expect( config.stringMaxLength ).toBe( maxLength );
			} );
		} );
	} );
} );
