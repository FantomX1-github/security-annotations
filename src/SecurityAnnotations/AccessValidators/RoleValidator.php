<?php
/**
 * This file is part of the nepada/security-annotations.
 * Copyright (c) 2017 Petr Morávek (petr@pada.cz)
 */

declare(strict_types = 1);

namespace Nepada\SecurityAnnotations\AccessValidators;

use Nepada\SecurityAnnotations\UnexpectedValueException;
use Nette;
use Nette\Security\Permission;
use Nette\Security\User;
use Nette\Utils\Validators;


class RoleValidator implements IAccessValidator
{

    use Nette\SmartObject;

    /** @var User */
    private $user;

    /** @var Permission|null */
    private $permission;


    /**
     * @param User $user
     * @param Permission|null $permission
     */
    public function __construct(User $user, ?Permission $permission = null)
    {
        $this->user = $user;
        $this->permission = $permission;
    }

    /**
     * @param mixed $annotation parsed value of annotation
     * @throws Nette\Application\BadRequestException
     */
    public function validateAccess($annotation): void
    {
        if ($annotation instanceof \Traversable) {
            $annotation = iterator_to_array($annotation);
        }
        if (is_string($annotation)) {
            $roles = [$annotation];
        } elseif (Validators::isList($annotation) && Validators::everyIs($annotation, 'string') && count($annotation) > 0) {
            $roles = $annotation;
        } else {
            throw new UnexpectedValueException('Unexpected annotation type, string or a list of strings expected.');
        }

        $success = false;
        foreach ($roles as $role) {
            if ($this->isUserInRole($role)) {
                $success = true;
                break;
            }
        }

        if (!$success) {
            $message = sprintf("User is not in any of the required roles '%s'.", implode("', '", $roles));
            throw new Nette\Application\ForbiddenRequestException($message);
        }
    }

    /**
     * @param string $requiredRole
     * @return bool
     */
    private function isUserInRole(string $requiredRole): bool
    {
        $found = false;
        $roles = $this->user->getRoles();

        if (in_array($requiredRole, $roles, true)) {
            $found = true;

        } elseif ($this->permission !== null) {
            foreach ($roles as $role) {
                try {
                    if ($this->permission->roleInheritsFrom($role, $requiredRole)) {
                        $found = true;
                        break;
                    }
                } catch (Nette\InvalidStateException $exception) {
                    // ignore undefined roles
                }
            }
        }

        return $found;
    }

}