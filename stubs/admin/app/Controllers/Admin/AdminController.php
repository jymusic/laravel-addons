<?php

namespace {$namespace}\Controllers\Admin;

use {$namespace}\Controllers\Controller;

class AdminController extends Controller
{
    public function index()
    {
        return addon()->view('admin.index');
    }
}
