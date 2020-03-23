<?php

namespace Unterrichtsplanung\Routes\Users;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Unterrichtsplanung\Errors\AuthorizationFailedException;
use Unterrichtsplanung\Errors\Error;
use Unterrichtsplanung\UnterrichtsplanungTrait;
use Unterrichtsplanung\UnterrichtsplanungController;

class UsersShow extends UnterrichtsplanungController
{
    use UnterrichtsplanungTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $data = [
            'id'       => $user->id,
            'username' => $user->username,
            'fullname' => get_fullname($user->id),
            'status'   => $user->perms,
            'admin'    => \RolePersistence::isAssignedRole(
                $GLOBALS['user']->user_id,
                $this->container['roles']['admin'])
        ];

        return $this->createResponse([
            'type' => 'user',
            'id'   => $user->id,
            'data' => $data
        ], $response);
    }
}
