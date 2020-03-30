<?php

namespace srag\DataTableUI\SrUserEnrolment\Implementation;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Implementation\Render\ResourceRegistry;
use ILIAS\UI\Renderer as RendererInterface;
use srag\DataTableUI\SrUserEnrolment\Component\Data\Data;
use srag\DataTableUI\SrUserEnrolment\Component\Format\Format;
use srag\DataTableUI\SrUserEnrolment\Component\Settings\Settings;
use srag\DataTableUI\SrUserEnrolment\Component\Table;
use srag\DataTableUI\SrUserEnrolment\Implementation\Utils\DataTableUITrait;
use srag\DIC\SrUserEnrolment\DICTrait;

/**
 * Class Renderer
 *
 * @package srag\DataTableUI\SrUserEnrolment\Implementation
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class Renderer extends AbstractComponentRenderer
{

    use DICTrait;
    use DataTableUITrait;


    /**
     * @inheritDoc
     */
    protected function getComponentInterfaceName() : array
    {
        return [Table::class];
    }


    /**
     * @inheritDoc
     *
     * @param Table $component
     */
    public function render(Component $component, RendererInterface $default_renderer) : string
    {
        self::dic()->language()->loadLanguageModule(Table::LANG_MODULE);

        $this->checkComponent($component);

        return $this->renderDataTable($component);
    }


    /**
     * @param Table $component
     *
     * @return string
     */
    protected function renderDataTable(Table $component) : string
    {
        $settings = $component->getSettingsStorage()->read($component->getTableId(), intval(self::dic()->user()->getId()));
        $settings = $component->getBrowserFormat()->handleSettingsInput($component, $settings);
        $settings = $component->getSettingsStorage()->handleDefaultSettings($settings, $component);

        $data = $this->handleFetchData($component, $settings);

        $html = $this->handleFormat($component, $data, $settings);

        $component->getSettingsStorage()->store($settings, $component->getTableId(), intval(self::dic()->user()->getId()));

        return $html;
    }


    /**
     * @inheritDoc
     */
    public function registerResources(ResourceRegistry $registry)/* : void*/
    {
        parent::registerResources($registry);

        $dir = __DIR__;
        $dir = "./" . substr($dir, strpos($dir, "/Customizing/") + 1) . "/../..";

        $registry->register($dir . "/css/datatableui.css");

        $registry->register($dir . "/js/datatableui.min.js");
    }


    /**
     * @inheritDoc
     */
    protected function getTemplatePath(/*string*/ $name) : string
    {
        return __DIR__ . "/../../templates/" . $name;
    }


    /**
     * @param Table    $component
     * @param Settings $settings
     *
     * @return Data|null
     */
    protected function handleFetchData(Table $component, Settings $settings)/* : ?Data*/
    {
        if (!$component->getDataFetcher()->isFetchDataNeedsFilterFirstSet() || $settings->isFilterSet()) {
            $data = $component->getDataFetcher()->fetchData($settings);
        } else {
            $data = null;
        }

        return $data;
    }


    /**
     * @param Table     $component
     * @param Data|null $data
     * @param Settings  $settings
     *
     * @return string
     */
    protected function handleFormat(Table $component, /*?Data*/ $data, Settings $settings) : string
    {
        $input_format_id = $component->getBrowserFormat()->getInputFormatId($component);

        /**
         * @var Format $format
         */
        $format = current(array_filter($component->getFormats(), function (Format $format) use ($input_format_id): bool {
            return ($format->getFormatId() === $input_format_id);
        }));

        if ($format === false) {
            $format = $component->getBrowserFormat();
        }

        $rendered_data = $format->render($component, $data, $settings);

        switch ($format->getOutputType()) {
            case Format::OUTPUT_TYPE_DOWNLOAD:
                $format->deliverDownload($rendered_data, $component);

                return "";

            case Format::OUTPUT_TYPE_PRINT:
            default:
                return $rendered_data;
        }
    }
}
