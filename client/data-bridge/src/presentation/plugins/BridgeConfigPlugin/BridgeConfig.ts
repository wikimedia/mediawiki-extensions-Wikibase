import WikibaseClientConfiguration from '@/definitions/WikibaseClientConfiguration';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';

export type BridgeConfigOptions = WikibaseClientConfiguration & Partial<WikibaseRepoConfiguration>;

export default class BridgeConfig {
	public readonly usePublish: boolean;
	public readonly stringMaxLength: number | null;

	public constructor( config: BridgeConfigOptions ) {
		this.usePublish = config.usePublish;
		this.stringMaxLength = config.dataTypeLimits?.string.maxLength || null;
	}
}
