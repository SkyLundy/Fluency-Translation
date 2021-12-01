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
    $moduleBaseUrl = wire('config')->urls->get($fluencyModule);
    $deeplSourceLanguages = [];
    $deeplTargetLanguages = [];
    $deeplApiUsage = null;
    $apiKeyIsValid = null;
    $deepL = null;
    $apiKeyCheckMessage = null;
    $apiHttpResponse = null;

    ///////////////////////////////
    // API key check & API calls //
    ///////////////////////////////
    if ($fluencyModule->deepl_api_key) {
      // Instantiate a new DeepL class instance to access the API
      $deepL = new DeepL([
        'apiKey' => $fluencyModule->deepl_api_key,
        'accountType' => $fluencyModule->deepl_account_type
      ]);

      $request = $deepL->getApiUsage();

      // API key is good
      if ($request->httpStatus !== 403) {
        $apiKeyIsValid = true;
        $deeplApiUsage = $request->data;
        // Get all source/target langs. This ensures that Fluency always has
        // all of DeepL's languages available
        $deeplLanguages = $fluencyModule->translatableLanguages();

        $deeplSourceLanguages = $deeplLanguages->source;
        $deeplTargetLanguages = $deeplLanguages->target;
      }

      if ($request->httpStatus === 403) {
        $apiKeyIsValid = false;
        $apiKeyCheckMessage = 'Authorization failed. Please supply a valid API key.';
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
    if (!$fluencyModule->deepl_api_key || !$apiKeyIsValid) {
      return $inputfields;
    }

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
     */

    $itemTpl =<<<EOT
                <tr>
                  <td style="text-align:right;">%{ITEM_NUMBER}</td>
                  <td style="padding: 0 .25rem;text-align:right;">%{ISO_CODE}</td>
                  <td style="padding: 0 .15rem">&middot;</td>
                  <td style="padding: 0 .3rem;">%{NAME}</td>
                </tr>
               EOT;

    ////////////////////////////////
    // Available source languages //
    ////////////////////////////////
    // This creates an <ol> list of languages that can be translated *from*
    // Create markup for field
    $items = '';

    for ($i = 0; $i < count($deeplSourceLanguages); $i++) {
      $lang = $deeplSourceLanguages[$i];

      $items .= strtr($itemTpl, [
        '%{ITEM_NUMBER}' =>  $i + 1 . '.',
        '%{ISO_CODE}' =>  $lang->language,
        '%{NAME}' =>  $lang->name,
      ]);
    }

    $fieldValue = "<table>{$items}</table>";

    // Create field and add markup
    $sourceLangMarkupField = $this->modules->get('InputfieldMarkup');
    $sourceLangMarkupField->label = __('Source Languages');
    $sourceLangMarkupField->notes = __('The language of the content you are translating from must be listed here.');
    $sourceLangMarkupField->value = $fieldValue;
    $sourceLangMarkupField->columnWidth = 50;
    $sourceLangMarkupField->collapsed = Inputfield::collapsedNever;
    $sourceLangMarkupField->themeBorder = 'hide';
    // Added to fieldset below

    ////////////////////////////////
    // Available target languages //
    ////////////////////////////////
    $items = '';

    for ($i=0; $i < count($deeplTargetLanguages); $i++) {
      $lang = $deeplTargetLanguages[$i];

      $items .= strtr($itemTpl, [
        '%{ITEM_NUMBER}' =>  $i + 1 . '.',
        '%{ISO_CODE}' =>  $lang->language,
        '%{NAME}' =>  $lang->name,
      ]);
    }

    $fieldValue = "<table>{$items}</table>";

    // Create field and add markup
    $targetLangMarkupField = $this->modules->get('InputfieldMarkup');
    $targetLangMarkupField->label = __('Destination Languages');
    $targetLangMarkupField->columnWidth = 50;
    $targetLangMarkupField->value = $fieldValue;
    $targetLangMarkupField->collapsed = Inputfield::collapsedNever;
    $targetLangMarkupField->themeBorder = 'hide';
    // Added to fieldset below

    /////////////////////////////////////
    // From/To language group fieldset //
    /////////////////////////////////////
    // Create fieldset
    $fieldset = $this->modules->get('InputfieldFieldset');
    $fieldset->name = 'fieldset_available_langauges';
    $fieldset->label = __('Available DeepL Languages');
    $fieldset->description = __('DeepL recognizes the following languages and may be used for translation. Source Languages are those which your content must be in to be translated. Destination Languages are those that DeepL will translate to.');

    // Add two markup columns with source/dest languages
    $fieldset->append($sourceLangMarkupField);
    $fieldset->append($targetLangMarkupField);
    $inputfields->add($fieldset);

    /////////////////////////
    // Translation Options //
    /////////////////////////
    // Create fieldset
    $fieldset = $this->modules->get('InputfieldFieldset');
    $fieldset->name = 'fieldset_translation_options';
    $fieldset->label = __('Translation Options');
    $fieldset->description = __('Configure translation behavior.');

    // Strings not translated
    $field = $this->modules->get('InputfieldTextarea');
    $field->name = 'non_translated_strings';
    $field->label = __('Global Non-Translated Strings');
    $field->description = __('Add strings here that when present in content should not be translated. This is useful for things like brand names. For example, if the name of a company is Red Rock Mountain Climbing and it should always appear that way, then adding that string here will prevent it from being translated globally.');
    $field->notes = __('Provide multiple strings as comma separated values, values are case insensitive');
    $field->columnWidth = 50;
    $field->collapsed = Inputfield::collapsedNever;
    $field->themeBorder = 'hide';
    $fieldset->add($field);

    // Preserve Formatting Option
    $field = $this->modules->get('InputfieldSelect');
    $field->name = 'api_param_preserve_formatting';
    $field->label = __('Preserve Formatting');
    $field->description = __('This ensures strict respect to formatting and prevents DeepL from adding/removing punctuation or attempting to correct content');
    $field->notes = __('DeepL API parameter: preserve_formatting');
    $field->required = true;
    $field->columnWidth = 50;
    $field->collapsed = Inputfield::collapsedNever;
    $field->themeBorder = 'hide';
    $field->addOption(1, 'Yes (Recommended)');
    $field->addOption(0, 'No (DeepL default)');
    $fieldset->add($field);

    $inputfields->add($fieldset);

    ///////////////////////////
    // Language Associations //
    ///////////////////////////

    // ===== Create fieldset
    $fieldset = $this->modules->get('InputfieldFieldset');
    $fieldset->name = 'fieldset_language_associations';
    $fieldset->label = __('Language Translation Associations');

    $userLanguage = $this->user->language->name;


    // Set up a language association for all languages present in PW
    if (count($deeplTargetLanguages)) {
      // Loop through PW languages
      foreach ($this->languages as $language) {
        // Get information from languages configured in ProcessWire
        $pwLanguageName = $language->getLanguageValue($userLanguage, 'name');
        $pwLanguageTitle = $language->getLanguageValue($userLanguage, 'title');
        $langSelectFieldName =  "pw_language_{$language->id}";


        // Create language select field
        $langSelectField = $this->modules->get('InputfieldSelect');
        $langSelectField->name = $langSelectFieldName;
        $langSelectField->themeBorder = 'hide';
        $langSelectField->collapsed = Inputfield::collapsedNever;

        $isDefaultLanguage = $pwLanguageName === 'default';

        // Create PW default language association
        if ($isDefaultLanguage) {
          $langSelectField->label = __('ProcessWire Default Language: ') . "{$pwLanguageTitle}";
          $langSelectField->description = __('Translations will be made from this language into associated languages below.');
          $langSelectField->notes = __('This language must be listed in the source languages above.');
          $langSelectField->required = true;
          // Add each language option to the select field
          // We store all of the data for each language as the value. Allows for
          // referencing all of the data later when needed
          foreach ($deeplSourceLanguages as $lang) {
            $langSelectField->addOption(json_encode($lang), $lang->name);
          }

          $fieldset->append($langSelectField);
        }

        // Create other PW language associations.
        if (!$isDefaultLanguage) {
          $childFieldset = $this->modules->get('InputfieldFieldset');
          $childFieldset->name = "pw_language_{$language->id}_fieldset";
          $childFieldset->label = __("ProcessWire Language: ") . $pwLanguageTitle;
          $childFieldset->columnWidth = 50;
          $childFieldset->themeBorder = 'hide';
          $childFieldset->collapsed = Inputfield::collapsedNever;

          $langSelectField->skipLabel = Inputfield::skipLabelHeader;
          $langSelectField->description = __("DeepL language to associate with ") . $pwLanguageTitle;

          // Add each language option to the select field
          // We store all of the data for each language as the value. Allows for
          // referencing all of the data later when needed
          foreach ($deeplTargetLanguages as $lang) {
            $langSelectField->addOption(json_encode($lang), $lang->name);
          }

          $childFieldset->append($langSelectField);

          $fieldset->append($childFieldset);
        }
      }

    }

    $inputfields->add($fieldset);

    /////////////////////////////////////////////
    // Module Localization && Self-Translation //
    /////////////////////////////////////////////

    // ===== Create fieldset
    $fieldset = $this->modules->get('InputfieldFieldset');
    $fieldset->addClass('fluency-localization');
    $fieldset->name = 'fieldset_module_localization';
    $fieldset->label = __('Module Localization & Self Translation');

    $content = '<p>' . __('Fluency has the ability to translate itself. This localizes UI strings for translation elements when editing pages, error messages, and the translation tool.') . '<p>';

    $content .= '<p>' . __('When languages have been configured, click Localize Module to have Fluency translate and cache all client UI text. When changes to languages have been made, such as adding or removing languages, click Clear Localization Cache and then Localize Module to re-generate the localized strings. Clearning the localization cache will destroy all cached texts and all UI elements will default to English.') . '<p>';

    // Add processing overlay element
    $content .= '<div class="fluency-activity-overlay active"> ' .
                  '<span class="message processing">' . __('Working...') . '</span>' .
                  '<span class="message success">' . __('Module Localization Complete') . '</span>' .
                  '<span class="message error">' . __('There was an error localizing Fluency') . '</span>' .
                '</div>';

    // Create field for content
    $field = $this->modules->get('InputfieldMarkup');
    $field->collapsed = Inputfield::collapsedNever;
    $field->themeBorder = 'hide';
    $field->value = $content;
    $fieldset->add($field);

    // Localize Module button
    $button = $this->modules->get('InputfieldSubmit');
    $button->addClass('fluency-localization-translate-module-button');
    $button->addClass('fluency-localization-button');
    $button->addClass('js-fluency-translate-module');
    $button->text = 'Localize Module';
    $button->attr('icon','language');
    $fieldset->add($button);

    // Clear Localization Cache button
    $button = $this->modules->get('InputfieldSubmit');
    $button->addClass('fluency-localization-clear-module-cache-button');
    $button->addClass('fluency-localization-button');
    $button->addClass('js-fluency-clear-module-cache');
    $button->text = 'Clear Localization Cache';
    $button->attr('icon','bomb');
    $fieldset->add($button);


    $inputfields->add($fieldset);

    ////////////////
    // Beg-a-Thon //
    ////////////////
    $content = '<h3>' . __("Want to brighten someone's day?") . '</h3>';

    $content .= '<p>' . __("Did you or your client find this module useful? Do you have cash just lying around? Did you sneak in a few extra bucks in your client contract to pass along to the module builders you love? Whatever the case, if you want to throw a tip my way, give that button a click! It will probably go towards bourbon.") . '</p>';

    $content .= "<a class='button-donate' href='https://paypal.me/noonesboy' rel='noopener' target='_blank'><img src='{$moduleBaseUrl}/img/paypal_me.png' alt='PayPal Me'></a>";

    // Create field for content
    $field = $this->modules->get('InputfieldMarkup');
    $fieldset->name = 'fieldset_donation';
    $field->label = __('Beg-A-Thon');
    $field->value = $content;
    $inputfields->add($field);

    return $inputfields;
  }
}
