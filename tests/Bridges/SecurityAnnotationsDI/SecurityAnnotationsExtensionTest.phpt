<?php
declare(strict_types = 1);

namespace NepadaTests\Bridges\SecurityAnnotationsDI;

use Nepada\SecurityAnnotations;
use NepadaTests\TestCase;
use Nette;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class SecurityAnnotationsExtensionTest extends TestCase
{

    /** @var Nette\Configurator */
    private $configurator;

    public function setUp(): void
    {
        $this->configurator = new Nette\Configurator();
        $this->configurator->setTempDirectory(TEMP_DIR);
        $this->configurator->setDebugMode(true);
        $this->configurator->addConfig(__DIR__ . '/fixtures/config.neon');
    }

    public function testDefaultValidators(): void
    {
        $container = $this->configurator->createContainer();

        Assert::type(SecurityAnnotations\AccessValidators\LoggedInValidator::class, $container->getService('securityAnnotations.accessValidator.loggedIn'));
        Assert::type(SecurityAnnotations\AccessValidators\RoleValidator::class, $container->getService('securityAnnotations.accessValidator.role'));
        Assert::type(SecurityAnnotations\AccessValidators\PermissionValidator::class, $container->getService('securityAnnotations.accessValidator.allowed'));

        $requirementsChecker = $container->getService('securityAnnotations.requirementsChecker');
        Assert::type(SecurityAnnotations\RequirementsChecker::class, $requirementsChecker);

        $reflection = new \ReflectionProperty(SecurityAnnotations\RequirementsChecker::class, 'accessValidators');
        $reflection->setAccessible(true);
        $accessValidators = $reflection->getValue($requirementsChecker);
        Assert::count(3, $accessValidators);
        Assert::type(SecurityAnnotations\AccessValidators\LoggedInValidator::class, $accessValidators['loggedIn']);
        Assert::type(SecurityAnnotations\AccessValidators\RoleValidator::class, $accessValidators['role']);
        Assert::type(SecurityAnnotations\AccessValidators\PermissionValidator::class, $accessValidators['allowed']);
    }

    public function testCustomValidators(): void
    {
        $this->configurator->addConfig(__DIR__ . '/fixtures/config.custom-validators.neon');
        $container = $this->configurator->createContainer();
        $requirementsChecker = $container->getByType(SecurityAnnotations\RequirementsChecker::class);

        $reflection = new \ReflectionProperty(SecurityAnnotations\RequirementsChecker::class, 'accessValidators');
        $reflection->setAccessible(true);
        $accessValidators = $reflection->getValue($requirementsChecker);
        Assert::count(2, $accessValidators);
        Assert::type(FooValidator::class, $accessValidators['foo']);
        Assert::type(BarValidator::class, $accessValidators['bar']);
    }

    public function testInvalidValidator(): void
    {
        $this->configurator->addConfig(__DIR__ . '/fixtures/config.invalid-validator.neon');

        Assert::exception(
            function (): void {
                $this->configurator->createContainer();
            },
            \LogicException::class,
            'Access validator class \'NepadaTests\SecurityAnnotations\SecuredPresenter\' must implement IAccessValidator interface.'
        );
    }

    public function testNotFoundValidator(): void
    {
        $this->configurator->addConfig(__DIR__ . '/fixtures/config.not-found-validator.neon');

        Assert::exception(
            function (): void {
                $this->configurator->createContainer();
            },
            \LogicException::class,
            'Access validator class \'NotFoundValidator\' not found.'
        );
    }

}


(new SecurityAnnotationsExtensionTest())->run();
