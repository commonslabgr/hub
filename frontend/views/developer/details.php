<?php
    use kartik\tabs\TabsX;

    $this->title = Yii::$app->name . ' | ' . $app->name;
    $this->params['breadcrumbs'][] = ['label' => Yii::t('common', 'Developer')];
    $this->params['breadcrumbs'][] = ['label' => Yii::t('common', 'My apps'), 'url' => ['developer/index']];
    $this->params['breadcrumbs'][] = $app->name;
?>

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <h1 style="margin-bottom:40px;">
            <?php if($app->image_url != null){ ?>
                <div class="app_icon_image big_icon" style="background-image: url(<?php echo $app->image_url; ?>)"></div>
            <?php } else { ?>
                <div class="app_icon big_icon">
                    <span><?php echo strtoupper($app->name[0]); ?></span>
                </div>
            <?php } ?>
            <?php echo $app->name; ?>
        </h1>
    </div>
</div>
<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <?php
            $items = [
                [
                    'label' =>'<i class="fa fa-cog"></i> ' . Yii::t('common', 'Settings'),
                    'content' => Yii::$app->controller->renderPartial('_settings', ['app' => $app, 'appDevelopers' => $appDevelopers]),
                    'active' => true
                ],
                // [
                //     'label' =>'<i class="fa fa-trophy"></i> ' . Yii::t('common', 'Badges'),
                //     'content' => Yii::$app->controller->renderPartial('_badges')
                // ],
                // [
                //     'label' =>'<i class="fa fa-pie-chart"></i> ' . Yii::t('common', 'Stats'),
                //     'content' => Yii::$app->controller->renderPartial('_stats')
                // ]
            ];

            echo TabsX::widget([
                'items' => $items,
                'position' => TabsX::POS_ABOVE,
                'encodeLabels' => false
            ]);
        ?>
    </div>
</div>
