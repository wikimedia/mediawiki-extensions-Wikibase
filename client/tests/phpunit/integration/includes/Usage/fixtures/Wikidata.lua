local p = {}

function p.claim(frame)
	local property = frame.args[1]
	local id = frame.args["id"]

    -- get entity
	local entity = mw.wikibase.getEntity(id)
	if not entity then
		return "ERROR: entity not found"
	end

    -- get claims
	local claims
	if entity.claims then claims = entity.claims[property] end
	if not claims or not claims[1] then
		return "ERROR: property not found"
	end

    -- return value of mainsnak (assume it is a string)
    local snak = claims[1].mainsnak
    return snak.datavalue.value
end

return p