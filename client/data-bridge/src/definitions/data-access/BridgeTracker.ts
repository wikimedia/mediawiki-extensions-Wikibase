export default interface BridgeTracker {
	trackPropertyDatatype( datatype: string ): void;
	trackError( type: string ): void;
	trackRecoveredError( type: string ): void;
	trackUnknownError( type: string ): void;
	trackSavingUnknownError( type: string ): void;
}
