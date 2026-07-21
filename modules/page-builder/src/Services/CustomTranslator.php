<?php

namespace HansSchouten\LaravelPageBuilder\Services;

class CustomTranslator extends \PHPageBuilder\Translator
{
    /**
     * Override to inject Vietnamese translation into pagebuilder translations.
     */
    public function customize($translations)
    {
        if (isset($translations['languages'])) {
            $translations['languages']['vi'] = 'Tiếng Việt';
        } else {
            $translations['languages'] = ['vi' => 'Tiếng Việt'];
        }
        return $translations;
    }
}
