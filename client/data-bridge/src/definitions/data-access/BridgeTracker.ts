export default interface BridgeTracker {
	trackPropertyDatatype( datatype: string ): void;
	trackTitlePurgeError(): void;
	trackError( type: string ): void;
	trackUnknownError( type: string ): void;
	trackSavingUnknownError( type: string ): void;
}
