
var Fluency = Fluency || {}


/**
 * This handles any ProcessWire module configuration actions
 *
 * NOTE: This relies on the FluencyTools JSs module. Ensure that it is loaded into the page first
 *
 * @return {object} Public methods
 */
Fluency.ProcessWireModuleConfig = (function() {

  /**
   * These classes visually change the appearance of ProcessWire button
   * states. The button must still have it's disabled attribute set
   * @type {Object}
   */
  var uiButtonClass = {
    enabled: 'ui-state-default',
    disabled: 'ui-state-disabled',
  }

  /**
   * Initializes module
   * @return {void}
   */
  var init = function() {
    _bindTranslateTrigger()
    _bindClearCacheTrigger()
  }

  var _bindTranslateTrigger = function() {
    document.querySelector('.js-fluency-translate-module').addEventListener('click', function(e) {
      e.preventDefault()
      Fluency.Tools.moduleRequest({req: 'localizeModule'})
    })
  }

  var _bindClearCacheTrigger = function() {
    document.querySelector('.js-fluency-clear-module-cache').addEventListener('click', function(e) {
      e.preventDefault()
      Fluency.Tools.moduleRequest({req: 'clearModuleLocalization'})
    })
  }

  return {
    init: init
  }
}())

// Initialize all modules when the DOM is ready
window.addEventListener('load', Fluency.ProcessWireModuleConfig.init)