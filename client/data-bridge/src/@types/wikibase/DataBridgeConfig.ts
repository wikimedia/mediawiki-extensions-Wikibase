interface DataBridgeConfig {
	/** string in RegExp syntax to match edit link hrefs */
	hrefRegExp: string;
	/** strings for tagging the edit */
	editTags: string[];
	/** bool for switch between publish and save on the button label */
	usePublish: boolean;
}

export default DataBridgeConfig;
