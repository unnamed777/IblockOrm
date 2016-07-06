<?
namespace nav\IblockOrm;
use Bitrix\Main\Entity;
use Bitrix\Main;

abstract class SinglePropertyElementTable extends Entity\DataManager
{
    protected static $_iblockId;

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_iblock_element_prop_s' . static::$_iblockId;
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        \Bitrix\Main\Loader::includeModule('iblock');
        $metadata = ElementTable::getMetadata(static::$_iblockId);

        $map = array(
            'IBLOCK_ELEMENT_ID' => array(
                'data_type' => 'integer',
                'primary' => true,
            ),
        );

        foreach ($metadata['props'] as $arProp) {
            if ($arProp['MULTIPLE'] == 'Y') {
                continue;
            }

            switch ($arProp['PROPERTY_TYPE']) {
                case 'N':
                    $map[] = new Entity\FloatField($arProp['CODE'], array(
                        'column_name' => 'PROPERTY_' . $arProp['ID'],
                    ));
                    $map[] = new Entity\StringField($arProp['CODE'] . '_DESCRIPTION', array(
                        'column_name' => 'DESCRIPTION_' . $arProp['ID'],
                    ));
                    break;

                case 'L':
                case 'E':
                case 'G':
                    $map[] = new Entity\IntegerField($arProp['CODE'], array(
                        'column_name' => 'PROPERTY_' . $arProp['ID'],
                    ));
                    $map[] = new Entity\StringField($arProp['CODE'] . '_DESCRIPTION', array(
                        'column_name' => 'DESCRIPTION_' . $arProp['ID'],
                    ));
                    break;

                case 'S':
                default:
                    $map[] = new Entity\StringField($arProp['CODE'], array(
                        'column_name' => 'PROPERTY_' . $arProp['ID'],
                    ));
                    $map[] = new Entity\StringField($arProp['CODE'] . '_DESCRIPTION', array(
                        'column_name' => 'DESCRIPTION_' . $arProp['ID'],
                    ));
                    break;
            }
        }

        return $map;
    }

    /**
     * @param $iblockId
     * @param array $parameters
     * @return \Bitrix\Main\Entity\Base
     * @throws Main\ArgumentException
     */
    public static function createEntity($iblockId, $parameters = array())
    {
        $classCode = '';
        $classCodeEnd = '';

        if (!is_int($iblockId) || $iblockId <= 0) {
            throw new Main\ArgumentException('$iblockId should be integer');
        }

        $entityName = 'SinglePropertyIblock' . $iblockId . 'Table';

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
        $classCode = $classCode."class {$entityName} extends \\nav\\IblockOrm\\SinglePropertyElementTable {";
        $classCodeEnd = '}'.$classCodeEnd;

        $classCode .= 'protected static $_iblockId = ' . $iblockId . ';';
        $classCode .= 'public static function getFilePath(){return __FILE__;}';

        // create entity
        eval($classCode.$classCodeEnd);
        /** @var \Bitrix\Main\Entity\Base $entity */
        $entity = $fullEntityName::getEntity();
        return $entity;
    }
}
