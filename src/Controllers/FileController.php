<?php

declare(strict_types=1);

namespace DLCore\Controllers;

use DLCore\Core\BaseController;

final class FileController extends BaseController {

    /**
     * Ejecutar una prueba con el archivo
     *
     * @return array
     */
    public function upload(): array {
        $this->set_basedir("/storage");
        return $this->upload_file('file');
    }
}
