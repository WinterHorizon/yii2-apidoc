# Yii2 APIDOC

## 安装
```
composer require --dev anes/apidoc
```

## 排除
排除有属性`$noapidoc`的控制器
eg:
```
class DefaultController extends \yii\web\Controller
{
    public $noapidoc;
    ...
}
```
排除命名为`^test(\w+)$`的方法
eg:
```
    ...
    public function actionTestimport(){
        ...
    }
    ...
```

## 方法名
方法注解第一行

## 自定义接口测试
第二行如果命名`^testApi:(.+)$`则`$1`为接口测试地址
 
##使用示例
```
$config['bootstrap'][] = 'apidoc';
$config['modules']['apidoc'] = [
      'class' => 'anes\apidoc\Module',
   ];
```
 
##必须在控制器基类添加下行
```
use \anes\apidoc\components\BaseApidoc;
```

