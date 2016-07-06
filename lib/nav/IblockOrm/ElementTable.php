<?
namespace nav\IblockOrm;
use nav\IblockOrm;
use Bitrix\Main;
use Bitrix\Main\Entity;

class ElementTable extends Entity\DataManager
{
    /** @var  int */
    static protected $_iblockId;

    /** @var  array */
    static protected $_metadata;

    /** @var  array */
    static public $cacheMetadata = true;

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_iblock_element';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        \Bitrix\Main\Loader::includeModule('iblock');

        static::$_metadata = static::getMetadata();

        $map = array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
            ),
            'NAME' => array(
                'data_type' => 'string',
            ),
            'CODE' => array(
                'data_type' => 'string',
            ),
            'PREVIEW_PICTURE' => array(
                'data_type' => 'integer',
            ),
            'DETAIL_PICTURE' => array(
                'data_type' => 'integer',
            ),
            'PREVIEW_TEXT' => array(
                'data_type' => 'string',
            ),
            'DETAIL_TEXT' => array(
                'data_type' => 'string',
            ),
            'IBLOCK_ID' => array(
                'data_type' => 'integer',
            ),
            'IBLOCK' => array(
                'data_type' => 'Iblock',
                'reference' => array('=this.IBLOCK_ID' => 'ref.ID'),
            ),
            'ACTIVE' => array(
                'data_type' => 'boolean',
                'values' => array('N','Y'),
            ),
            'IBLOCK_SECTION_ID' => array(
                'data_type' => 'integer',
            ),
            'SORT' => array(
                'data_type' => 'integer',
            ),
            'XML_ID' => array(
                'data_type' => 'string',
            ),
            'WF_STATUS_ID' => array(
                'data_type' => 'integer',
            ),
            'WF_PARENT_ELEMENT_ID' => array(
                'data_type' => 'integer',
            ),
            new Entity\ExpressionField(
                'DETAIL_PAGE_URL',
                "''"
            ),
            new Entity\ExpressionField(
                'DISTINCT',
                'DISTINCT %s',
                'ID'
            ),
        );


        $singleEntity = SinglePropertyElementTable::createEntity(static::$_iblockId);

        $map[] = new Entity\ReferenceField(
            'PROPERTY',
            $singleEntity->getDataClass(),
            array(
                'ref.IBLOCK_ELEMENT_ID' => 'this.ID'
            ),
            array('join_type' => 'INNER')
        );

        $multiEntity = MultiplePropertyElementTable::createEntity(static::$_iblockId);

        foreach (static::$_metadata['props'] as $arProp) {
            if ($arProp['MULTIPLE'] == 'Y') {
                $map[] = new Entity\ReferenceField(
                    'PROPERTY_' . $arProp['CODE'] . '_ENTITY',
                    $multiEntity->getDataClass(),
                    array(
                        'ref.IBLOCK_ELEMENT_ID' => 'this.ID',
                        'ref.IBLOCK_PROPERTY_ID' => array('?i', $arProp['ID'])
                    ),
                    array('join_type' => 'INNER')
                );

                $map[] = new Entity\ExpressionField(
                    'PROPERTY_' . $arProp['CODE'],
                    '%s',
                    'PROPERTY_' . $arProp['CODE'] . '_ENTITY.VALUE'
                );

                $map[] = new Entity\ExpressionField(
                    'PROPERTY_' . $arProp['CODE'] . '_DESCRIPTION',
                    '%s',
                    'PROPERTY_' . $arProp['CODE'] . '_ENTITY.DESCRIPTION'
                );
            } else {
                $map[] = new Entity\ExpressionField(
                    'PROPERTY_' . $arProp['CODE'],
                    '%s',
                    'PROPERTY.' . $arProp['CODE']
                );

                $map[] = new Entity\ExpressionField(
                    'PROPERTY_' . $arProp['CODE'] . '_DESCRIPTION',
                    '%s',
                    'PROPERTY.' . $arProp['CODE'] . '_DESCRIPTION'
                );
            }
        }

