# Fluency - Integrated DeepL Translation for ProcessWire
Fluency is a module that brings DeepL's powerful AI deep learning translation engine to the ProcessWire CMF/CMS. With minimal configuration and effort, ProcessWire's native multi-language fields are enhanced with a button to translate the content entered under the default language to the language under the field's currently selected language.

Fluency can be added to new websites in development, existing websites adding multi-language abilities, or to websites that already have multi-language capabilities where it is desireable to have high quality translation built in.

**Please note, this is an alpha release.** Please use in production after thorough testing for your own project and create Github issues for bugs found if possible.

## What is DeepL?
DeepL is an advanced language translation engine that provides industry-leading results when compared to other automated solutions. It handily beats the quality and accuracy of offerings from companies like Facebook, Google, and Microsoft. DeepL has been praised for it's ability to reproduce language translations that read like native speech.

In addition to its quality and power, DeepL is able to parse XML/HTML tags and translate them while retaining the markup structure. This allows for incredible opportunities in integrating DeepL with web-based applications as Fluency has done with ProcessWire.

## Fluency Features
Fluency brings many features to ProcessWire and provides an experience that feels native to the CMS and complements the powerful multi-language capabilities already built into it's core. Any field that can be a multi-language field can be translated by Fluency. These include:

- Translate any plain textarea or text input
- Translate any CKEditor content (yes, with markup)
- Translate image descriptions
- Translate page names for fully localized URLs
- Translate your in-template translation function wrapped strings
- Translate 3rd party modules
- Even translate the ProcessWire core files themselves if one were so inclined (although there are high quality language packs that would probably save you time)

Best of all, you don't have to modify the way you build out your ProcessWire site. Use any field in any configuration. Fluency scans the page for multi-language fields and adds the ability to translate. Fields like Repeater and Repeater Matrix which load or add additional items using AJAX are compatible thanks to scripts that watch for new fields and add Fluency functionality as they appear on page.

## Cost
Fluency is free to use. There is no cost for this module and it can be used on any site that you build with ProcessWire.

## DeepL Account
To use Fluency you must have a Developer account with DeepL. The Developer account provides access to their REST API which is used to power Fluency. DeepL has two account types which are Free and Pro, both of which can be used with Fluency.

