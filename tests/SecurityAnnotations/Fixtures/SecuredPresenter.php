<?php
declare(strict_types = 1);

namespace NepadaTests\SecurityAnnotations\Fixtures;

use Nepada\SecurityAnnotations;
use Nette;

class SecuredPresenter extends Nette\Application\UI\Presenter
{

    use SecurityAnnotations\SecurityAnnotations;

    public function __construct()
    {
        parent::__construct();
        $this->autoCanonicalize = false;
    }

    public function actionDefault(): void
    {
    }

    public function renderDefault(): void
    {
        $this->sendResponse(new Nette\Application\Responses\TextResponse(''));
    }

    protected function createComponentFoo(): SecuredControl
    {
        $control = new SecuredControl();
        $control->injectRequirementsChecker($this->requirementsChecker);
        return $control;
    }

}