//         p($map,1);
        return $map;
    }

    /**
     * Fetches iblock metadata for further using. Uses cache.
     * Cache can be disabled with flag self::#cacheMetadata = false.
     * @param int|null $iblockId
     * @return array
     * @throws Main\ArgumentException
     * @throws Main\LoaderException
     */
    public static function getMetadata($iblockId = null)
    {
        if (empty($iblockId)) {
            $iblockId = static::$_iblockId;
        }

        \Bitrix\Main\Loader::includeModule('iblock');
        $result = array();
        $obCache = new \CPHPCache;
        $cacheDir = '/' . $iblockId;

        if (static::$cacheMetadata && $obCache->InitCache(3600, 'iblockOrm', $cacheDir)) {
            $result = $obCache->GetVars();
        } else {
            $result['iblock'] = \Bitrix\Iblock\IblockTable::getRowById($iblockId);

            $result['props'] = array();

            $rs = \Bitrix\Iblock\PropertyTable::getList(array('filter' => array(
                'IBLOCK_ID' => $iblockId
            )));

            while ($arProp = $rs->fetch()) {
                $result['props'][$arProp['CODE']] = $arProp;
            }

            if (static::$cacheMetadata) {
                $obCache->StartDataCache();
                $obCache->EndDataCache($result);
            }
        }

        return $result;
    }

    /**
     * Wrapper for DataManager::getList() with page navigation support
     * @param $parameters
     * @return \Bitrix\Main\DB\Result
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function getList($parameters)
    {
        $parameters['filter']['=IBLOCK_ID'] = static::$_iblockId;

        $oldCDBResult = static::_preparePageNav($parameters);
        $rs = parent::getList($parameters);

        if (in_array('DISTINCT', $parameters['select'])) {
            $rs->addReplacedAliases(array('DISTINCT' => 'ID'));
        }

        $rs->addFetchDataModifier(array(__CLASS__, 'fetchDataModifier'));

        /** @var \CDBResult oldCDBResult */
        $rs->oldCDBResult = $oldCDBResult;
        return $rs;
    }

    /**
     * Removes custom parameters for native getList and return them
     */
    /*public static function extractCustomParameters(&$parameters)
    {
        if (isset($parameters['select']) && in_array('DETAIL_PAGE_URL', $parameters['select'])) {
            $pos = array_search('DETAIL_PAGE_URL', $parameters['select']);
            $parameters['select'] = array_splice($parameters['select'], $pos, 1);
        }

        return $parameters;
    }*/

    public static function fetchDataModifier($entry)
    {
        $newEntry = array();

        // @todo Add option to disable tilda
        foreach ($entry as $key => $value) {
            $newEntry['~' . $key] = $value;
            $newEntry[$key] = htmlspecialcharsbx($value);
        }

        if (isset($newEntry['DETAIL_PAGE_URL'])) {
            $newEntry["~DETAIL_PAGE_URL"] = \CIBlock::ReplaceDetailUrl(static::$_metadata['iblock']['DETAIL_PAGE_URL'], $newEntry, true, 'E');
            $newEntry["DETAIL_PAGE_URL"] = htmlspecialcharsbx($newEntry["~DETAIL_PAGE_URL"]);
        }

        return $newEntry;
    }

    /**
     * Creates classic navObject and initializes page navigation
     * @param $parameters
     * @return \CDBResult|void
     * @throws Main\ArgumentException
     */
    protected static function _preparePageNav($parameters)
    {
        // Check offset as indicator of necessity to use page navigation
        if (!(isset($parameters['offset']) && isset($parameters['limit']))) {
            return;
        }

        $parametersCount = $parameters;
        unset($parametersCount['order']);
        unset($parametersCount['limit']);
        unset($parametersCount['offset']);

        if (isset($parametersCount['group'])) {
            $parametersCount['select'] = array('TMP' => new Entity\ExpressionField('TMP', "'x'"));
            $result = parent::getList($parametersCount);
            $rowsCount = $result->getSelectedRowsCount();
        } else {
            $parametersCount['runtime']['COUNT_ROWS'] = new Entity\ExpressionField('COUNT_ROWS', 'COUNT(DISTINCT %s)', array('ID'));
            $parametersCount['select'] = array('COUNT_ROWS');
            $result = parent::getList($parametersCount)->fetch();
            $rowsCount = $result['COUNT_ROWS'];
        }

        // The only way to use default page navigation is using of CDBResult
        $cdbresult = new \CDBResult();

        // Restore classic navParams
        $arNavStartParams = array(
            'iNumPage' => (int) round($parameters['offset'] / $parameters['limit']) + 1,
            'nPageSize' => $parameters['limit']
        );

        $cdbresult->InitNavStartVars($arNavStartParams);
        // So dirty, so pity
        $cdbresult->NavPageNomer = $arNavStartParams['iNumPage'];
        $cdbresult->NavPageCount = (int) ceil($rowsCount / $arNavStartParams['nPageSize']);
        $cdbresult->nSelectedCount = (int) $rowsCount;

        return $cdbresult;
    }

    /**
     * Returns filled Query object with getList-compatible parameters.
     * Almost copy of DataManager::GetList().
     * @param $parameters Array
     * @return Entity\Query
     * @throws Main\ArgumentException
     */
    public static function getQuery($parameters)
    {
        $query = static::query();

        $parameters['filter']['=IBLOCK_ID'] = static::$_iblockId;

        if (!isset($parameters['select'])) {
            $query->setSelect(array('*'));
        }

        foreach ($parameters as $param => $value) {
            switch ($param) {
                case 'select':
                    $query->setSelect($value);
                    break;
                case 'filter':
                    $query->setFilter($value);
                    break;
                case 'group':
                    $query->setGroup($value);
                    break;
                case 'order';
                    $query->setOrder($value);
                    break;
                case 'limit':
                    $query->setLimit($value);
                    break;
                case 'offset':
                    $query->setOffset($value);
                    break;
                case 'count_total':
                    $query->countTotal($value);
                    break;
                case 'runtime':
                    foreach ($value as $name => $fieldInfo) {
                        $query->registerRuntimeField($name, $fieldInfo);
                    }
                    break;
                case 'data_doubling':
                    if($value) {
                        $query->enableDataDoubling();
                    } else {
                        $query->disableDataDoubling();
                    }
                    break;
                default:
                    throw new Main\ArgumentException("Unknown parameter: " . $param, $param);
            }
        }

        return $query;
    }

    /**
     * Create dynamically class for requested iblock
     * @param int $iblockId
     * @param array $parameters
     * @return mixed
     * @throws Main\ArgumentException
     */
    public static function createEntity($iblockId, $parameters = array())
    {
        $classCode = '';
        $classCodeEnd = '';

        if (!is_int($iblockId) || $iblockId <= 0) {
            throw new Main\ArgumentException('$iblockId should be integer');
        }

        $entityName = 'Iblock' . $iblockId . 'Table';

        // validation
        if (!preg_match('/^[a-z0-9_]+$/i', $entityName))
        {
            throw new Main\ArgumentException(sprintf(
                'Invalid entity classname `%s`.', $entityName
            ));
        }

        $fullEntityName = $entityName;

        // namespace configuration
        if (!empty($parameters['namespace']) && $parameters['namespace'] !== '\\') {
            $namespace = $parameters['namespace'];

            if (!preg_match('/^[a-z0-9\\\\]+$/i', $namespace)) {
                throw new Main\ArgumentException(sprintf(
                    'Invalid namespace name `%s`', $namespace
                ));
            }

            $classCode = $classCode . "namespace {$namespace} {";
            $classCodeEnd = '}' . $classCodeEnd;

            $fullEntityName = '\\'.$namespace.'\\'.$fullEntityName;
        }

        // build entity code
        $classCode = $classCode."class {$entityName} extends \\nav\\IblockOrm\\ElementTable {";
        $classCodeEnd = '}'.$classCodeEnd;

        $classCode .= 'protected static $_iblockId = ' . $iblockId . ';';

        $classCode .= 'public static function getFilePath(){return __FILE__;}';

        // create entity
        eval($classCode.$classCodeEnd);

        /** @var \Bitrix\Main\Entity\Base $entity */
        $entity = $fullEntityName::getEntity();

        return $entity;
    }

	public static function add(array $data)
    {
        throw new \Exception('Method not supported');
    }

    public static function update($primary, array $data)
    {
        throw new \Exception('Method not supported');
    }

    public static function delete($primary)
    {
        throw new \Exception('Method not supported');
    }

    /**
     * Helper for fetching iblock elements with all properties
     * @param $rs
     * @return array
     */
    public static function fetchAllWithProperties($rs)
    {
        $arItems = array();

        while ($arItem = $rs->fetch()) {
            $arItem['PROPERTIES'] = array();
            $arItems[] = $arItem;
            $arElementsLink[$arItem['ID']] = &$arItems[count($arItems) - 1];
        }

        \CIBlockElement::GetPropertyValuesArray($arElementsLink, static::$_iblockId, array(
            'ID' => array_keys($arElementsLink),
            'IBLOCK_ID' => static::$_iblockId,
        ));

        return $arItems;
    }
}
