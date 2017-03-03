<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
/* @var $this yii\web\View */

$this->title = "Api文档";

if(!$help){
    return;
}

$this->title = "{$help['name']}";

?>
<div class="ads-index">

    <div class="body-content">
        <div class="row">
            <div class="col-lg-12">
                <h5>接口路由：<?=$help['rule'] ?></h5>
                <h5>接口名称：<?=$help['name'] ?></h5>
                <h5>接口描述：<?=$help['desc'] ?></h5>
            
            <?php if($help['options']): ?>
                <table class="table table-striped table-bordered">
                    <tr>
                        <td>参数</td><td>类型</td><td>说明</td><td>必填</td><td>默认</td>
                    </tr>
                <?php foreach ($help['options'] as $field=>$option): ?>
                    <tr>
                        <td><?=$field?></td>
                        <td><?=$option['type']?></td>
                        <td><?=$option['comment']?></td>
                        <td><?=$option['required']?'是':'否'?></td>
                        <td><?=$option['default']?></td>
                    </tr>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>
                
            </div>
        </div>
        
        <?php if(isset($help['testApi'])): ?>
            <div class="row">
                <div class="col-lg-12">
                    <h5><?= Html::a("接口测试", $help['testApi'], ['class'=>'btn btn-primary','target'=>'result']) ?></h5>
                    <iframe name="result" class="col-lg-12" style="height: 260px;"></iframe>
                </div>
            </div>
        <?php else: ?>
        
        <div class="row">
            <div class="col-lg-12">
                <?php $form = ActiveForm::begin(['id' =>'form-test','action'=>Url::to(['run','command'=>$help['rule']]),'options'=>['target'=>'result']]); ?>    
                <h5><?= Html::submitButton("接口测试", ['class'=>'btn btn-primary']) ?></h5>
                
                <?php foreach ($help['options'] as $filed=>$option): ?>
                    <?php if(preg_match('/^array(\/array\((.*)\))?$/', $option['type'],$preg_arr)): ?>
                        <h5>
                        <?php if(isset($preg_arr[1])): ?>
                            <?= Html::label($option['comment'], $field, ['order'=>0]) ?> :
                            <?php $items= explode(',', $preg_arr[2]) ?>
                            <?php foreach ($items as $item): ?>
                                <?php $item_arr = explode('=>',$item) ?>
                                <?php if(count($item_arr)==2):?>
                                    <?php list($item_filed,$item_comment) = $item_arr; ?>
                                    <?= Html::textInput("form[{$filed}][0][{$item_filed}]",null,['placeholder'=>$item_comment,'class' => 'control','required'=>$option['required'],'validate'=>$option['type']]); ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?= Html::textInput("form[{$filed}][]",$option['default'],['placeholder'=>$option['comment'],'class' => 'control','required'=>$option['required'],'validate'=>$option['type']]); ?>
                        <?php endif; ?>
                            <?= Html::button('+', ['onClick'=>'additem(this)']) ?>
                        </h5>
                    <?php else: ?>
                        <?= Html::textInput("form[{$filed}]",$option['default'],['placeholder'=>$option['comment'],'class' => 'control','required'=>$option['required'],'validate'=>$option['type']]); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php ActiveForm::end(); ?>
                <h5></h5>
                <iframe name="result" class="col-lg-12" style="height: 260px;"></iframe>
            </div>
        </div>
        
        <?php endif; ?>
        
    </div>
</div>
<script type="text/javascript">
    function additem(mod){
        var temp = $(mod).parent().clone();
        var label = temp.find('label');
        if(label){
            var order=parseInt(label.attr('order'))+1;
            var filed=label.attr('for');
            label.attr('order',order);
            temp.find('input').each(function(){
                var name = $(this).attr('name');
                $(this).attr('name',name.replace(/\[\d\]/,"["+order+"]"));
            })
        }
        $(mod).parent().after(temp);
        $(mod).remove();
    }
</script>