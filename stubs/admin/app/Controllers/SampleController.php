<?php

namespace {$namespace}\Controllers;

class SampleController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        return addon()->view('sample');
    }
}
