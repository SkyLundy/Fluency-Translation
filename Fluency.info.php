<?php namespace ProcessWire;

// FileCompiler=0

$info = [
  'title' => 'Fluency',
  'version' => '032',
  'href' => 'https://gitlab.com/SkyLundy/fluency-processwire.git',
  'icon' => 'language',
  'summary' => __("Translation service integration module that uses the DeepL machine learning langauge translation API."),
  'autoload' => true,
  'singular' => true,
  'requires' => [
    'ProcessWire>=300'
  ],
  'permission' => 'fluency-translate',
  'permissions' => [
    'fluency-translate' => 'Use Fluency translation'
  ],
  'page' => [
    'name' => 'fluency',
    'title' => 'Translation'
  ]
];
