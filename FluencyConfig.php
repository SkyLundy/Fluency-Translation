<?php namespace ProcessWire;

// FileCompiler=0

/**
 * Module settings configuration file
 */
require_once 'classes/FluencyTools.class.php';
require_once 'classes/DeepL.class.php';

class FluencyConfig extends ModuleConfig {


  /**
   * Permission required to use Fluency in the admin
   * @var string
   */
  private $fluencyPermission = 'fluency-translate';

  /**
   * Config variable default values
   *
   * @return array Default key/values for module configuration
   */
  public function getDefaults() {
    return [
      'deepl_api_key' => '',
      'api_key_valid' => 'invalid'
    ];
  }

  /**
   * Gets input fields from configuration page.
   * Calls API for key check and data
   *
   * @return InputfieldWrapper Object for output to module config page.
   */
  public function getInputFields(): InputfieldWrapper {
    $inputfields = parent::getInputFields();
    $fluencyTools = new FluencyTools;
    $fluencyModule = $this->modules->get('Fluency');
    $moduleConfig = $this->modules->getModuleConfigData('Fluency');
    $deeplSourceLanguages = null;
    $deeplTargetLanguages = null;
    $deeplApiUsage = null;
    $apiKeyIsValid = false;
    $deepL = null;
    $apiKeyCheckMessage = null;
    $apiHttpResponse = null;

    ///////////////////////////////
    // API key check & API calls //
    //////////////////////////////
    // Check if there's an api key
    // Use an API usage call as a key test
    // If successful, set data variables used below
    if ($fluencyModule->deepl_api_key) {
      // Instantiate a new DeepL class instance to access the API
      $deepL = new DeepL([
        'apiKey' => $fluencyModule->deepl_api_key
      ]);

      // Use API usage as key test
      $request = $deepL->getApiUsage();
      $apiHttpResponse = $request->httpCode;

      // Check API response code for unauthorize failure and set variable if
      // API key is good
      if ($apiHttpResponse !== 403) {
        $apiKeyIsValid = true;
        $deeplApiUsage = $request->data;
        // Source/target languages are differentiated to accurately identify
        // what is available via the DeepL API as it may change when it is
        // updated or upgraded at a alter date.
        $deeplSourceLanguages = $deepL->getLanguageList('source')->data;
        $deeplTargetLanguages = $deepL->getLanguageList('target')->data;
      } else {
        $apiKeyCheckMessage = $request->message;
      }

      $moduleConfig['api_key_valid'] = $apiKeyIsValid;

      $this->modules->saveModuleConfigData('Fluency', $moduleConfig);
    }

    /////////////////////////
    // DeepL API key field //
    /////////////////////////
    $field = $this->modules->get('InputfieldText');
    $field->name = "deepl_api_key";
    $field->label = __("DeepL API Key");
    $field->description = __('Fluency relies on the DeepL developer API which is a paid service. Find out more here: https://www.deepl.com');
    $field->required = true;

    // Control API key field visibility && error
    if (!$fluencyModule->deepl_api_key) {
      $field->notes = __('A valid API key must be present to configure this module.');
    } elseif ($fluencyModule->deepl_api_key && !$apiKeyIsValid) {
      $field->error($apiKeyCheckMessage);
    } elseif ($fluencyModule->deepl_api_key && $apiKeyIsValid) {
      $field->notes = __('API key validated');
      $field->collapsed = true;
    }

    $inputfields->add($field);

    // We only want to allow further configuration of the module if the DeepL
    // API key has been added to the configuration.
    if (!$fluencyModule->deepl_api_key || !$apiKeyIsValid) return $inputfields;

    ////////////////////////
    // Module information //
    ////////////////////////
    $content = '<h3>Fluency - ' . __("A DeepL neural machine translation service integration for ProcessWire") . '</h3>';

    $content .= '<p>' . __("DeepL is a highly accurate and powerful neural machine translation service which produces some of the most fluent and natural automated langauge translations available, trouncing anything provided by Google or Microsoft. This module connects to this service via the DeepL API and enhances ProcessWire's core multi-language fields by making them one-click translatable. In addition to translating text and textarea fields, DeepL supports translating text inside HTML/XML markup which makes this translation ability available to CKEditors.") . '</p>';

    $content .= '<p>' . __("Please read the API Documentation on the DeepL website to understand the service better and learn what to expect when translating. This module uses DeepL API v2") . '</p>';

    $content .= '<p><a href="https://www.deepl.com/docs-api/" target="_blank" rel="noopener">DeepL API documentation</a></p>';

    // Create field for markup
    $field = $this->modules->get('InputfieldMarkup');
    $field->label = __('Information');
    $field->value = $content;
    $inputfields->add($field);

    //////////////////////////
    // API Usage Statistics //
    //////////////////////////
    // Create markup for field
    $usageTable = $this->modules->get('MarkupAdminDataTable');
    $usageTable->sortable = false;
    $usageTable->headerRow([
      __('Character Limit'),
      __('Characters Translated'),
      __('Characters Remaining'),
      __('Total Usage')
    ]);

    $charLimit = $deeplApiUsage->character_limit;
    $charsUsed = $deeplApiUsage->character_count;
    $charsRemain = $deeplApiUsage->character_count;
    $totalUsage = round($charsUsed / $charLimit * 100);

    $usageTable->row([
      number_format($charLimit),
      number_format($charsUsed),
      number_format($charLimit - $charsUsed),
      number_format($totalUsage) . '%'
    ]);

    // Create field for markup
    $field = $this->modules->get('InputfieldMarkup');
    $field->label = __('DeepL API Account Usage For Current Billing Period');
    $field->value = $usageTable->render();;
    $inputfields->add($field);

    /**
     * The following two fields are markup fields that display the available
     * source languages that can be translated from and languages that can be
     * translated to. They are pulled from the API so are always the latest
     * available.
     *
     * The two markup fields will be added to fieldset after both have been
     * created.
     */

    ////////////////////////////////
    // Available source languages //
    ////////////////////////////////
    // This creates an <ol> list of languages that can be translated *from*
    // Create markup for field
    $items = '';

    foreach ($deeplSourceLanguages as $lang) {
      $items .= '<li>' . "{$lang->language} - {$lang->name}" . '</li>';
    }

    $fieldValue = '<ol>' . $items . '</ol>';

    // Create field and add markup
    $sourceLangMarkupField = $this->modules->get('InputfieldMarkup');
    $sourceLangMarkupField->label = __('Source Languages');
    $sourceLangMarkupField->notes = __('The language of the content you are translating from must match one of these.');
    $sourceLangMarkupField->value = $fieldValue;
    $sourceLangMarkupField->columnWidth = 50;
    // Added to fieldset below

    ////////////////////////////////
    // Available target languages //
    ////////////////////////////////
    // This creates an <ol> list of languages that can be translated *to*

    // Create markup for field
    $items = '';

    foreach ($deeplTargetLanguages as $lang) {
      $items .= '<li>' . "{$lang->language} - {$lang->name}" . '</li>';
    }

    $fieldValue = '<ol>' . $items . '</ol>';

    // Create field and add markup
    $targetLangMarkupField = $this->modules->get('InputfieldMarkup');
    $targetLangMarkupField->label = __('Destination Languages');
    $targetLangMarkupField->columnWidth = 50;
    $targetLangMarkupField->value = $fieldValue;
    // Added to fieldset below

    /////////////////////////////////////
    // From/To language group fieldset //
    /////////////////////////////////////
    // Create fieldset
    $fieldset = $this->modules->get('InputfieldFieldset');
    $fieldset->name = 'fieldset_language_associations';
    $fieldset->label = __('Available DeepL Languages');
    $fieldset->description = __('DeepL recognizes the following languages and may be used for translation. Source Languages are those which your content must be in to be translated. Destination Languages are those that DeepL will translate to.');

    // Add two markup columns with source/dest languages
    $fieldset->append($sourceLangMarkupField);
    $fieldset->append($targetLangMarkupField);
    $inputfields->add($fieldset);

    ////////////////////////////
    // Strings not translated //
    ////////////////////////////

    $field = $this->modules->get('InputfieldTextarea');
    $field->name = 'non_translated_strings';
    $field->label = __('Global Non-Translated Strings');
    $field->description = __('Add strings here that when present in content should not be translated. This is useful for things like brand names. For example, if the name of a company is Red Rock Mountain Climbing and it should always appear that way, then adding that string here will prevent it from being translated globally.');
    $field->notes = __('Provide multiple strings as comma separated values, values are case insensitive');
    $field->columnWidth = 50;
    $inputfields->add($field);

    ////////////////////////////////
    // Preserve Formatting Option //
    ////////////////////////////////
    $field = $this->modules->get('InputfieldSelect');
    $field->name = 'api_param_preserve_formatting';
    $field->label = __('Preserve Formatting');
    $field->description = __('This ensures strict respect to formatting and prevents DeepL from adding/removing punctuation or attempting to correct content');
    $field->notes = __('DeepL API parameter: preserve_formatting');
    $field->required = true;
    $field->columnWidth = 50;
    $field->addOption(1, 'Yes (Recommended)');
    $field->addOption(0, 'No (DeepL default)');
    $inputfields->add($field);

    /////////////////////////////
    // Language Configurations //
    /////////////////////////////
    // ===== Create fieldset
    $fieldset = $this->modules->get('InputfieldFieldset');
    $fieldset->name = 'fieldgroup_language_associations';
    $fieldset->label = __('Language Translation Associations');
    $fieldset->description = __('Select a DeepL translation language that matches languages configured in ProcessWire');

    $userLanguage = $this->user->language->name;

    // All language names that exist in ProcessWire
    $pwLanguageNames = [];

    // Put 'em all in an array
    foreach ($this->languages as $language) {
      $pwLanguageNames[] = $language->getLanguageValue('default', 'name');
    }

    ///////////////////////////
    // Language Associations //
    ///////////////////////////
    // Set up a language association for all languages present in PW
    // This will also assign a null value when new languages are found that
    // are not in the language associations value
    if (gettype($deeplTargetLanguages) == 'array' &&
        count($deeplTargetLanguages)) {
      foreach ($this->languages as $language) {
        // Get information from languages configured in ProcessWire
        $pwLanguageName = $language->getLanguageValue($userLanguage, 'name');
        $pwLanguageTitle = $language->getLanguageValue($userLanguage, 'title');
        $languageId = $language->id;

        // Create language select field
        $langSelectField = $this->modules->get('InputfieldSelect');
        $langSelectField->name = "pw_language_{$languageId}";

        // If this is the default language, build the options in the select
        // from the source languages.
        // Otherwise build the list from the destination languages
        if ($pwLanguageName === 'default') {
          $langSelectField->label = __('ProcessWire Language: ') .
                                    "{$pwLanguageTitle} " .
                                    __('(default)');
          $langSelectField->description = __('Translations will be made from this language into other languages.');
          $langSelectField->notes = __('This language must be listed in the source languages above to work properly.');
          $langSelectField->required = true;

          // Add each DeepL source language to the select field
          foreach ($deeplSourceLanguages as $lang) {
            $langSelectField->addOption($lang->language, $lang->name);
          }
        } else {
        // Create the language select to choose what language should be associated
        // with languages that exist in the CMS
        $langSelectField->label = __("ProcessWire Language: ") . $pwLanguageTitle;
        $langSelectField->description = __("DeepL language to associate with ") . $pwLanguageTitle;
        $langSelectField->columnWidth = 50;

          // Add each DeepL dest language to the select field
          foreach ($deeplTargetLanguages as $lang) {
            $langSelectField->addOption($lang->language, $lang->name);
          }
        }

        $fieldset->append($langSelectField);
      }

    }

    $inputfields->add($fieldset);

    ////////////////
    // Beg-a-Thon //
    ////////////////
    $content = '<h3>' . __("Want to brighten someone's day?") . '</h3>';

    $content .= '<p>' . __("Did you or your client find this module useful? Do you have cash just lying around? Did you sneak in a few extra bucks in your client contract to pass along to the module builders you love? Whatever the case, if you want to throw a tip my way, give that button a click! It will probably go towards bourbon.") . '</p>';

    $content .= '<a href="https://paypal.me/noonesboy" style="display: block; margin: 20px auto 0; width: 250px;" rel="noopener" target="_blank"><img src="/site/modules/Fluency/img/paypal_me.png" alt="PayPal Me"></a>';

    // Create field for content
    $field = $this->modules->get('InputfieldMarkup');
    $field->label = __('Beg-A-Thon');
    $field->value = $content;
    $inputfields->add($field);

    return $inputfields;
  }
}
