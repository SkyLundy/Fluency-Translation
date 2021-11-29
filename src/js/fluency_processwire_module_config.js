
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
      alert('fired translate trigger')
    })
  }

  var _bindClearCacheTrigger = function() {
    document.querySelector('.js-fluency-clear-module-cache').addEventListener('click', function(e) {
      e.preventDefault()
      alert('fired clear cache trigger')
    })
  }

  return {
    init: init
  }
}())

// Initialize all modules when the DOM is ready
window.addEventListener('load', Fluency.ProcessWireModuleConfig.init)