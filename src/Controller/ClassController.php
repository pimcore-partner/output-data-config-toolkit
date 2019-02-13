<?php
/**
 * Created by PhpStorm.
 * User: jraab
 * Date: 12.02.2019
 * Time: 16:49
 */

namespace OutputDataConfigToolkitBundle\Controller;


use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\Column;
use OutputDataConfigToolkitBundle\Constant\ColumnConfigDisplayMode;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Classificationstore;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ClassController
 * @package OutputDataConfigToolkitBundle\Controller
 *
 */
class ClassController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController
{
    /* @var string $columnConfigClassificationDisplayMode */
    protected $columnConfigClassificationDisplayMode;

    /**
     * @Route("/get-class-definition-for-column-config", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function getClassDefinitionForColumnConfigAction(Request $request)
    {
        $classId = $request->get('id');
        $class = DataObject\ClassDefinition::getById($classId);
        $objectId = intval($request->get('oid'));

        $filteredDefinitions = DataObject\Service::getCustomLayoutDefinitionForGridColumnConfig($class, $objectId);

        $layoutDefinitions = isset($filteredDefinitions['layoutDefinition']) ? $filteredDefinitions['layoutDefinition'] : false;
        $filteredFieldDefinition = isset($filteredDefinitions['fieldDefinition']) ? $filteredDefinitions['fieldDefinition'] : false;

        $fieldDefinitions = $class->getFieldDefinitions();
        $class->setFieldDefinitions(null);

        $result = [];

        $result['objectColumns']['childs'] = $layoutDefinitions->getChilds();
        $result['objectColumns']['nodeLabel'] = 'object_columns';
        $result['objectColumns']['nodeType'] = 'object';

        // array("id", "fullpath", "published", "creationDate", "modificationDate", "filename", "classname");
        $systemColumnNames = DataObject\Concrete::$systemColumnNames;
        $systemColumns = [];
        foreach ($systemColumnNames as $systemColumn) {
            $systemColumns[] = ['title' => $systemColumn, 'name' => $systemColumn, 'datatype' => 'data', 'fieldtype' => 'system'];
        }
        $result['systemColumns']['nodeLabel'] = 'system_columns';
        $result['systemColumns']['nodeType'] = 'system';
        $result['systemColumns']['childs'] = $systemColumns;

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();

        foreach ($list as $brickDefinition) {
            $classDefs = $brickDefinition->getClassDefinitions();
            if (!empty($classDefs)) {
                foreach ($classDefs as $classDef) {
                    if ($classDef['classname'] == $class->getName()) {
                        $fieldName = $classDef['fieldname'];
                        if ($filteredFieldDefinition && !$filteredFieldDefinition[$fieldName]) {
                            continue;
                        }

                        $key = $brickDefinition->getKey();

                        $result[$key]['nodeLabel'] = $key;
                        $result[$key]['brickField'] = $fieldName;
                        $result[$key]['nodeType'] = 'objectbricks';
                        $result[$key]['childs'] = $brickDefinition->getLayoutdefinitions()->getChilds();
                        break;
                    }
                }
            }
        }

        $this->considerClassificationStoreForColumnConfig($request, $class, $fieldDefinitions, $result);

        return $this->adminJson($result);
    }

    /**
     * @param Request $request
     * @param DataObject\ClassDefinition|null $class
     * @param array $fieldDefinitions
     * @param array $result
     */
    private function considerClassificationStoreForColumnConfig(Request $request, ?DataObject\ClassDefinition $class, array $fieldDefinitions, array &$result): void
    {
        $displayMode = $this->getColumnConfigClassificationDisplayMode();

        if ($displayMode == ColumnConfigDisplayMode::NONE) {
            return;
        }

        if ($displayMode == ColumnConfigDisplayMode::OBJECT || $displayMode == ColumnConfigDisplayMode::RELEVANT) {
            $targetObjectId = $request->get('target_oid');

            if (($targetObject = DataObject\Concrete::getById($targetObjectId)) && !$targetObject instanceof DataObject\Folder) {
                $class->setFieldDefinitions($fieldDefinitions);
                DataObject\Service::enrichLayoutDefinition($result['objectColumns']['childs'][0], $targetObject);
                try {
                    // todo: is there a better way to check if a classification group exists for class?
                    $enrichment = Db::get()->fetchOne("SELECT EXISTS (SELECT * FROM object_classificationstore_groups_{$class->getId()} WHERE o_id = '{$targetObjectId}')");
                } catch (TableNotFoundException $exception) {
                    $enrichment = false;
                }
            }
        }

        if ($displayMode == ColumnConfigDisplayMode::ALL || ($displayMode == ColumnConfigDisplayMode::RELEVANT && !$enrichment)) {
            $keyConfigDefinitions = [];
            $keyConfigs = new Classificationstore\KeyConfig\Listing();
            $keyConfigs = $keyConfigs->load();

            foreach ($keyConfigs as $keyConfig) {
                $definition = Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
                $definition->setTooltip($definition->getName() . ' - ' . $keyConfig->getDescription());
                $keyConfigDefinitions[] = $definition;
            }

            $result["classificationColumns"] = [
                "nodeType" => "classificationstore",
                "nodeLabel" => "classificationstore",
                "childs" => $keyConfigDefinitions,
            ];
        }
    }

    /**
     * @param string $columnConfigClassificationDisplayMode
     */
    public function setColumnConfigClassificationDisplayMode(string $columnConfigClassificationDisplayMode)
    {
        $this->columnConfigClassificationDisplayMode = $columnConfigClassificationDisplayMode;
    }

    /**
     * @return string
     */
    public function getColumnConfigClassificationDisplayMode(): string
    {
        return $this->columnConfigClassificationDisplayMode;
    }

}