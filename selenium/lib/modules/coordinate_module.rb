# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for coordinate page object

module CoordinatePage
  include PageObject
  # time UI elements
  div(:coordinateInputExtender, :class => "ui-inputextender-extension")
  div(:coordinateInputExtenderClose, :class => "ui-inputextender-extension-close")
  div(:coordinatePreview, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-preview')]")
  div(:coordinatePreviewLabel, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-preview')]/div[contains(@class, 'valueview-preview-label')]")
  div(:coordinatePreviewValue, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-preview')]/div[contains(@class, 'valueview-preview-value')]")
  #div(:timeCalendarHint, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarhint')]")
  #span(:timeCalendarHintMessage, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarhint')]/span[contains(@class, 'valueview-expert-timeinput-calendarhint-message')]")
  #link(:timeCalendarHintSwitch, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarhint')]/span[contains(@class, 'valueview-expert-timeinput-calendarhint-switch')]")
  link(:coordinateInputExtenderAdvanced, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/a[contains(@class, 'valueview-expert-globecoordinateinput-advancedtoggler')]")
  div(:coordinatePrecision, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]")
  link(:coordinatePrecisionRotatorAuto, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-auto')]")
  link(:coordinatePrecisionRotatorPrev, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-prev')]")
  link(:coordinatePrecisionRotatorNext, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-next')]")
  link(:coordinatePrecisionRotatorSelect, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-globecoordinateinput-precision')]/a[contains(@class, 'ui-listrotator-curr')]")
  #div(:timeCalendar, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]")
  #link(:timeCalendarRotatorAuto, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-auto')]")
  #link(:timeCalendarRotatorPrev, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-prev')]")
  #link(:timeCalendarRotatorNext, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-next')]")
  #link(:timeCalendarRotatorSelect, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-curr')]")
  unordered_list(:coordinatePrecisionMenu, :class => "ui-listrotator-menu", :index => 0)
  #unordered_list(:timeCalendarMenu, :class => "ui-listrotator-menu", :index => 1)
  # methods
  def select_precision prec
    self.show_advanced_time_settings
    if prec == "auto"
      self.timePrecisionRotatorAuto
      return
    end
    self.coordinatePrecisionRotatorSelect
    self.coordinatePrecisionMenu_element.when_visible
    self.coordinatePrecisionMenu_element.each do |item|
      if item.text == prec
        item.click
        return
      end
    end
  end

  def show_advanced_coordinate_settings
    if !self.timePrecision_element.visible?
      self.coordinateInputExtenderAdvanced
      self.coordinatePrecision_element.when_visible
    end
  end
end
