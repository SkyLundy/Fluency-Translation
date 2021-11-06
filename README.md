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

## Using Fluency & DeepL Programatically
The Fluency module is a ProcessWire interface for bringing DeepL translation to the admin and content editing screens. There are two ways to access translation in your scsripts and templates. The `translate()` method for both is identical and is as follows:

```php
$fluency->translate(
  string $sourceLanguageCode,
  string|array $contentToTranslate,
  string $targetLanguageCode,
  array $additionalParameters,
  array $ignoredStrings
);
```

### Using the Fluency module
This requires that your current user has the `fluency-translate` permission. This will use the API key from the ProcessWire configuration screen as well as the global ignored strings, preserve formatting, etc. It does not reference configured languages as those are defined manually when translating so all are available when using the module directly.

```php
$fluency = $modules->get('Fluency');

// Simple example, for more complex requests, including additional API parameters
// see the DeepL class call below. Any additional configurations will be merged
// with the module's configuration.
$result = $fluency->translate('EN', 'Hello!', 'ES');

echo $result->data->translations[0]->text;
```

### Calling the DeepL class directly
This does not use any configurations in the ProcessWire configuration screen and requires that you provide your DeepL API key as well as any API parameters. Since this bypasses ProcessWire altogether the Fluency user permissions do not apply.

```php
// Namespaced under ProcessWire
use DeepL;

$deepl = new DeepL([
  'apiKey' => 'your-deepl-api-key-here'
  'accountType' => 'pro' // Options are 'pro' and 'free'
]);

// Here is an extended example
$result = $deepl->translate(
  'EN',
  [
    'Hello my friend!',
    'Goodbye, but not forever!!'
  ],
  'ES',
  [
    'preserve_formatting' => 1
  ],
  [
    'friend',
    'forever'
  ]
);

foreach ($result->data->translations as $translation) {
  echo "{$translation}<br>";
}
```

Additional methods are available when calling either Fluency or DeepL directly.

`getApiUsage()` - Gets the current API usage
`getLanguageList()` - Gets all langauges DeepL translates from and to

## Limitations:
- The browser plugin for Grammarly conflicts with Fluency. The immediate solution is to either disable Grammarly while using Fluency in the ProcessWire admin, or log into the admin in a private browser window where Grammarly may not be running.
- No "translate page" - Translating multiple fields can be done by clicking multiple translation links on multiple fields at once but engineering a "one click page translate" is not feasible from a user experience standpoint. The time it takes to translate one field can be a second or two, but cumulatively that may take much longer (CKEditor fields are slower than plain text fields). This may have a workaround in the future but I wanted to eliminate the possibility that translating a whole page takes so long that the user things that it hanged and gets frustrated. Right now one click per language per field makes it easy, predictable, and prevents user frustration.
- No "translate whole site" - Same thing goes for translating an entire website at once. It would be great, but it would be a very intense process and take a very long time. There may be a way to create some sort of dedicated site translation page that leverages a progress bar or chunks the work, but again the DeepL request to response time would likely create some issues and there are limitations to how many concurrent requests can be made, a number which isn't documented by DeepL. This may change in the future but it's not on the roadmap as of right now.
- Inline CKEditor not supported - This is on the roadmap but unfortunately for now you have to use regular CKEditor configuration.
- Alpha release - This module is an alpha release. All of my testing during development hasn't turned up any errors or problems, but those don't come out until more wide usage. I will be using this on a website I am building that will be launched in the next couple of weeks so it's going to get real-world usage pretty quickly.
