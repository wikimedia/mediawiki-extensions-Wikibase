--[[
	Registers and defines an extension of Capiunto which offers
	a convenient way to add statements to infoboxes

	@since 0.5

	@license GNU GPL v2+
	@author Bene* < benestar.wikimedia@gmail.com >
]]

local capiunto = require 'capiunto'
local util = require 'libraryUtil'

-- Callback function to render references
local referenceRenderer

-- Adds a row containing the best statements found for the given property.
-- The parameter can be the id or the label of a property. Calls addRow internally.
--
-- @param t
-- @param property
function addStatement( t, property )
	local value = ''
	local frame = mw.getCurrentFrame()

	-- @todo frame is nil in unit tests only
	if frame ~= nil and frame.args[property] then
		value = frame.args[property]
	else
		local entity = mw.wikibase.getEntity()
		local propertyId = mw.wikibase.resolvePropertyId( property )

		if propertyId ~= nil then
			value = renderStatements( entity:getBestStatements( propertyId ) )
		end
	end

	if value ~= '' then
		t:addRow( property, value )
	end

	return t
end

-- Renders a list of statements.
--
-- @param statements
function renderStatements( statements )
	local value = ''
	local frame = mw.getCurrentFrame()

	for k, statement in pairs( statements ) do
		if value ~= '' then
			value = value .. tostring( mw.message.new( 'comma-separator' ) )
		end

		-- @todo frame is nil in unit tests only
		if frame ~= nil then
			value = value .. frame:preprocess(
				mw.wikibase.renderSnak( statement.mainsnak )
			)
		else
			value = value .. mw.wikibase.renderSnak( statement.mainsnak )
		end

		if statement.references then
			value = value .. renderReferences( statement.references )
		end
	end

	return value
end

-- Renders a list of references.
--
-- @param references
function renderReferences( references )
	local value = ''
	local frame = mw.getCurrentFrame()

	for k, reference in pairs( references ) do
		local renderedReference = referenceRenderer( reference.snaks )

		if renderedReference then
			-- @todo frame is nil in unit tests only
			if frame ~= nil then
				value = value .. frame:preprocess(
					'<ref name="' .. reference.hash .. '">' .. renderedReference .. '</ref>'
				)
			else
				value = value .. '<ref name="' .. reference.hash .. '">' .. renderedReference .. '</ref>'
			end
		end
	end

	return value
end

-- Default renderer which just calls renderSnaks.
--
-- @param snaks
function defaultReferenceRenderer( snaks )
	return mw.wikibase.renderSnaks( snaks )
end

local create = capiunto.create

-- Calls the standard capiunto create function and adds an addStatement method.
--
-- @param options
capiunto.create = function( options )
	local infobox = create( options )
	infobox.addStatement = addStatement

	options = options or {}
	referenceRenderer = options.referenceRenderer or defaultReferenceRenderer
	util.checkType( 'referenceRenderer', 1, referenceRenderer, 'function' )

	return infobox
end

mw.wikibase.capiunto = capiunto
package.loaded['mw.wikibase.capiunto'] = capiunto

return capiunto
