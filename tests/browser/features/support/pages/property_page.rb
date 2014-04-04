# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for property page

class PropertyPage
  include PageObject
  include EntityPage

  # ***** METHODS *****
  def create_property_data(props)
    property_data = Hash.new
    props.raw.each do |prop|
      handle = prop[0]
      type = prop[1]
      label = generate_random_string(8)
      description = generate_random_string(20)
      data = '{"labels":{"en":{"language":"en","value":"' + label +
          '"}},"descriptions":{"en":{"language":"en","value":"' + description +
          '"}},"datatype":"' + type + '"}'
      property_data[handle] = data
    end

    property_data
  end

end
