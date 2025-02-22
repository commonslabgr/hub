<?php
    use yii\grid\GridView;
    use yii\grid\ActionColumn;
    use yii\helpers\Url;
    use yii\helpers\Html;
    use frontend\models\WenetApp;
    use frontend\models\AppBadge;
?>

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

        <?php if ($app->status != WenetApp::STATUS_ACTIVE): ?>
            <a href="<?= Url::to(['/badge/create', 'appId' => $app->id]); ?>" class="btn btn-primary pull-right" style="margin: 20px 0;">
                <i class="fa fa-plus" aria-hidden="true"></i>
                <?php echo Yii::t('badge', 'Create a badge'); ?>
            </a>
        <?php else: ?>

            <div class="alert alert-warning" role="alert" style="margin-top:20px;">
                <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                <?php echo Yii::t('badge', 'WARNING - Operations are available only for apps with status In Develpment.'); ?>
            </div>

        <?php endif; ?>

        <?php
            echo GridView::widget([
                'id' => 'badge_apps_grid',
                'layout' => "{items}\n{summary}\n{pager}",
                'dataProvider' => $appBadges,
                'columns' => [
                    [
                        'attribute' => 'image',
                        'format' => 'raw',
                        'value' => function ($data) {
                            return '<div class="badge_image" style="background-image:url('.$data->image.')";></div>';
                        },
                    ],
                    [
                        'header' => Yii::t('badge', 'Name, Description'),
                        'format' => 'raw',
                        'value' => function ($data) {
                            $description = $data->description;
                            if(strlen($description) > 110){
                                $description = substr($data->description, 0, 110).'...';
                            }
                            return '<p><strong>'.$data->name.'</strong><br>'.$description.'</p>';
                        },
                    ],
                    [
                        'header' => Yii::t('badge', 'Incentive Serveer ID'),
                        'format' => 'raw',
                        'value' => function ($data) {
                            return '<pre>'.$data->incentive_server_id.'</pre>';
                        },
                    ],
                    [
                        'header' => Yii::t('badge', 'Type'),
                        'format' => 'raw',
                        'value' => function ($data) {
                            if($data->details()->isTaskBadge()){
                                $value = '<span>'.Yii::t('badge', 'Task').'</span>';
                            } else if ($data->details()->isTransactionBadge()){
                                $value = '<span style="display:block;margin-bottom:5px;">'.Yii::t('badge', 'Transaction').'</span><pre>'.$data->label.'</pre>';
                            } else if ($data->details()->isMessageCallbackBadge()){
                                $value = '<span style="display:block;margin-bottom:5px;">'.Yii::t('badge', 'Callback').'</span><pre>'.$data->label.'</pre>';
                            } else {
                                Yii::warning("Badge [$data->id] has un-supported badge type [$data->label]");
                                $value = '<pre>?</pre>';
                            }
                            return $value;
                        },
                    ],
                    [
                        'attribute' => 'threshold',
                        'format' => 'raw',
                        'value' => function ($data) {
                            return '<span style="display:block;text-align:center;">'.$data->threshold.'</span>';
                        },
                    ],
                    [
                        'headerOptions' => [
                            'class' => 'action-column',
                        ],
                        'class' => ActionColumn::className(),
                        'visible' => $app->status != WenetApp::STATUS_ACTIVE,
                        'template' => '{update} {delete}',
                        'buttons'=>[
                            'update' => function ($url, $model) {
                                $url = Url::to(['/badge/update', 'id' => $model->id, 'appId' => $model->wenetApp->id]);
                                return Html::a('<span class="actionColumn_btn"><i class="fa fa-pencil"></i></span>', $url, [
                                    'title' => Yii::t('common', 'edit'),
                                ]);
                            },
                            'delete' => function ($url, $model) {
                                $url = Url::to(['/badge/delete', 'id' => $model->id, 'appId' => $model->wenetApp->id]);
                                return Html::a('<span class="actionColumn_btn delete_btn open_modal"><i class="fa fa-trash"></i></span>', $url, [
                                    'title' => Yii::t('common', 'delete'),
                                ]);
                            }
                        ]
                    ]
                ]
            ]);
        ?>
        <?php
            if($app->status != WenetApp::STATUS_ACTIVE){
                echo Yii::$app->controller->renderPartial('../_delete_modal', ['title' => Yii::t('badge', 'Delete badge')]);
            }
        ?>
    </div>
</div>
