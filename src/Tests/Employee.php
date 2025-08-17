<?php

namespace DLCore\Test;

use DLCore\Database\Model;

final class Employee extends Model {
    protected static ?string $table = "dl_employee";
}
