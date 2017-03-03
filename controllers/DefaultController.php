<?php
namespace mopon\apidoc\controllers;

use Yii;
use yii\helpers\Url;
use mopon\apidoc\components\ApiHandler;

/**
 * 接口文档
 */
class DefaultController extends \yii\web\Controller
{
    public $noapidoc;


    public $layout = 'main';


    /**
     * 文档主页
     * @return type
     */
    public function actionIndex($command=null){
        $help = null;
        if($command){
            $help = ApiHandler::CommandHelp($command);
        }
        
        return $this->render('doc',['help'=>$help]);
    }
    
    /**
     * 接口调试
     */
    public function actionRun($command){
        $params = Yii::$app->request->post('form');
        $uri = Url::to($command,true);
        $paramsTep = $this->http_build_query($params);
        $result = $this->requestByCurl($uri, $paramsTep, 'get');
        
        echo "<pre>";
        
        echo "<h3>测试描述：</h3>";
        echo "调用接口：{$command} \r\n";
        echo "参数签名：{$paramsTep} \r\n";
        echo "返回结果：{$result}\r\n";
        
        echo "<h3>格式化分析：</h3>";
        echo "接收参数：";
        echo print_r($params,true);
        echo "\r\n";
        
        echo "输出数据:";
        if(preg_match('/^\{.+\}$/', $result)){
            echo print_r(json_decode($result,true),true);
        }
        else{
            echo $result;
        }
        echo "\r\n";
        echo "</pre>";
    }

    /**
     * 此处完成签名
     * @param type $command
     * @param type $params
     */
    public function http_build_query($params){
        if(is_array($params)){
            return http_build_query($params);
        }
        return null;
    }
    
    /**
     * 
     * @param type $uri
     * @param type $paramsTep
     * @param type $type
     * @return type
     * @throws ErrException
     */
    public function requestByCurl($uri,$paramsTep='',$type='get')
    {
        //定义content-type为xml,注意是数组 
        $header[] = "Content-type:application/x-www-form-urlencoded";  
        
        //初始一个curl会话
        $ch = curl_init($uri);
        
        //设置
        curl_setopt($ch,CURLOPT_URL,$uri);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
        //POST发送
        if($type=='post')
        {
            curl_setopt($ch,CURLOPT_POST,1);//设置发送方式：post 
            curl_setopt($ch,CURLOPT_POSTFIELDS, $paramsTep);//设置发送数据
        }
        curl_setopt($ch,CURLOPT_TIMEOUT,30);//设置超时时间
        $content = curl_exec($ch);//抓取URL并把它传递给浏览器
//        $curlinfo_content_type = curl_getinfo($ch,CURLINFO_CONTENT_TYPE);
        $error = curl_errno($ch);
        if($error){
            throw new \Exception("接口发生错误：API=>{$uri}");
        }
        curl_close($ch);
       
//        $curlinfo_content_typeArr = explode(';', $curlinfo_content_type);
        
        //构建返回数组
//        $response = [
//            'content_type'=>$curlinfo_content_typeArr[0],
//            'content'=>$content
//        ];
        
        //返回
        return $content;       
    }

}
