import {
	DataType,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import { ApiResponse } from '@/definitions/data-access/Api';

export interface ApiResponseEntity {
	id: string;
	missing?: ''; // string '' instead of boolean true – see T145050
}

export interface ApiWbgetentitiesResponse extends ApiResponse {
	entities: {
		[entityId: string]: ApiResponseEntity;
	};
}

export interface PartialEntity extends ApiResponseEntity {
	type: string;
}

export interface EntityWithDataType extends PartialEntity {
	datatype: DataType;
}

export interface EntityWithLabels extends PartialEntity {
	labels: {
		[lang: string]: {
			language: string;
			value: string;
			'for-language'?: string;
		};
	};
}

export interface EntityWithInfo extends PartialEntity {
	lastrevid: number;
}

export interface EntityWithClaims extends PartialEntity {
	claims: StatementMap;
}
