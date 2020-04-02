import WikibaseClientConfiguration from '@/definitions/WikibaseClientConfiguration';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';

export type BridgeConfigOptions = WikibaseClientConfiguration & Partial<WikibaseRepoConfiguration>;

export default class BridgeConfig {
	public readonly usePublish: boolean;
	public readonly issueReportingLink: string;
	public readonly stringMaxLength: number | null;
	public readonly dataRightsText: string | null;
	public readonly dataRightsUrl: string | null;
	public readonly termsOfUseUrl: string | null;

	public constructor( config: BridgeConfigOptions ) {
		this.usePublish = config.usePublish;
		this.issueReportingLink = config.issueReportingLink;
		this.stringMaxLength = config.dataTypeLimits?.string.maxLength ?? null;
		this.dataRightsText = config.dataRightsText ?? null;
		this.dataRightsUrl = config.dataRightsUrl ?? null;
		this.termsOfUseUrl = config.termsOfUseUrl ?? null;
	}
}
