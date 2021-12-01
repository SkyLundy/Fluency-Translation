
var Fluency = Fluency || {}



/**
 * These are common tools the Fluency translation module uses
 * It is loaded first in order of scripts added to the page so that the module
 * is available to all subsequent scripts
 *
 * @return {Object}   Object containing public methods
 */
Fluency.Tools = (function() {

  /**
   * Makes an ajax GET call to the module. General purpose utility function to
   * package all calls up into a standard format.
   * NOTE: The requestData object must have a req property containing an ID string
   * that is defined in the ___executeData method in Fluency.module.php
   *
   * @param  {object}   requestData Contains key/val request data for Fluency
   *                                module.
   * @param  {function} callback    Function to be executed when data is returned
   *                                from module
   * @return {void}
   */
  var moduleRequest = function(requestData, callback) {
    var winLoc = window.location,
        params = [],
        requestUrl = winLoc.protocol + '//' +
                     winLoc.host + '/' +
                     winLoc.pathname.split('/')[1] + '/fluency/data/'

    // Parse requestData object and create key=value URL parameters
    for (var item in requestData) {
      if ({}.hasOwnProperty.call(requestData, item)) {
        var dataValue = requestData[item],
            cleanData = dataValue.replace(/(\r\n|\n|\r)/gm,"")
        params.push(item + '=' + encodeURIComponent(cleanData))
      }
    }

    var data = params.join('&');

    // Kick it off
    var xhr = new XMLHttpRequest()

    xhr.open('POST', requestUrl)
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded')

    xhr.onload = function() {
      if (typeof callback === 'function') {
        callback(null, JSON.parse(xhr.response))
      }
    }

    xhr.onerror = function() {
      if (typeof callback === 'function') {
        callback(xhr.response)
      }
    }

    xhr.send(data)
  }

  /**
   * This updates the value of an input
   * When a field is updated it must be focused so that it is recognized as
   * having been changed. If it is not focused then you will not see a message
   * about leaving with unsaved changes when changes have been made but are not
   * saved.
   *
   * @param  {HTMLElement} input Input element to have value set
   * @param  {string}      value
   * @return {void}
   */
  var updateInputValue = function(input, value) {
    input.focus()
    input.value = value
    input.blur()
  }

  /**
   * Helper method that gets the associated language object from an array of
   * target langauges from the module
   * data and returns it's object
   *
   * @param  {int}    id    ProcessWire language ID
   * @return {object|null}  Associated language object from targetLanguages or
   *                         null if not found
   */
  var getTargetLanguageById = function(pwLanguageId, targetLanguages) {
    var targetLanguage = null,
        pwLanguageId = parseInt(pwLanguageId, 10)

    for (var i = 0; i < targetLanguages.length; i++) {
      var language = targetLanguages[i]

      if (language.processWire.id === pwLanguageId) {
        targetLanguage = language
        break
      }
    }

    return targetLanguage
  }

  /**
   * Creates an activity overlay element for insertion into DOM.
   * Style/animations expect any message
   *
   * @param  {array}       Array of texts to add to animated message
   * @return {HTMLElement} Element to be inserted into DOM
   */
  var createActivityOverlay = function(messageType) {
    var messages = {
      translating: [
        'Translating...',
        'Übersetzen...',
        'Traduire...',
        'Traduciendo...',
        'Traduzir...',
        'Traduzione...',
        'Vertaling...',
        'Tłumaczenie...',
        'Перевод...'
      ]
    }

    var overlay = document.createElement('div')
        overlay.setAttribute('class', 'fluency-activity-overlay')

    overlay.setAttribute('data-gradient-1', 'rgba(62, 185, 152, .85)')

    messages[messageType].forEach(function(message) {
      var overlayText = document.createElement('span')
          overlayText.setAttribute('class', 'fluency-overlay-animation-item')
          overlayText.innerHTML = message

      overlay.appendChild(overlayText)
    })

    // Create a message element that can be targeted to hold a message
    var message = document.createElement('span')
        message.setAttribute('class', 'fluency-overlay-message')

    overlay.appendChild(message)

    return overlay
  }

  /**
   * Shows the translation activity overlay element passed
   * @param HTMLElement Activity overlay to be shown
   * @return {void}
   */
  var showActivityOverlay = function(overlayEl) {
    overlayEl.classList.add('active')
  }

  /**
   * Hides the translation activity overlay element passed
   * @param HTMLElement Activity overlay to be hidden
   * @return {void}
   */
  var hideActivityOverlay = function(overlayEl) {
    overlayEl.classList.remove('active')
  }

  /**
   * Gets the page ID from the current URL
   * @return {int|null} Page ID number, null if not present
   */
  var getPageId = function() {
    var urlParams = new URLSearchParams(window.location.search)

    return urlParams ? parseInt(urlParams.get('id')) : null
  }

  /**
   * Checks page ID against page ID passed
   * @param  {int}  id ID to check against
   * @return {bool}    True/false whether is current page ID
   */
  var pageIdIs = function(id) {
    return getPageId() === id
  }

  return {
    moduleRequest: moduleRequest,
    getTargetLanguageById: getTargetLanguageById,
    createActivityOverlay: createActivityOverlay,
    showActivityOverlay: showActivityOverlay,
    hideActivityOverlay: hideActivityOverlay,
    getPageId: getPageId,
    pageIdIs: pageIdIs,
    updateInputValue: updateInputValue
  };
}());
