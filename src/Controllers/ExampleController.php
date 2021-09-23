<?php

namespace ChunkUpload\Controllers;

use ChunkUpload\BaseController;

class ExampleController extends BaseController
{
    public function index()
    {
        return view('chunk-upload::example');
    }
}
