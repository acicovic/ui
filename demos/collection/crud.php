<?php

declare(strict_types=1);

namespace Atk4\Ui\Demos;

/** @var \Atk4\Ui\App $app */
require_once __DIR__ . '/../init-app.php';

$model = new CountryLock($app->db);

$crud = \Atk4\Ui\Crud::addTo($app, ['ipp' => 10]);

// callback for model action add form.
$crud->onFormAdd(function ($form, $t) {
    $form->js(true, $form->getControl('name')->jsInput()->val('Entering value via javascript'));
});

// callback for model action edit form.
$crud->onFormEdit(function ($form) {
    $form->js(true, $form->getControl('name')->jsInput()->attr('readonly', true));
});

// callback for both model action edit and add.
$crud->onFormAddEdit(function ($form, $ex) {
    $form->onSubmit(function (\Atk4\Ui\Form $form) use ($ex) {
        return [$ex->hide(), new \Atk4\Ui\JsToast('Submit all right! This demo does not saved data.')];
    });
});

$crud->setModel($model);

$crud->addDecorator($model->title_field, [\Atk4\Ui\Table\Column\Link::class, ['test' => false, 'path' => 'interfaces/page'], ['_id' => 'id']]);

\Atk4\Ui\View::addTo($app, ['ui' => 'divider']);

$columns = \Atk4\Ui\Columns::addTo($app);
$column = $columns->addColumn(0, 'ui blue segment');

// Crud can operate with various fields
\Atk4\Ui\Header::addTo($column, ['Configured Crud']);
$crud = \Atk4\Ui\Crud::addTo($column, [
    'displayFields' => ['name'], // field to display in Crud
    'editFields' => ['name', 'iso', 'iso3'], // field to display on 'edit' action
    'ipp' => 5,
    'paginator' => ['range' => 2, 'class' => ['blue inverted']],  // reduce range on the paginator
    'menu' => ['class' => ['green inverted']],
    'table' => ['class' => ['red inverted']],
]);
// Condition on the model can be applied on a model
$model = new CountryLock($app->db);
$model->addCondition('numcode', '<', 200);
$model->onHook(\Atk4\Data\Model::HOOK_VALIDATE, function ($model, $intent) {
    $err = [];
    if ($model->get('numcode') >= 200) {
        $err['numcode'] = 'Should be less than 200';
    }

    return $err;
});
$crud->setModel($model);

// Because Crud inherits Grid, you can also define custom actions
$crud->addModalAction(['icon' => [\Atk4\Ui\Icon::class, 'cogs']], 'Details', function ($p, $id) use ($crud) {
    \Atk4\Ui\Message::addTo($p, ['Details for: ' . $crud->model->load($id)->get('name') . ' (id: ' . $id . ')']);
});

$column = $columns->addColumn();
\Atk4\Ui\Header::addTo($column, ['Cutomizations']);

/** @var \Atk4\Ui\UserAction\ModalExecutor $myExecutorClass */
$myExecutorClass = get_class(new class() extends \Atk4\Ui\UserAction\ModalExecutor {
    public function addFormTo(\Atk4\Ui\View $view): \Atk4\Ui\Form
    {
        $columns = \Atk4\Ui\Columns::addTo($view);
        $left = $columns->addColumn();
        $right = $columns->addColumn();

        $result = parent::addFormTo($left);

        if ($this->action->getOwner()->get('is_folder')) {
            \Atk4\Ui\Grid::addTo($right, ['menu' => false, 'ipp' => 5])
                ->setModel($this->action->getOwner()->ref('SubFolder'));
        } else {
            \Atk4\Ui\Message::addTo($right, ['Not a folder', 'warning']);
        }

        return $result;
    }
});

$file = new FileLock($app->db);
$app->getExecutorFactory()->registerExecutor($file->getUserAction('edit'), [$myExecutorClass]);

$crud = \Atk4\Ui\Crud::addTo($column, [
    'ipp' => 5,
]);

$crud->menu->addItem(['Rescan', 'icon' => 'recycle']);

// Condition on the model can be applied after setting the model
$crud->setModel($file)->addCondition('parent_folder_id', null);
