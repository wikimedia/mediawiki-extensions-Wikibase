import BridgeTracker from '@/definitions/data-access/BridgeTracker';

export default function ( methods?: Partial<BridgeTracker> ): BridgeTracker {
	return {
		...{
			trackPropertyDatatype: jest.fn(),
			trackError: jest.fn(),
			trackRecoveredError: jest.fn(),
			trackUnknownError: jest.fn(),
			trackSavingUnknownError: jest.fn(),
		},
		...methods,
	};
}
