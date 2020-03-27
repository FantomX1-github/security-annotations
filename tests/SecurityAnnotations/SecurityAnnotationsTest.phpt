<?php
declare(strict_types = 1);

namespace NepadaTests\SecurityAnnotations;

use Mockery;
use Nepada\SecurityAnnotations;
use NepadaTests\SecurityAnnotations\Fixtures\SecuredPresenter;
use NepadaTests\TestCase;
use Nette;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';


/**
 * @testCase
 */
class SecurityAnnotationsTest extends TestCase
{

    public function testGetRequirementsCheckerFromDI(): void
    {
        $requirementsChecker = Mockery::mock(SecurityAnnotations\RequirementsChecker::class);
        $requirementsChecker->shouldReceive('protectElement');

        $container = Mockery::mock(Nette\DI\Container::class);
        $container->shouldReceive('getByType')->withArgs([SecurityAnnotations\RequirementsChecker::class])->andReturn($requirementsChecker);

        $presenter = new SecuredPresenter();
        $presenter->injectPrimary($container, null, null, Mockery::mock(Nette\Http\IRequest::class), Mockery::mock(Nette\Http\IResponse::class));
        Assert::same($requirementsChecker, $presenter->getRequirementsChecker());
        Assert::same($requirementsChecker, $presenter->getComponent('foo')->getRequirementsChecker());
    }

    public function testCheckRequirements(): void
    {
        $requirementsChecker = Mockery::mock(SecurityAnnotations\RequirementsChecker::class);
        $requirementsChecker->shouldReceive('protectElement')->times(3);

        $presenter = new SecuredPresenter();
        $presenter->injectPrimary(null, null, null, new Nette\Http\Request(new Nette\Http\UrlScript('http://example.com')), new Nette\Http\Response());
        $presenter->setRequirementsChecker($requirementsChecker);
        Assert::noError(function () use ($presenter): void {
            $request = new Nette\Application\Request('SecuredPresenter', 'GET', ['action' => 'default']);
            $presenter->run($request);
        });
    }

}


(new SecurityAnnotationsTest())->run();