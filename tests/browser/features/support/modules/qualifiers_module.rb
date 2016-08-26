# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for qualifiers page object

module QualifierPage
  include PageObject
  # qualifiers UI elements
  div(:qualifiers_container, class: 'wb-claim-qualifiers')
  a(:add_qualifier, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/span[contains(@class, 'wb-addtoolbar')]/div/span/span/a[text()='add qualifier']")
  a(:remove_qualifier_line1, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/div[contains(@class, 'wikibase-snaklistview-listview')]/div[contains(@class, 'wikibase-snakview')][1]/span[contains(@class, 'wb-removetoolbar')]/div/span/span/a[text()='remove']")
  a(:remove_qualifier_line2, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/div[contains(@class, 'wikibase-snaklistview-listview')]/div[contains(@class, 'wikibase-snakview')][2]/span[contains(@class, 'wb-removetoolbar')]/div/span/span/a[text()='remove']")
  textarea(:qualifier_value_input1, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]//textarea[contains(@class, 'valueview-input')]", index: 0)
  textarea(:qualifier_value_input2, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]//textarea[contains(@class, 'valueview-input')]", index: 1)

  a(:qualifier_property_link1, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/div[contains(@class, 'wikibase-snaklistview-listview')]/div[contains(@class, 'wikibase-snakview')][1]/div[contains(@class, 'wikibase-snakview-property-container')]/div/a")
  a(:qualifier_property_link2, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/div[contains(@class, 'wikibase-snaklistview-listview')]/div[contains(@class, 'wikibase-snakview')][2]/div[contains(@class, 'wikibase-snakview-property-container')]/div/a")
  div(:qualifier_property1, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/div[contains(@class, 'wikibase-snaklistview-listview')]/div[contains(@class, 'wikibase-snakview')][1]/div[contains(@class, 'wikibase-snakview-property-container')]/div")
  div(:qualifier_property2, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/div[contains(@class, 'wikibase-snaklistview-listview')]/div[contains(@class, 'wikibase-snakview')][2]/div[contains(@class, 'wikibase-snakview-property-container')]/div")
  a(:qualifier_value_link1, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/div[contains(@class, 'wikibase-snaklistview-listview')]/div[contains(@class, 'wikibase-snakview')][1]/div[contains(@class, 'wikibase-snakview-value-container')]/div[contains(@class, 'wikibase-snakview-value')]/div/div/a")
  a(:qualifier_value_link2, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/div[contains(@class, 'wikibase-snaklistview-listview')]/div[contains(@class, 'wikibase-snakview')][2]/div[contains(@class, 'wikibase-snakview-value-container')]/div[contains(@class, 'wikibase-snakview-value')]/div/div/a")
  div(:qualifier_value1, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/div[contains(@class, 'wikibase-snaklistview-listview')]/div[contains(@class, 'wikibase-snakview')][1]/div[contains(@class, 'wikibase-snakview-value-container')]/div[contains(@class, 'wikibase-snakview-value')]/div/div")
  div(:qualifier_value2, xpath: "//div[contains(@class, 'wb-claim-qualifiers')]/div[contains(@class, 'wikibase-snaklistview')]/div[contains(@class, 'wikibase-snaklistview-listview')]/div[contains(@class, 'wikibase-snakview')][2]/div[contains(@class, 'wikibase-snakview-value-container')]/div[contains(@class, 'wikibase-snakview-value')]/div/div")

  def wait_for_qualifier_value_box
    wait_until do
      qualifier_value_input1?
    end
  end

  def add_qualifier_to_first_claim(property, value)
    edit_first_statement
    add_qualifier
    self.entity_selector_input = property
    ajax_wait
    wait_for_entity_selector_list
    wait_for_qualifier_value_box
    self.qualifier_value_input1 = value
    ajax_wait
    save_statement
    ajax_wait
    wait_for_statement_request_finished
  end
end
