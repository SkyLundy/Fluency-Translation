<?php namespace ProcessWire;

// FileCompiler=0

/**
 * Module settings configuration file
 */
require_once 'classes/FluencyTools.class.php';
require_once 'engines/DeepL.class.php';

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
      'deepl_account_type' => '',
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
        'apiKey' => $fluencyModule->deepl_api_key,
        'accountType' => $fluencyModule->deepl_account_type
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

    ///////////////////////////////////
    // DeepL API account type select //
    ///////////////////////////////////
    // DeepL offers two account levels, free and pro. Create a select to choose
    $field = $this->modules->get('InputfieldSelect');
    $field->name = 'deepl_account_type';
    $field->label = __('DeepL Account Type');
    $field->description = __('Select your DeepL account type');
    $field->required = true;
    $field->addOption('free', 'Free');
    $field->addOption('pro', 'Pro');
    $field->columnWidth = 50;

    if (!$fluencyModule->deepl_account_type) {
      $field->notes = __('Select and save to continue.');
    }

    if ($fluencyModule->deepl_account_type && $fluencyModule->deepl_api_key && $apiKeyIsValid) {
      $field->collapsed = true;
    }

    $inputfields->add($field);

    /////////////////////////
    // DeepL API key field //
    /////////////////////////
    $field = $this->modules->get('InputfieldText');
    $field->name = "deepl_api_key";
    $field->label = __("DeepL API Key");
    $field->description = __('An active DeepL Developer account is required. Ensure that your key and account type match.');
    $field->columnWidth = 50;
    $field->required = true;

    // Control API key field visibility && error

    // If no API key exists
    if (!$fluencyModule->deepl_api_key) {
      $field->notes = __('Enter and save to continue. Your API key will be validated.');
    }

    // If an API key is present but not valid
    if ($fluencyModule->deepl_api_key && !$apiKeyIsValid) {
      $field->error($apiKeyCheckMessage);
    }

    // If API key exists and is valid
    if ($fluencyModule->deepl_api_key && $apiKeyIsValid) {
      $field->notes = __('API key validated');
      $field->collapsed = true;
    }

    $inputfields->add($field);

    // We only want to allow further configuration of the module if the DeepL
    // account type has been selected and API key has been added.
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
    $charsRemain = $charLimit - $charsUsed;
    $totalUsage = round($charsUsed / $charLimit * 100);

    $usageTable->row([
      number_format($charLimit),
      number_format($charsUsed),
      number_format($charsRemain),
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
      $items .= "<li>{$lang->language} - {$lang->name}</li>";
    }

    $fieldValue = "<ol>{$items}</ol>";

    // Create field and add markup
    $sourceLangMarkupField = $this->modules->get('InputfieldMarkup');
    $sourceLangMarkupField->label = __('Source Languages');
    $sourceLangMarkupField->notes = __('The language of the content you are translating from must be listed here.');
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
      $items .= "<li>{$lang->language} - {$lang->name}</li>";
    }

    $fieldValue = "<ol>{$items}</ol>";

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

    ////////////////////////////
    // Translate Trigger Text //
    ////////////////////////////

    $field = $this->modules->get('InputfieldText');
    $field->name = "translate_trigger_text";
    $field->label = __("UI Translate Trigger Text");
    $field->description = __('This is the text used for the translation trigger next to fields when editing content.');
    $field->placeholder = 'Default: Translate from {default language name}';
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
    if (gettype($deeplTargetLanguages) == 'array' && count($deeplTargetLanguages)) {
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
          $langSelectField->label = __('ProcessWire Default Language: ') .
                                    "{$pwLanguageTitle}";
          $langSelectField->description = __('Translations will be made from this language into associated languages below.');
          $langSelectField->notes = __('This language must be listed in the source languages above.');
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

          $sortedTargetLanguages = $deeplTargetLanguages;

          // Sort Target Languages
          usort($sortedTargetLanguages, function($a, $b) {
            return strcmp($a->name, $b->name);
          });

          // Add each DeepL dest language to the select field
          foreach ($sortedTargetLanguages as $lang) {
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
    $moduleBaseUrl = wire('config')->urls->get($this->modules->get('Fluency'));

    $content = '<h3>' . __("Want to brighten someone's day?") . '</h3>';

    $content .= '<p>' . __("Did you or your client find this module useful? Do you have cash just lying around? Did you sneak in a few extra bucks in your client contract to pass along to the module builders you love? Whatever the case, if you want to throw a tip my way, give that button a click! It will probably go towards bourbon.") . '</p>';

    $content .= "<style>.button-donate {border:1px solid #29A2CE;box-shadow: 0 5px 10px rgba(0,0,0,.35);transition: box-shadow .5s,scale .4s;display: block; margin: 20px auto; width: 200px;}.button-donate:hover {box-shadow: 0 10px 20px rgba(0,0,0,.25);scale: 1.005;}</style>";

    $content .= "<a class='button-donate' href='https://paypal.me/noonesboy' rel='noopener' target='_blank'><img src='{$moduleBaseUrl}/img/paypal_me.png' alt='PayPal Me'></a>";

    // Create field for content
    $field = $this->modules->get('InputfieldMarkup');
    $field->label = __('Beg-A-Thon');
    $field->value = $content;
    $inputfields->add($field);

    return $inputfields;
  }
}
