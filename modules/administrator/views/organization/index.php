<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\Pjax;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\OrganizationSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Organizations';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="organization-index">

    <div class="add_new_btn_group text-right">
<?= Html::a('Create Organization', ['create'], ['class' => 'btn btn-success']) ?>
    </div>
    <p class="clearfix"></p> 

    <?php
    Pjax::begin([
        'id' => 'grid-data',
        'enablePushState' => FALSE,
        'enableReplaceState' => FALSE,
        'timeout' => false,
    ]);
    ?>
    <div class="filter_sec">
        <?php
        echo $this->render('_search', ['model' => $searchModel]);
        ?>
    </div>
    <p class="clearfix"></p> 
    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'layout' => "{items}\n{pager}",
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
            'sendbird_app_id',
            [
                'attribute' => 'status',
                'format' => 'html',
                'filter' => false,
                'value' => function ($model) {
                    if ($model->status == 1) {
                        $text = 'Active';
                        $class = 'label-success';
                    } else {
                        $text = 'Inactive';
                        $class = 'label-warning';
                    }

                    return '<span class="label ' . $class . '">' . $text . '</span>';
                }
            ],
            ['class' => 'yii\grid\ActionColumn', 'template' => '{update}',],
        ],
    ]);
    ?>
<?php Pjax::end() ?>
</div>
