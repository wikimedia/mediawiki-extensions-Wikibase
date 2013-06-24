# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for coordinate page object

module CoordinatePage
  include PageObject
  # coordinate UI elements
  link(:coordinateInputExtenderAdvanced, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/a[contains(@class, 'valueview-expert-globecoordinateinput-advancedtoggler')]")
  div(:coordinatePrecision, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]")
  link(:coordinatePrecisionRotatorAuto, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-auto')]")
  link(:coordinatePrecisionRotatorPrev, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-prev')]")
  link(:coordinatePrecisionRotatorNext, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-next')]")
  link(:coordinatePrecisionRotatorSelect, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-curr')]")
  unordered_list(:coordinatePrecisionMenu, :class => "ui-listrotator-menu", :index => 0)
  # methods
  def select_coordinate_precision prec
    self.show_advanced_coordinate_settings
    if prec == "auto"
      self.coordinatePrecisionRotatorAuto
      return
    end
    self.coordinatePrecisionRotatorSelect
    self.coordinatePrecisionMenu_element.when_visible(10)
    self.coordinatePrecisionMenu_element.each do |item|
      if item.text == prec
        item.click
        return
      end
    end
  end

  def show_advanced_coordinate_settings
    if !self.coordinatePrecision_element.visible?
      self.coordinateInputExtenderAdvanced
      self.coordinatePrecision_element.when_visible(10)
    end
  end
end
