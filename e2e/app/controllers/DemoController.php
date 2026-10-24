<?php

namespace E2e\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Paginator\Adapter\NativeArray;
use PhalconInertia\Inertia;

class DemoController extends Controller
{
    public function dashboardAction()
    {
        $session = $this->session;

        return $this->inertia->render('Dashboard', [
            'counter' => function () use ($session) {
                $count = $session->get('counter', 0) + 1;
                $session->set('counter', $count);

                return $count;
            },
            'stats' => Inertia::defer(function () {
                return ['visits' => 42];
            }),
            'audit' => Inertia::optional(function () {
                return 'audit log loaded';
            }),
            'permissions' => Inertia::defer(function () {
                throw new \RuntimeException('Permissions service is down');
            }, 'default', true),
            'plans' => Inertia::once(function () use ($session) {
                $calls = $session->get('plans', 0) + 1;
                $session->set('plans', $calls);

                return $calls;
            }),
        ]);
    }

    public function feedAction()
    {
        $adapter = new NativeArray([
            'data' => array_map(function ($n) {
                return 'Item '.$n;
            }, range(1, 30)),
            'limit' => 10,
            'page' => max(1, (int) $this->request->getQuery('page', 'int', 1)),
        ]);

        return $this->inertia->render('Feed', [
            'items' => Inertia::scroll(method_exists($adapter, 'paginate') ? $adapter->paginate() : $adapter->getPaginate()),
        ]);
    }

    public function jumpAction()
    {
        return $this->response->redirect('dashboard#stats');
    }

    public function noopAction() {}

    public function adminAction()
    {
        return $this->inertia->setRootView('admin')->render('Admin');
    }

    public function awayAction()
    {
        return $this->inertia->location('/dashboard?external=1');
    }

    public function notFoundAction()
    {
        return $this->inertia->render('Error', ['status' => 404])->setStatusCode(404, 'Not Found');
    }
}
