--[[
	Integration tests verifiying that arbitrary data access doesn't work, if it's disabled.

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
]]

local testframework = require 'Module:TestFramework'

local tests = {
	-- Integration tests

	{ name = "mw.wikibase.getEntityObject (foreign access)", func = mw.wikibase.getEntityObject,
	  args = { 'Q42' },
	  expect = 'Access to arbitrary entities has been disabled.'
	},
	{ name = "mw.wikibase.getBestStatements (foreign access)", func = mw.wikibase.getBestStatements,
	  args = { 'Q42', 'P12' },
	  expect = 'Access to arbitrary entities has been disabled.'
	},
	{ name = "mw.wikibase.getAllStatements (foreign access)", func = mw.wikibase.getAllStatements,
	  args = { 'Q42', 'P12' },
	  expect = 'Access to arbitrary entities has been disabled.'
	},
	{ name = "mw.wikibase.entityExists (foreign access)", func = mw.wikibase.entityExists,
	  args = { 'Q42' },
	  expect = 'Access to arbitrary entities has been disabled.'
	},
	{ name = "mw.wikibase.getBadges (foreign access)", func = mw.wikibase.getBadges,
	  args = { 'Q42' },
	  expect = 'Access to arbitrary entities has been disabled.'
	},
	{ name = "mw.wikibase.getReferencedEntityId", func = mw.wikibase.getReferencedEntityId,
	  args = { 'Q42', 'P42', { 'Q2' } },
	  expect = 'Access to arbitrary entities has been disabled.'
	},
}

return testframework.getTestProvider( tests )
