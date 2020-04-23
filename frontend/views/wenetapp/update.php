<?php
    use yii\widgets\ActiveForm;
    use kartik\select2\Select2;
    use kartik\switchinput\SwitchInput;
    use yii\helpers\Html;

    $this->title = Yii::$app->name . ' | ' . Yii::t('common', 'Update app') . ' - ' . $app->name;
    $this->params['breadcrumbs'][] = ['label' => Yii::t('common', 'Developer'), 'url' => ['index-developer']];
    $this->params['breadcrumbs'][] = Yii::t('common', 'Update app') . ' - ' . $app->name;
?>

<!-- se vuoi renderla attiva tutti! i campi devono essere compilati e deve esserci almeno una piattaforma definita -->

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <?php
            $form = ActiveForm::begin([
                'id' => 'app-update-form',
                'options' => ['class' => ''],
            ])
        ?>
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                    <?php echo $form->field($app, 'name'); ?>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                    <?php echo $form->field($app, 'status')->widget(SwitchInput::classname(), []); ?>
                    <!-- TODO style! -->
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                    <?php echo $form->field($app, 'description')->textarea(); ?>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                    <?php echo $form->field($app, 'message_callback_url')->textarea(); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                    <?php echo $form->field($app, 'associatedCategories')->widget(Select2::classname(), [
                        'data' => ['puppa' => 'prova'],
                        'options' => ['placeholder' => Yii::t('app', 'Select tags ...')],
                        'pluginOptions' => [
                            'allowClear' => true
                        ],
                    ]); ?>
                    <!-- https://demos.krajee.com/widget-details/select2 -->
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="form-group">
                        <?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-primary']) ?>
                    </div>
                </div>
            </div>
        <?php ActiveForm::end() ?>
    </div>
</div>
