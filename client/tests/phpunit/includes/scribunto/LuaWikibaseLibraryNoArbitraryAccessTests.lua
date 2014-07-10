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
	  expect = 'Access to arbitrary items has been disabled.'
	}
}

return testframework.getTestProvider( tests )
