export interface WellFormedResponse {
	entities: {
		[ entityId: string ]: EntityWithLabelData;
	};
}

export interface EntityWithLabelData {
	labels: {
		[ lang: string ]: {
			language: string;
			value: string;
			'for-language'?: string;
		};
	};
}

export default interface EntityInfoDispatcher {
	dispatchEntitiesInfoRequest( requestData: {
		props: string[];
		ids: string[];
		otherParams?: { [ paramKey: string ]: number | string };
	} ): Promise<WellFormedResponse['entities']>;
}
