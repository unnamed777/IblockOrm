<?
namespace nav\IblockOrm;
use Bitrix\Main\Entity;
use Bitrix\Main;

abstract class MultiplePropertyElementTable extends Entity\DataManager
{
    protected static $_iblockId;

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_iblock_element_prop_m' . static::$_iblockId;
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => 'ID',
            ),
            'IBLOCK_ELEMENT_ID' => array(
                'data_type' => 'integer',
                'required' => true,
            ),
            new Entity\ReferenceField(
                'ELEMENT',
                '\Base',
                array('=this.IBLOCK_ELEMENT_ID' => 'ref.ID')
            ),
            'IBLOCK_PROPERTY_ID' => array(
                'data_type' => 'integer',
                'required' => true,
            ),
            'VALUE' => array(
                'data_type' => 'string',
                'required' => true,
            ),
            'VALUE_ENUM' => array(
                'data_type' => 'integer',
                'required' => true,
            ),
            'VALUE_NUM' => array(
                'data_type' => 'float',
                'required' => true,
            ),
            'DESCRIPTION' => array(
                'data_type' => 'string',
            ),
            new Entity\ReferenceField(
                'PROPERTY',
                '\Bitrix\Iblock\Property',
                array('this.IBLOCK_PROPERTY_ID' => 'ref.ID')
            ),
            new Entity\ExpressionField(
                'CODE',
                '%s',
                'PROPERTY.CODE'
            )
        );
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

        $entityName = 'MultiplePropertyIblock' . $iblockId . 'Table';

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
        $classCode = $classCode."class {$entityName} extends \\nav\\IblockOrm\\MultiplePropertyElementTable {";
        $classCodeEnd = '}'.$classCodeEnd;

        $classCode .= 'protected static $_iblockId = ' . $iblockId . ';';
        $classCode .= 'public static function getFilePath(){return __FILE__;}';

        // create entity
        eval($classCode.$classCodeEnd);
        /** @var \Bitrix\Main\Entity\Base $entity */
        $entity = $fullEntityName::getEntity();
        return $entity;
    }

    public static function getClassName()
    {
        return __CLASS__;
    }
}
