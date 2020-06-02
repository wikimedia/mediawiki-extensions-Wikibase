interface DataBridgeConfig {
	/** string in RegExp syntax to match edit link hrefs */
	hrefRegExp: string;
	/** strings for tagging the edit */
	editTags: readonly string[];
	/** bool for switch between publish and save on the button label */
	usePublish: boolean;
	/** URL where the user can report a problem with the Data Bridge. May include placeholder for body: `<body>`. */
	issueReportingLink: string;
}

export default DataBridgeConfig;
