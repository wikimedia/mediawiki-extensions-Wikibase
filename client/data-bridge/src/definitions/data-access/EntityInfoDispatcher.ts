import DataType from '@/datamodel/DataType';

export interface WellFormedResponse {
	entities: {
		[ entityId: string ]: EntityWithLabelData | EntityResponseWithDataType;
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

export interface EntityResponseWithDataType {
	datatype: DataType;
}

export default interface EntityInfoDispatcher {
	dispatchEntitiesInfoRequest( requestData: {
		props: string[];
		ids: string[];
		otherParams?: { [ paramKey: string ]: number | string };
	} ): Promise<WellFormedResponse['entities']>;
}
