<?php
use \Bitrix\Main;
use \Bitrix\Main\Text\String as String;
use \Bitrix\Main\Localization\Loc as Loc;
use \Bitrix\Main\SystemException as SystemException;

class CElementList extends \CBitrixComponent
{
    protected $items = array();
    protected $filter = array();
    /** @var \Bitrix\Main\DB\Result */
    protected $rs;
    protected $arElementsLink = array();
    protected $sortOrder;
    protected $sortCode;
    protected $countOnPage = 5;

    /**
     * Prepare component params
     */
    public function onPrepareComponentParams($params)
    {
        $defaultParams = array(
            'FILTER_NAME' => 'arrFilter',
            'SORT_FIELD' => 'ID',
            'SORT_ORDER' => 'DESC',
            'COUNT_ON_PAGE' => 5,
        );

        if (empty($params['FILTER_NAME']) || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $params["FILTER_NAME"])) {
            $params['FILTER_NAME'] = $defaultParams['FILTER_NAME'];
        }

        foreach ($defaultParams as $paramName => $paramValue) {
            if (!empty($params[$paramName])) {
                continue;
            }

            $params[$paramName] = $paramValue;
        }

        return $params;
    }

    public function executeComponent()
    {
        global $APPLICATION;

        try {
            $this->checkModules();
            $this->processRequest();

            if (!$this->extractDataFromCache()) {
                $this->prepareData();
                $this->formatResult();
                //$this->setResultCacheKeys(array());

                if ($this->isAjax()) {
                    ob_start();
                }

                $this->includeComponentTemplate();

                if ($this->isAjax()) {
                    $contents = ob_get_flush();
                    $APPLICATION->RestartBuffer();

                    print json_encode(array_merge(array(
                        'content' => $contents
                    ), (array) $this->arResult['JSON_RESPONSE']));

                    exit;
                }

                $this->putDataToCache();
            }
        } catch (SystemException $e) {
            $this->abortDataCache();

            if ($this->isAjax()) {
                $APPLICATION->restartBuffer();
                print json_encode(array(
                    'status' => 'error',
                    'error' => $e->getMessage())
                );
                die();
            }

            ShowError($e->getMessage());
        }
    }

    /**
     * Check required modules
     */
    protected function checkModules()
    {
    }

    /**
     * Extract data from cache. No action by default.
     * @return bool
     */
    protected function extractDataFromCache()
    {
        return false;
    }

    protected function putDataToCache()
    {
    }

    protected function abortDataCache()
    {
    }

    protected function isAjax()
    {
        $context = Main\Context::getCurrent();
        return ($context->getRequest()->getQuery('ajax') == 'Y' && $context->getRequest()->getQuery('method') == 'load');
    }

    /**
     * Process incoming request.
     * @return void
     */
    protected function processRequest()
    {
        global $APPLICATION;

        $this->request = $_REQUEST;

        \CPageOption::SetOptionString("main", "nav_page_in_session", "N");

        /*if (isset($this->request['page'])) {
            $pageNavVar = 'PAGEN_' . ($GLOBALS['NavNum'] + 1);
            global $$pageNavVar;
            $$pageNavVar = $this->request['page'];
        }*/
    }


    /**
     * @return void
     */
    protected function prepareFilter()
    {
        global ${$this->arParams['FILTER_NAME']};
        $this->filter = array();

        if (is_array(${$this->arParams['FILTER_NAME']})) {
         $this->filter = array_merge(${$this->arParams['FILTER_NAME']}, $this->filter);
        }
    }

    /**
     * Get data.
     * @return array
     */
    protected function getItems()
    {
        $arNavParams = array(
            "nPageSize" => $this->arParams['COUNT_ON_PAGE']
        );

        $arNavigation = \CDBResult::GetNavParams($arNavParams);
        $entity = \nav\IblockOrm\ElementTable::createEntity((int) $this->arParams['IBLOCK_ID']);
        $entityClass = $entity->getDataClass();

        $this->rs = $entityClass::getList(array(
            'select' => array('*', 'DETAIL_PAGE_URL'),
            'order' => array($this->arParams['SORT_FIELD'] => $this->arParams['SORT_ORDER']),
            'filter' => $this->filter,
            'limit' => $arNavigation['SIZEN'],
            'offset' => $arNavigation['SIZEN'] * ($arNavigation['PAGEN'] - 1),
        ));

        $this->items = $entityClass::fetchAllWithProperties($this->rs);

        return $this->items;
    }

    protected function prepareData()
    {
        $this->prepareFilter();
        $this->getItems();
    }

    /**
     * Prepare data to render.
     * @return void
     */
    protected function formatResult()
    {
        $this->arResult['ITEMS'] = $this->items;

        if (is_object($this->rs->oldCDBResult)) {
            $navComponentObject = null;
            $this->arResult['NAV_STRING'] = $this->rs->oldCDBResult->GetPageNavStringEx($navComponentObject, null, '.default', 'N');
            $this->arResult['NAV_CACHED_DATA'] = $navComponentObject->GetTemplateCachedData();
            $this->arResult['NAV_RESULT'] = $this->rs->oldCDBResult;
        }
    }
}
