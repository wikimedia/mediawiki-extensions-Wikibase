'use strict';

module.exports = {
	"ItemStatementResponse": {
		"id": "Q24$9966A1CA-F3F5-4B1D-A534-7CD5953169DA",
		"rank": "normal",
		"property": {
			"id": "P17",
			"data_type": "string"
		},
		"value": {
			"type": "value",
			"content": "Senior Team Supervisor"
		},
		"qualifiers": [
			{
				"property": {
					"id": "P706",
					"data_type": "time"
				},
				"value": {
					"type": "value",
					"content": {
						"time": "+2023-06-13T00:00:00Z",
						"precision": 11,
						"calendarmodel": "http://www.wikidata.org/entity/Q1985727"
					}
				}
			}
		],
		"references": [
			{
				"hash": "7ccd777f870b71a4c5056c7fd2a83a22cc39be6d",
				"parts": [
					{
						"property": {
							"id": "P709",
							"data_type": "url"
						},
						"value": {
							"type": "value",
							"content": "https://news.example.org"
						}
					}
				]
			}
		]
	},
	"PropertyStatementResponse": {
		"id": "P694$B4C349A2-C504-4FC5-B7D5-8B781C719D71",
		"rank": "normal",
		"property": {
			"id": "P1628",
			"data_type": "url"
		},
		"value": {
			"type": "value",
			"content": "http://www.w3.org/1999/02/22-rdf-syntax-ns#type"
		},
		"qualifiers": [],
		"references": []
	},
	"PatchItemStatementRequest": {
		"patch": [
			{
				"op": "add",
				"path": "/references/-",
				"value": {
					"parts": [
						{
							"property": { "id": "P709" },
							"value": {
								"type": "value",
								"content": "https://news.example.org"
							}
						}
					]
				}
			}
		],
		"tags": [],
		"bot": false,
		"comment": "Add reference to Statement"
	},
	"PatchPropertyStatementRequest": {
		"patch": [
			{
				"op": "replace",
				"path": "/value/content",
				"value": "http://www.w3.org/1999/02/22-rdf-syntax-ns#type"
			}
		],
		"tags": [],
		"bot": false,
		"comment": "Update value of the 'equivalent property' Statement"
	}
};
