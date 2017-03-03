<?php
namespace mopon\apidoc\components;

use Yii;
use yii\base\Application;
use yii\helpers\Inflector;
use yii\helpers\Url;

/* 
 * 返回控制器下所有接口
 */

class ApiHandler {
    
    public static function MenuHelper($command=null){
        $menu = [];
        
        $Api = new self;
        $cas = $Api->getDefaultHelp();
        if($cas){
            foreach ($cas as $ca){
                $item = [
                    'label' => $ca['desc'],
                    'url' => Url::to([$command,'command'=>$ca['rule']])
                ];
                foreach ($ca['action'] as $action){
                    $item['items'][] = [
                        'label' => $action['desc'],
                        'url' => Url::to([$command,'command'=>$action['rule']])
                    ];
                }
                $menu[] = $item;
            }
        }
        
        return $menu;
    }
    
    public static function CommandHelp($command){
        $Api = new self;
        
        $result = Yii::$app->createController($command);
        if($result){
            list($controller, $actionID) = $result;
            return $Api->getSubCommandHelp($controller, $actionID);
        }
        
        return false;
    } 

    /**
     * Displays all available commands.
     */
    public function getDefaultHelp()
    {
        $commands = $this->getCommandDescriptions();
        if (!empty($commands)) {
            $ca = [];
            
            foreach ($commands as $command => $description) {
                $_c = [
                    'rule' => $command,
                    'desc' => $description
                ];
                
                $result = Yii::$app->createController($command);
                if ($result !== false) {
                    list($controller, $actionID) = $result;
                    $actions = $this->getActions($controller);
                    if (!empty($actions)) {
                        $prefix = $controller->getUniqueId();
                        foreach ($actions as $action) {
                            if(preg_match('/^test\w+$/', $action)){
                                continue;
                            }
                            
                            $_action = $controller->createAction($action);
                            if(!$_action){
                                throw  new Exception("Error $_action");
                            }
                            
                            $_c['action'][] = [
                                'rule' => "{$prefix}/{$action}",
                                'desc' => $controller->getActionHelpSummary($_action)
                            ];
                            
                        }
                    }
                }
                
                $ca[] = $_c;
                
            }
        }
        
        return $ca;
    }


    /**
     * Displays the overall information of the command.
     * @param Controller $controller the controller instance
     */
    protected function getCommandHelp($controller)
    {
        $ca = [];
        
        $ca['desc'] = $controller->getHelp();

        $actions = $this->getActions($controller);
        if (!empty($actions)) {
            $prefix = $controller->getUniqueId();

            foreach ($actions as $action) {
                $_a = [];
                $_a['rule'] = "{$prefix}/{$action}";
                
                $len = strlen($prefix.'/'.$action) + 2;
                if ($action === $controller->defaultAction) {
                    $len += 10;
                }
                $_a['desc'] = $controller->getActionHelpSummary($controller->createAction($action));
                
                $ca['action'][] = $_a;
                
            }
        }
        
        return $ca;
        
    }

    /**
     * Displays the detailed information of a command action.
     * @param Controller $controller the controller instance
     * @param string $actionID action ID
     * @throws Exception if the action does not exist
     */
    protected function getSubCommandHelp($controller, $actionID)
    {
        $ca = [];
        
        $action = $controller->createAction($actionID);
        if ($action === null) {
            $name = rtrim($controller->getUniqueId() . '/' . $actionID, '/');
            throw new Exception("No help for unknown sub-command \"$name\".");
        }

        $actionDesc = explode("\n", $controller->getActionHelp($action));
        
        $help = $controller->getHelp();
        $actionHelp = empty($actionDesc[0])?$actionID: array_shift($actionDesc);
        if(isset($actionDesc[0]) && preg_match('/^testApi\:(.+)$/', $actionDesc[0], $descArr)){
            $ca['testApi'] = $descArr[1];
            unset($actionDesc[0]);
        }
        
        $ca['name'] = "{$help}-{$actionHelp}";
        
        $ca['desc'] = implode('', $actionDesc);

        $ca['rule'] = $action->getUniqueId();
        
        $ca['options'] = $controller->getActionArgsHelp($action);

        return $ca;
    }


    /**
     * Returns all available command names.
     * @return array all available command names
     */
    public function getCommands()
    {
        $commands = $this->getModuleCommands(Yii::$app);
        sort($commands);
        return array_unique($commands);
    }

    /**
     * Returns an array of commands an their descriptions.
     * @return array all available commands as keys and their description as values.
     */
    protected function getCommandDescriptions()
    {
        $descriptions = [];
        foreach ($this->getCommands() as $command) {
            $description = '';

            $result = Yii::$app->createController($command);
            if ($result !== false) {
                list($controller, $actionID) = $result;
                /** @var Controller $controller */
                $description = $controller->getHelpSummary();
            }

            $descriptions[$command] = $description;
        }

        return $descriptions;
    }

    /**
     * Returns all available actions of the specified controller.
     * @param Controller $controller the controller instance
     * @return array all available action IDs.
     */
    public function getActions($controller)
    {
        $actions = array_keys($controller->actions());
        $class = new \ReflectionClass($controller);
        foreach ($class->getMethods() as $method) {
            $name = $method->getName();
            if ($name !== 'actions' && $method->isPublic() && !$method->isStatic() && strpos($name, 'action') === 0) {
                $actions[] = Inflector::camel2id(substr($name, 6), '-', true);
            }
        }
        //sort($actions);

        return array_unique($actions);
    }

    /**
     * Returns available commands of a specified module.
     * @param \yii\base\Module $module the module instance
     * @return array the available command names
     */
    protected function getModuleCommands($module)
    {
        $prefix = $module instanceof Application ? '' : $module->getUniqueId() . '/';
        $commands = [];        
        $controllerPath = $module->getControllerPath();
        if (is_dir($controllerPath)) {
            $files = scandir($controllerPath);
            foreach ($files as $file) {
                if (!empty($file) && substr_compare($file, 'Controller.php', -14, 14) === 0) {
                    $controllerClass = $module->controllerNamespace . '\\' . substr(basename($file), 0, -4);
                    if ($this->validateControllerClass($controllerClass)) {
                        $commands[] = $prefix . Inflector::camel2id(substr(basename($file), 0, -14));
                    }
                }
            }
        }
        return $commands;
    }

    /**
     * Validates if the given class is a valid web controller class.
     * @param string $controllerClass
     * @return boolean
     */
    protected function validateControllerClass($controllerClass)
    {
        if (class_exists($controllerClass)) {
            $class = new \ReflectionClass($controllerClass);
            if(!$class->isAbstract() && $class->isSubclassOf('yii\web\Controller')){
                if($class->hasProperty('noapidoc')){
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    
    
    
    
}

