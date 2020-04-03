--[[
	Calls various mw.wikibase/mw.wikibase.entity functions to create stats tracking data

	@license GNU GPL v2+
	@author Marius Hoch
]]

local testframework = require 'Module:TestFramework'

local function doCalls()
	-- Tracks one call
	mw.wikibase.getDescription( 'Q15862' )
	-- Tracks one call
	mw.wikibase.resolvePropertyId( 'blah blah blah' )
	-- Tracks three calls (getEntity, getEntityIdForCurrentPage, entity.getSitelink)
	mw.wikibase.getEntity( 'Q32487' ):getSitelink( 'eswiki' )
end

local tests = {
	{ name = "Call to various mw.wikibase/mw.wikibase.entity functions", func = doCalls,
	  expect = { nil }
	},
}

return testframework.getTestProvider( tests )
