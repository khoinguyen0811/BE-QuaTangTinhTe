<?php

namespace HansSchouten\LaravelPageBuilder\Services;

use PHPageBuilder\Modules\GrapesJS\PageRenderer;

class CustomPageRenderer extends PageRenderer
{
    /**
     * Return the array of all blocks rendered while parsing shortcodes.
     * Override to ensure empty languages are represented as empty objects instead of null,
     * preventing client-side TypeError on undefined keys.
     */
    public function getPageBlocksData()
    {
        $initialLanguage = $this->language;

        // remove the already rendered blocks
        $this->shortcodeParser->resetRenderedBlocks();

        // create the structure of page blocks data for each language
        $pageBlocks = [];
        foreach (phpb_active_languages() as $languageCode => $languageTranslation) {
            $this->setLanguage($languageCode);

            // for the current language build up a structure of rendered versions and use the stored data for the other languages
            if ($languageCode === $initialLanguage) {
                $this->renderBody();
                $pageBlocks[$languageCode] = $this->shortcodeParser->getRenderedBlocks()[$languageCode] ?? [];
            } else {
                $pageBlocks[$languageCode] = $this->pageBlocksData;
            }

            if (empty($pageBlocks[$languageCode])) {
                $pageBlocks[$languageCode] = new \stdClass;
            }
        }

        // revert to initial language
        $this->setLanguage($initialLanguage);

        return $pageBlocks;
    }
}
