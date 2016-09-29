--[[
	Tests for pages that are not connected to any Item.

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
]]

local testframework = require 'Module:TestFramework'

local tests = {
	-- Integration tests

	{ name = "mw.wikibase.getEntityIdForCurrentPage returns nil", func = mw.wikibase.getEntityIdForCurrentPage,
	  expect = { nil }
	},
	{ name = "mw.wikibase.getEntityObject returns nil", func = mw.wikibase.getEntityObject,
	  expect = { nil }
	},
	{ name = "mw.wikibase.getEntityUrl returns nil", func = mw.wikibase.getEntityUrl,
	  expect = { nil }
	},
	{ name = "mw.wikibase.label returns nil", func = mw.wikibase.label,
	  expect = { nil }
	},
	{ name = "mw.wikibase.getLabelWithLang returns nil, nil", func = mw.wikibase.getLabelWithLang,
	  expect = { nil, nil }
	},
	{ name = "mw.wikibase.description returns nil", func = mw.wikibase.description,
	  expect = { nil }
	},
	{ name = "mw.wikibase.getDescriptionWithLang returns nil, nil", func = mw.wikibase.getDescriptionWithLang,
	  expect = { nil, nil }
	},
}

return testframework.getTestProvider( tests )