To learn more about DeepL Developer accounts and sign up, [click here to find out more](https://www.deepl.com/pro#developer).

## Instructions
1. Download and unzip the contents into /site/modules
2. Install the module in the developer admin
3. Open the module configuration page, select your account type, enter your DeepL API key, then save
4. Set Preserve Formatting choice
5. Scroll down to the list of the languages that are configured in your ProcessWire installation and select the DeepL language you would like to associate with them, then save

That's it! All multi-language fields should now have click to translate buttons and a translator tool is available in the Admin menu bar.

If no langauges are present in ProcessWire or if languages are present and not configured with Fluency, it is still possible to use the translator tool in the Admin menu as long as a valid API key is present and the current user is assigned a role with the `fluency-translate` permission.

### Customizing/Translating UI Text
All text for the Fluency UI elements can be customized. This is done through ProcessWire's language setup. In the Admin visit Setup->Languages, edit the 'default' language. Then click "Find Files To Translate".

All UI related translation files will be located in the `module_localizations` folder in the Fluency plugin directory.

By default the "Translate From" trigger for each field uses the title of the default language as it is configured in ProcessWire.

## Using Fluency & DeepL Programatically
The Fluency module is a ProcessWire interface for bringing DeepL translation to the admin and content editing screens. There are two ways to access translation in your scsripts and templates. The `translate()` method for both is identical and is as follows:

```php
$fluency->translate(
  string $sourceLanguageCode, // Language translating from in ISO 639-1 format
  string|array $contentToTranslate, // String or array of strings to translate
  string $targetLanguageCode, // Language translating to in ISO 639-1 format
  ?array $addApiParams, // Additional DeepL API parameters (Refer to DeepL API docs)
  ?array $ignoredStrings // Substrings within content that should not be translated
);
```

### Using the Fluency module
This requires that your current user has the `fluency-translate` permission. This will use the API key from the ProcessWire configuration screen as well as the global ignored strings, preserve formatting, etc. It does not reference configured languages as those are defined manually when translating so all are available when using the module directly.

#### Simple Example
Translate one string to another language.
```php
$fluency = $modules->get("Fluency");

// Simple example, for more complex requests, including additional API parameters
// see the DeepL class call below. Any additional configurations will be merged
// with the module's configuration.
$result = $fluency->translate("EN", "Hello!", "ES");

echo $result->data->translations[0]->text;
```

#### Full Example
Here is a request that makes use of all of the Fluency translate method parameters
and translates multiple separate strings at once:
```php
$fluency = $modules->get("Fluency");

$result = $fluency->translate(
  "EN",
  [
    "Hello my friend!",
    "Goodbye, but not forever!",
    "Translate me, but not me!",
    "Don't translate me. Translate me instead!"
  ],
  "ES",
  [
    "preserve_formatting" => 0
  ],
  [
    "but not me!",
    "Don't translate me."
  ]
);

foreach ($result->data->translations as $translation) {
  echo "{$translation->text}<br>";
}
```

### Additional Methods
Fluency includes tools to make working with translation and building a multi-language site easier, faster, and more standards/SEO compliant. The following methods are available:

- `Fluency::apiUsage()` - This returns the current API usage
- `Fluency::languageList()` - This gets all languages DeepL translates from and to.
- `Fluency::currentLanguageIsoCode()` - This returns the current language ISO code as a string
- `Fluency::altLanguageMetaTags()` - This returns a string of alternate language HTML meta tags. The ISO code is provided by DeepL, the URLs are as configured for each page in ProcessWire. See example below.

Examples:
Adding this to your document head markup:
```php
<?php namespace ProcessWire;
$fluency = $modules->get('Fluency');
?>
<!DOCTYPE html>
<html lang="<?php echo $fluency->currentLanguageIsoCode(); ?>">
  <head>
    <title><php echo $page->title; ?></title>
    <?php echo $fluency->altLanguageMetaTags(); ?>
    <!-- continuing code ommitted... -->
```

Will output this (when the current language is German):
```HTML
<!DOCTYPE html>
<html lang="DE">
  <head>
    <title>Über meine mehrsprachige Website</title>
    <meta rel="alternate" hreflang="EN" href="https://fluency.com/about-my-website">
    <meta rel="alternate" hreflang="DE" href="https://fluency.com/de/uber-meine-website">
    <meta rel="alternate" hreflang="FR" href="https://fluency.com/fr/a-propos-de-mon-site-web">
    <meta rel="alternate" hreflang="ES" href="https://fluency.com/es/sobre-mi-sitio-web">
    <meta rel="alternate" hreflang="IT" href="https://fluency.com/it/sul-mio-sito-web">
    <!-- continuing code ommitted... -->
```

## Extending Fluency Functionality
A companion module has been made by a member of the ProcessWire community to translate whole pages at once. Read more and download here https://github.com/robertweiss/ProcessTranslatePage

## Limitations:
- The browser plugin for Grammarly conflicts with Fluency. The immediate solution is to either disable Grammarly while using Fluency in the ProcessWire admin, or log into the admin in a private browser window where Grammarly may not be running.
- No "translate whole site" - Same thing goes for translating an entire website at once. It would be great, but it would be a very intense process and take a very long time. There may be a way to create some sort of dedicated site translation page that leverages a progress bar or chunks the work, but again the DeepL request to response time would likely create some issues and there are limitations to how many concurrent requests can be made, a number which isn't documented by DeepL. This may change in the future but it's not on the roadmap as of right now.
- Inline CKEditor not supported - This is on the roadmap but unfortunately for now you have to use regular CKEditor configuration.
- Alpha release - This module is an alpha release. All of my testing during development hasn't turned up any errors or problems, but those don't come out until more wide usage. I will be using this on a website I am building that will be launched in the next couple of weeks so it's going to get real-world usage pretty quickly.
