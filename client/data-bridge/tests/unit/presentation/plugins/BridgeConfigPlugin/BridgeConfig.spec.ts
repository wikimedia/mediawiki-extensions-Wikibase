import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin/BridgeConfig';

describe( 'BridgeConfig', () => {
	it( 'fails if invalid options have been provided', () => {
		expect( () => new BridgeConfig( undefined as any ) ).toThrowError();
	} );

	it( 'mirrors given values', () => {
		const config = new BridgeConfig( true );
		expect( config.usePublish ).toBe( true );
	} );
} );
