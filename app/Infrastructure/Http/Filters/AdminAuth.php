<?php

namespace App\Infrastructure\Http\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Allows any authenticated admin (admin or shop_admin role).
 * Sets AdminAuthContext for use by controllers and downstream filters.
 */
class AdminAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        /** @var \CodeIgniter\HTTP\IncomingRequest $request */
        $token = $request->getCookie('sn_admin_session');

        if (empty($token)) {
            return $this->unauthorized('Unauthorized');
        }

        $session = service('adminSessionRepository')->find($token);

        if ($session === null) {
            service('response')->deleteCookie('sn_admin_session');
            return $this->unauthorized('Session expired');
        }

        AdminAuthContext::set([
            'user_id' => (int) $session['user_id'],
            'role'    => $session['role'],
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}

    private function unauthorized(string $message): ResponseInterface
    {
        return service('response')
            ->setStatusCode(401)
            ->setContentType('application/json')
            ->setBody(json_encode(['error' => $message]));
    }
}
